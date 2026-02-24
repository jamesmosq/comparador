<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Schedule;
use App\Models\Competencia;
use Spatie\SimpleExcel\SimpleExcelReader;
use Illuminate\Support\Facades\Storage;

new class extends Component {
    use WithFileUploads;

    public $institutions;
    public $groups       = [];
    public $students     = [];
    public $schedules    = [];
    public $competencias = [];
    public $student_risks = [];

    public $institution_id;
    public $group_id;
    public $competencia_id;
    public $attendance_date;

    public $student_file;
    public $attendance_status  = [];   // 'present' | 'absent' | 'justified'
    public $attendance_remarks = [];

    // Agregar estudiante manualmente
    public string $new_student_name       = '';
    public string $new_student_email      = '';
    public string $new_student_identifier = '';

    // Editar estudiante
    public $editing_student_id              = null;
    public string $editing_student_name       = '';
    public string $editing_student_email      = '';
    public string $editing_student_identifier = '';
    public bool $showEditStudentModal         = false;

    // Búsqueda
    public string $search = '';

    public function mount()
    {
        $this->institutions    = Institution::visibleTo(auth()->user())->get();
        $this->attendance_date = date('Y-m-d');
    }

    public function updatedInstitutionId($value)
    {
        $this->groups        = Group::where('institution_id', $value)->get();
        $this->group_id      = null;
        $this->competencia_id = null;
        $this->competencias  = [];
        $this->students      = [];
        $this->schedules     = [];
        $this->student_risks = [];
        $this->search        = '';
    }

    public function updatedGroupId($value)
    {
        $this->competencia_id = null;
        $this->student_risks  = [];
        if ($value) {
            $this->loadStudents();
            $this->schedules = Schedule::where('group_id', $value)->get();
            // Cargar competencias del programa de la ficha
            $group = Group::with('programaFormacion.competencias')->find($value);
            $this->competencias = $group?->programaFormacion?->competencias ?? collect([]);
        } else {
            $this->students     = [];
            $this->schedules    = [];
            $this->competencias = [];
        }
        $this->search = '';
    }

    public function updatedCompetenciaId($value)
    {
        $this->loadStudents();
    }

    public function updatedAttendanceDate()
    {
        $this->loadStudents();
    }

    public function loadStudents()
    {
        if ($this->group_id) {
            $this->students = Student::where('group_id', $this->group_id)
                ->orderBy('name')
                ->get();
            $this->initAttendance();
            $this->loadStudentRisks();
        } else {
            $this->students           = [];
            $this->attendance_status  = [];
            $this->attendance_remarks = [];
            $this->student_risks      = [];
        }
    }

    protected function loadStudentRisks(): void
    {
        $this->student_risks = [];
        if (! $this->competencia_id || empty($this->students)) {
            return;
        }
        $studentIds = collect($this->students)->pluck('id');
        $data = Attendance::selectRaw('student_id, COUNT(*) as total, SUM(CASE WHEN is_present = 0 AND is_justified = 0 THEN 1 ELSE 0 END) as unjustified')
            ->where('competencia_id', $this->competencia_id)
            ->whereIn('student_id', $studentIds)
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        foreach ($this->students as $student) {
            $row = $data->get($student->id);
            $total      = $row ? (int)$row->total : 0;
            $unjustified = $row ? (int)$row->unjustified : 0;
            $pct        = $total > 0 ? round($unjustified / $total * 100) : 0;
            $this->student_risks[$student->id] = ['total' => $total, 'unjustified' => $unjustified, 'pct' => $pct];
        }
    }

    public function getFilteredStudentsProperty()
    {
        if (empty($this->search)) {
            return $this->students;
        }
        $q = mb_strtolower($this->search);
        return collect($this->students)->filter(function ($student) use ($q) {
            return str_contains(mb_strtolower($student->name), $q)
                || str_contains(mb_strtolower($student->identifier ?? ''), $q);
        });
    }

    public function getPresentCountProperty(): int
    {
        return collect($this->attendance_status)->filter(fn($v) => $v === 'present')->count();
    }

    public function getAbsentCountProperty(): int
    {
        return collect($this->attendance_status)->filter(fn($v) => $v === 'absent')->count();
    }

    public function getJustifiedCountProperty(): int
    {
        return collect($this->attendance_status)->filter(fn($v) => $v === 'justified')->count();
    }

    public function setStatus(int $studentId, string $status): void
    {
        $this->attendance_status[$studentId] = $status;
    }

    public function initAttendance()
    {
        $this->attendance_status  = [];
        $this->attendance_remarks = [];
        foreach ($this->students as $student) {
            $existing = Attendance::where('student_id', $student->id)
                ->where('attendance_date', $this->attendance_date)
                ->where('competencia_id', $this->competencia_id ?: null)
                ->first();

            if ($existing) {
                $this->attendance_status[$student->id] = $existing->is_present
                    ? 'present'
                    : ($existing->is_justified ? 'justified' : 'absent');
            } else {
                $this->attendance_status[$student->id] = 'present';
            }
            $this->attendance_remarks[$student->id] = $existing?->remarks ?? '';
        }
    }

    // --- Agregar estudiante manualmente ---

    public function addStudent()
    {
        $this->validate([
            'new_student_name'  => 'required|min:2',
            'new_student_email' => 'nullable|email',
            'group_id'          => 'required|exists:groups,id',
        ], [
            'new_student_name.required' => 'El nombre del estudiante es obligatorio.',
            'new_student_name.min'      => 'El nombre debe tener al menos 2 caracteres.',
            'new_student_email.email'   => 'Ingresa un correo válido.',
        ]);

        Student::create([
            'group_id'   => $this->group_id,
            'name'       => trim($this->new_student_name),
            'email'      => $this->new_student_email      ? trim($this->new_student_email)      : null,
            'identifier' => $this->new_student_identifier ? trim($this->new_student_identifier) : null,
        ]);

        $this->reset(['new_student_name', 'new_student_email', 'new_student_identifier']);
        $this->loadStudents();
        session()->flash('message', 'Estudiante añadido correctamente.');
    }

    // --- Editar estudiante ---

    public function openEditStudent($id)
    {
        $student = Student::find($id);
        $this->editing_student_id         = $id;
        $this->editing_student_name       = $student->name;
        $this->editing_student_email      = $student->email      ?? '';
        $this->editing_student_identifier = $student->identifier ?? '';
        $this->showEditStudentModal       = true;
    }

    public function saveStudent()
    {
        $this->validate([
            'editing_student_name'  => 'required|min:2',
            'editing_student_email' => 'nullable|email',
        ], [
            'editing_student_name.required' => 'El nombre es obligatorio.',
            'editing_student_email.email'   => 'Ingresa un correo válido.',
        ]);

        Student::find($this->editing_student_id)->update([
            'name'       => trim($this->editing_student_name),
            'email'      => $this->editing_student_email      ? trim($this->editing_student_email)      : null,
            'identifier' => $this->editing_student_identifier ? trim($this->editing_student_identifier) : null,
        ]);

        $this->showEditStudentModal = false;
        $this->loadStudents();
    }

    // --- Eliminar estudiante ---

    public function deleteStudent($id)
    {
        Student::find($id)->delete();
        unset($this->attendance_status[$id]);
        unset($this->attendance_remarks[$id]);
        $this->students = collect($this->students)->reject(fn($s) => $s->id == $id)->values()->all();
    }

    // --- Marcar todos ---

    public function markAllPresent()
    {
        foreach ($this->students as $student) {
            $this->attendance_status[$student->id] = 'present';
        }
    }

    public function markAllAbsent()
    {
        foreach ($this->students as $student) {
            $this->attendance_status[$student->id] = 'absent';
        }
    }

    public function markAllJustified()
    {
        foreach ($this->students as $student) {
            $this->attendance_status[$student->id] = 'justified';
        }
    }

    // --- Importar desde Excel/CSV ---

    public function importStudents()
    {
        $this->validate([
            'group_id'     => 'required|exists:groups,id',
            'student_file' => 'required|mimes:xlsx,csv|max:1024',
        ], [
            'student_file.mimes' => 'El archivo debe ser .xlsx o .csv.',
            'group_id.required'  => 'Debes seleccionar un grupo antes de importar.',
        ]);

        $path     = $this->student_file->store('temp');
        $fullPath = Storage::path($path);

        try {
            $reader = SimpleExcelReader::create($fullPath);
            if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) === 'csv') {
                $reader->useDelimiter(',');
            }
            $rows = $reader->getRows();
        } catch (\Exception $e) {
            session()->flash('error', 'Error al leer el archivo: ' . $e->getMessage());
            return;
        }

        $count   = 0;
        $headers = [];

        $rows->each(function (array $row) use (&$count, &$headers) {
            if (empty($headers)) {
                $headers = array_keys($row);
            }

            $normalizedRow = [];
            foreach ($row as $key => $value) {
                $normalizedKey = strtolower(trim($key));
                $normalizedKey = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $normalizedKey);
                $normalizedKey = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $normalizedKey);
                $normalizedRow[$normalizedKey] = $value;
            }

            $name       = $normalizedRow['nombre'] ?? $normalizedRow['nombres'] ?? $normalizedRow['name']
                          ?? $normalizedRow['completo'] ?? $normalizedRow['student'] ?? null;
            $email      = $normalizedRow['email'] ?? $normalizedRow['correo'] ?? $normalizedRow['mail'] ?? null;
            $identifier = $normalizedRow['identificacion'] ?? $normalizedRow['cedula']
                          ?? $normalizedRow['documento'] ?? $normalizedRow['identifier']
                          ?? $normalizedRow['id'] ?? null;

            if ($name && trim((string)$name) !== '') {
                Student::create([
                    'group_id'   => $this->group_id,
                    'name'       => trim((string)$name),
                    'email'      => $email      ? trim((string)$email)      : null,
                    'identifier' => $identifier ? trim((string)$identifier) : null,
                ]);
                $count++;
            }
        });

        Storage::delete($path);
        $this->reset('student_file');
        $this->loadStudents();

        if ($count > 0) {
            session()->flash('message', "Se importaron {$count} estudiantes correctamente.");
        } else {
            $cols = !empty($headers) ? implode(', ', $headers) : 'ninguna (posible error de delimitador)';
            session()->flash('error', "No se pudo importar. Columnas detectadas: {$cols}");
        }
    }

    // --- Guardar asistencia ---

    public function saveAttendance()
    {
        $this->validate([
            'group_id'        => 'required',
            'attendance_date' => 'required|date',
        ]);

        $savedCount = 0;
        foreach ($this->attendance_status as $studentId => $status) {
            $isPresent   = $status === 'present';
            $isJustified = $status === 'justified';
            Attendance::updateOrCreate(
                [
                    'student_id'      => $studentId,
                    'attendance_date' => $this->attendance_date,
                    'competencia_id'  => $this->competencia_id ?: null,
                ],
                [
                    'is_present'   => $isPresent,
                    'is_justified' => $isJustified,
                    'remarks'      => $this->attendance_remarks[$studentId] ?? null,
                ]
            );
            $savedCount++;
        }

        if ($savedCount > 0) {
            session()->flash('message', "Asistencia guardada correctamente para {$savedCount} estudiantes.");
        } else {
            session()->flash('error', 'No hay datos de asistencia para guardar.');
        }

        $this->dispatch('attendance-saved');
    }
}; ?>

<div class="p-6 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Asistencia de Estudiantes</h1>

    @if(session()->has('message'))
        <div class="bg-green-50 border border-green-200 text-green-800 p-4 mb-6 rounded-xl text-sm">
            {{ session('message') }}
        </div>
    @endif
    @if(session()->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 p-4 mb-6 rounded-xl text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Selector de grupo -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-semibold mb-4 text-gray-800">Seleccionar Grupo</h2>
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Institución</label>
                    <select wire:model.live="institution_id"
                            class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Selecciona una institución</option>
                        @foreach($institutions as $inst)
                            <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($institution_id)
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Grupo</label>
                    <select wire:model.live="group_id"
                            class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Selecciona un grupo</option>
                        @foreach($groups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                @if($group_id)
                <div class="mt-1 p-3 bg-indigo-50 border border-indigo-100 rounded-lg text-indigo-800 text-xs">
                    <p class="font-semibold mb-1">Cómo usar:</p>
                    <ul class="list-disc ml-4 space-y-0.5">
                        <li>Cambia la <strong>fecha</strong> para ver asistencias de otros días.</li>
                        <li>Usa el interruptor para marcar <strong>Presente</strong> o <strong>Ausente</strong>.</li>
                        <li>Al marcar ausente aparece un campo de observaciones.</li>
                    </ul>
                </div>
                @endif
                @endif
            </div>
        </div>

        <!-- Gestionar estudiantes -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-semibold mb-4 text-gray-800">Gestionar Estudiantes</h2>
            @if($group_id)
                <div class="mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Añadir individualmente</p>
                    <div class="space-y-2">
                        <input type="text" wire:model="new_student_name" placeholder="Nombre completo *"
                               class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        @error('new_student_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        <div class="grid grid-cols-2 gap-2">
                            <input type="text" wire:model="new_student_identifier" placeholder="Identificación"
                                   class="border border-gray-300 p-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <input type="email" wire:model="new_student_email" placeholder="Correo (opcional)"
                                   class="border border-gray-300 p-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                        @error('new_student_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        <button wire:click="addStudent" wire:loading.attr="disabled"
                                class="w-full bg-green-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors disabled:opacity-50">
                            <span wire:loading wire:target="addStudent">Añadiendo...</span>
                            <span wire:loading.remove wire:target="addStudent">Añadir Estudiante</span>
                        </button>
                    </div>
                </div>
                <hr class="border-gray-100 my-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Importar desde archivo</p>
                    <input type="file" wire:model="student_file"
                           class="border border-gray-300 p-2 rounded-lg w-full text-sm text-gray-600">
                    <div wire:loading wire:target="student_file" class="text-blue-500 text-xs mt-1">Subiendo...</div>
                    @error('student_file') <span class="text-red-500 text-xs block mt-1">{{ $message }}</span> @enderror
                    <button wire:click="importStudents" wire:loading.attr="disabled"
                            class="mt-2 w-full bg-blue-600 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors disabled:opacity-50">
                        <span wire:loading wire:target="importStudents">Importando...</span>
                        <span wire:loading.remove wire:target="importStudents">Importar Excel / CSV</span>
                    </button>
                    <p class="text-xs text-gray-400 mt-1">Formatos: .xlsx, .csv. Columnas: nombre, email, identificacion</p>
                </div>
            @else
                <div class="flex items-center justify-center h-40 text-gray-400 text-sm italic">
                    Selecciona un grupo para gestionar estudiantes
                </div>
            @endif
        </div>
    </div>

    <!-- Tomar asistencia -->
    @if($group_id)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-6 py-4 border-b border-gray-100 space-y-4">
                {{-- Fila 1: fecha + competencia --}}
                <div class="flex flex-wrap items-end gap-4">
                    <h2 class="font-semibold text-gray-800 w-full sm:w-auto">Tomar Asistencia</h2>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">Fecha</label>
                        <input type="date" wire:model.live="attendance_date"
                               class="border border-gray-300 p-2 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div class="flex-1 min-w-48">
                        <label class="block text-xs font-medium text-gray-500 mb-0.5">
                            Competencia de esta sesión
                        </label>
                        @if(count($competencias) > 0)
                        <select wire:model.live="competencia_id"
                                class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                            <option value="">— Sin competencia —</option>
                            @foreach($competencias as $comp)
                                <option value="{{ $comp->id }}">
                                    {{ $comp->code ? '['.$comp->code.'] ' : '' }}{{ $comp->name }}
                                    {{ $comp->total_hours ? '('.$comp->total_hours.'h)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @else
                        <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
                            Esta ficha no tiene un Programa asignado. Configúralo en <a href="{{ route('horarios') }}" class="underline font-medium">Horarios</a>.
                        </p>
                        @endif
                    </div>
                    @if($group_id && count($students) > 0)
                    <a href="{{ route('imprimir.asistencia', ['group_id' => $group_id, 'date' => $attendance_date, 'competencia_id' => $competencia_id]) }}"
                       target="_blank"
                       class="text-xs px-3 py-2 border border-gray-300 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimir lista
                    </a>
                    @endif
                </div>

                {{-- Fila 2: contadores + botones marcar todos --}}
                @if(count($students) > 0)
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2 text-sm flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-green-100 text-green-800 rounded-full font-medium text-xs">
                            <span class="w-2 h-2 bg-green-500 rounded-full"></span>
                            {{ $this->presentCount }} presentes
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-red-100 text-red-800 rounded-full font-medium text-xs">
                            <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                            {{ $this->absentCount }} ausentes
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full font-medium text-xs">
                            <span class="w-2 h-2 bg-yellow-500 rounded-full"></span>
                            {{ $this->justifiedCount }} justificados
                        </span>
                        <span class="text-gray-400 text-xs">de {{ count($students) }}</span>
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="markAllPresent"
                                class="text-xs px-2.5 py-1.5 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            Todos presentes
                        </button>
                        <button wire:click="markAllAbsent"
                                class="text-xs px-2.5 py-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                            Todos ausentes
                        </button>
                        <button wire:click="markAllJustified"
                                class="text-xs px-2.5 py-1.5 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                            Todos justificados
                        </button>
                    </div>
                </div>
                @endif
            </div>

            @if(count($students) > 0)
            <div class="px-6 py-3 border-b border-gray-50">
                <input type="text" wire:model.live.debounce.300ms="search"
                       placeholder="Buscar por nombre o identificación..."
                       class="border border-gray-200 p-2 rounded-lg w-full sm:max-w-xs text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            @endif

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Identificación</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Nombre</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Estado</th>
                            @if($competencia_id)
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">% Inasist.</th>
                            @endif
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Observación</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($this->filteredStudents as $student)
                        @php $status = $attendance_status[$student->id] ?? 'present'; @endphp
                            <tr class="hover:bg-gray-50 transition-colors {{ $status === 'absent' ? 'bg-red-50/30' : ($status === 'justified' ? 'bg-yellow-50/30' : '') }}">
                                <td class="px-4 py-3 text-gray-500 text-xs">{{ $student->identifier ?: '—' }}</td>
                                <td class="px-4 py-3 font-medium text-gray-900 text-sm">{{ $student->name }}</td>
                                <td class="px-4 py-3">
                                    {{-- Botones 3 estados --}}
                                    <div class="inline-flex rounded-lg overflow-hidden border border-gray-200 text-xs">
                                        <button wire:click="setStatus({{ $student->id }}, 'present')"
                                                class="px-2.5 py-1.5 font-medium transition-colors {{ $status === 'present' ? 'bg-green-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">
                                            P
                                        </button>
                                        <button wire:click="setStatus({{ $student->id }}, 'absent')"
                                                class="px-2.5 py-1.5 font-medium border-l border-gray-200 transition-colors {{ $status === 'absent' ? 'bg-red-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">
                                            A
                                        </button>
                                        <button wire:click="setStatus({{ $student->id }}, 'justified')"
                                                class="px-2.5 py-1.5 font-medium border-l border-gray-200 transition-colors {{ $status === 'justified' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-500 hover:bg-gray-50' }}">
                                            J
                                        </button>
                                    </div>
                                    <span class="ml-1.5 text-xs {{ $status === 'present' ? 'text-green-600' : ($status === 'justified' ? 'text-yellow-600' : 'text-red-500') }}">
                                        {{ $status === 'present' ? 'Presente' : ($status === 'justified' ? 'Justificado' : 'Ausente') }}
                                    </span>
                                </td>
                                @if($competencia_id)
                                <td class="px-4 py-3 text-center">
                                    @php $risk = $student_risks[$student->id] ?? null; @endphp
                                    @if($risk && $risk['total'] > 0)
                                        @php $pct = $risk['pct']; @endphp
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                            {{ $pct >= 20 ? 'bg-red-100 text-red-800' : ($pct >= 15 ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800') }}">
                                            {{ $pct }}%
                                        </span>
                                        @if($pct >= 20)
                                        <span class="block text-xs text-red-600 font-medium mt-0.5">⚠ Superó 20%</span>
                                        @elseif($pct >= 15)
                                        <span class="block text-xs text-yellow-600 mt-0.5">En riesgo</span>
                                        @endif
                                    @else
                                        <span class="text-gray-300 text-xs">—</span>
                                    @endif
                                </td>
                                @endif
                                <td class="px-4 py-3">
                                    @if($status !== 'present')
                                        <input type="text"
                                               wire:model.live="attendance_remarks.{{ $student->id }}"
                                               placeholder="{{ $status === 'justified' ? 'Ej: Certificado médico...' : 'Motivo de ausencia...' }}"
                                               class="border border-gray-200 rounded-lg p-1.5 text-xs w-full focus:outline-none focus:ring-1 focus:ring-gray-300 min-w-32">
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div x-data="{ confirmDelete: false }" class="flex items-center gap-2">
                                        <button wire:click="openEditStudent({{ $student->id }})"
                                                class="text-blue-500 hover:text-blue-700 text-xs">Editar</button>
                                        <span x-show="!confirmDelete">
                                            <button @click="confirmDelete = true"
                                                    class="text-red-500 hover:text-red-700 text-xs">Eliminar</button>
                                        </span>
                                        <span x-show="confirmDelete" x-cloak class="inline-flex items-center gap-1 text-xs">
                                            <button wire:click="deleteStudent({{ $student->id }})"
                                                    @click="confirmDelete = false"
                                                    class="bg-red-600 text-white px-2 py-0.5 rounded hover:bg-red-700">Sí</button>
                                            <button @click="confirmDelete = false"
                                                    class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded hover:bg-gray-300">No</button>
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $competencia_id ? 6 : 5 }}" class="px-5 py-8 text-center text-gray-400 italic">
                                    @if($search)
                                        No hay estudiantes que coincidan con "{{ $search }}"
                                    @else
                                        No hay estudiantes en este grupo. Añade o importa estudiantes.
                                    @endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(count($students) > 0)
            <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap gap-3">
                <button wire:click="saveAttendance" wire:loading.attr="disabled"
                        class="flex-1 sm:flex-none bg-indigo-600 text-white font-semibold py-2.5 px-8 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                    <span wire:loading wire:target="saveAttendance">Guardando...</span>
                    <span wire:loading.remove wire:target="saveAttendance">Guardar Asistencia</span>
                </button>
                <button wire:click="initAttendance"
                        class="bg-gray-100 text-gray-700 font-medium py-2.5 px-6 rounded-lg hover:bg-gray-200 transition-colors">
                    Restablecer
                </button>
            </div>
            @endif
        </div>
    @endif

    <!-- Modal: Editar estudiante -->
    <div x-data x-show="$wire.showEditStudentModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-800">Editar Estudiante</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo *</label>
                    <input wire:model="editing_student_name" type="text"
                           class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_student_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Identificación</label>
                    <input wire:model="editing_student_identifier" type="text"
                           class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                    <input wire:model="editing_student_email" type="email"
                           class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_student_email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end">
                <button wire:click="$set('showEditStudentModal', false)"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="saveStudent" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                    <span wire:loading wire:target="saveStudent">Guardando...</span>
                    <span wire:loading.remove wire:target="saveStudent">Guardar cambios</span>
                </button>
            </div>
        </div>
    </div>
</div>
