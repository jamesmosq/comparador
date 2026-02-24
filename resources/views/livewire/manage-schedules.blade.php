<?php

use Livewire\Volt\Component;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\ProgramaFormacion;

new class extends Component
{
    public $institutions;
    public $programas = [];
    public $groups = [];
    public $schedules = [];

    public $institution_id;
    public $group_id;

    // Crear institución
    public string $new_institution_name    = '';
    public string $new_institution_address = '';

    // Crear grupo
    public string $new_group_name     = '';
    public string $new_group_ficha    = '';
    public $new_group_programa_id     = '';
    public string $new_group_day      = '';
    public string $new_group_start    = '';
    public string $new_group_end      = '';

    // Crear horario (materia)
    public string $new_subject = '';
    public string $new_day     = '';
    public string $new_start   = '';
    public string $new_end     = '';

    // Editar institución (modal)
    public $editing_institution_id      = null;
    public string $editing_institution_name    = '';
    public string $editing_institution_address = '';
    public bool $showEditInstitutionModal       = false;

    // Editar grupo (modal)
    public $editing_group_id          = null;
    public string $editing_group_name  = '';
    public string $editing_group_ficha = '';
    public $editing_group_programa_id  = '';
    public string $editing_group_day   = '';
    public string $editing_group_start = '';
    public string $editing_group_end   = '';
    public bool $showEditGroupModal     = false;

    // Editar horario (modal)
    public $editing_schedule_id  = null;
    public string $editing_subject = '';
    public string $editing_day     = '';
    public string $editing_start   = '';
    public string $editing_end     = '';
    public bool $showEditScheduleModal = false;

    public function mount()
    {
        $this->loadInstitutions();
        $this->programas = ProgramaFormacion::visibleTo(auth()->user())->orderBy('name')->get();
    }

    public function loadInstitutions()
    {
        $this->institutions = Institution::visibleTo(auth()->user())->get();
    }

    public function updatedInstitutionId($value)
    {
        $this->groups   = Group::where('institution_id', $value)->get();
        $this->group_id = null;
        $this->schedules = [];
    }

    public function updatedGroupId($value)
    {
        if ($value) {
            $this->schedules = Schedule::where('group_id', $value)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
        } else {
            $this->schedules = [];
        }
    }

    public function getDayName(string $day): string
    {
        return match($day) {
            'Monday'    => 'Lunes',
            'Tuesday'   => 'Martes',
            'Wednesday' => 'Miércoles',
            'Thursday'  => 'Jueves',
            'Friday'    => 'Viernes',
            'Saturday'  => 'Sábado',
            'Sunday'    => 'Domingo',
            default     => $day,
        };
    }

    // ── INSTITUCIONES ────────────────────────────────────────────────────

    public function addInstitution()
    {
        $this->validate([
            'new_institution_name' => 'required|min:3',
        ]);

        Institution::create([
            'name'    => trim($this->new_institution_name),
            'address' => trim($this->new_institution_address) ?: null,
            'user_id' => auth()->id(),
        ]);

        $this->reset('new_institution_name', 'new_institution_address');
        $this->loadInstitutions();
        session()->flash('flash_inst', 'Institución añadida correctamente.');
    }

    public function openEditInstitution($id)
    {
        $inst = Institution::visibleTo(auth()->user())->findOrFail($id);
        $this->editing_institution_id      = $id;
        $this->editing_institution_name    = $inst->name;
        $this->editing_institution_address = $inst->address ?? '';
        $this->showEditInstitutionModal    = true;
    }

    public function saveInstitution()
    {
        $this->validate(['editing_institution_name' => 'required|min:3']);

        Institution::visibleTo(auth()->user())->findOrFail($this->editing_institution_id)->update([
            'name'    => trim($this->editing_institution_name),
            'address' => trim($this->editing_institution_address) ?: null,
        ]);

        $this->showEditInstitutionModal = false;
        $this->loadInstitutions();
    }

    public function deleteInstitution($id)
    {
        Institution::visibleTo(auth()->user())->findOrFail($id)->delete();
        if ($this->institution_id == $id) {
            $this->institution_id = null;
            $this->groups    = [];
            $this->group_id  = null;
            $this->schedules = [];
        }
        $this->loadInstitutions();
    }

    // ── GRUPOS ───────────────────────────────────────────────────────────

    public function addGroup()
    {
        $this->validate([
            'institution_id' => 'required|exists:institutions,id',
            'new_group_name' => 'required|min:2',
            'new_group_end'  => 'nullable|after:new_group_start',
        ], [
            'new_group_end.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        Group::create([
            'institution_id'      => $this->institution_id,
            'name'                => trim($this->new_group_name),
            'ficha_number'        => trim($this->new_group_ficha) ?: null,
            'programa_formacion_id' => $this->new_group_programa_id ?: null,
            'day_of_week'         => $this->new_group_day   ?: null,
            'start_time'          => $this->new_group_start ?: null,
            'end_time'            => $this->new_group_end   ?: null,
        ]);

        $this->reset('new_group_name', 'new_group_ficha', 'new_group_programa_id', 'new_group_day', 'new_group_start', 'new_group_end');
        $this->updatedInstitutionId($this->institution_id);
    }

    public function openEditGroup($id)
    {
        $grp = Group::find($id);
        $this->editing_group_id         = $id;
        $this->editing_group_name       = $grp->name;
        $this->editing_group_ficha      = $grp->ficha_number ?? '';
        $this->editing_group_programa_id = $grp->programa_formacion_id ?? '';
        $this->editing_group_day        = $grp->day_of_week ?? '';
        $this->editing_group_start      = $grp->start_time  ?? '';
        $this->editing_group_end        = $grp->end_time    ?? '';
        $this->showEditGroupModal       = true;
    }

    public function saveGroup()
    {
        $this->validate([
            'editing_group_name' => 'required|min:2',
            'editing_group_end'  => 'nullable|after:editing_group_start',
        ], [
            'editing_group_end.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        Group::find($this->editing_group_id)->update([
            'name'                  => trim($this->editing_group_name),
            'ficha_number'          => trim($this->editing_group_ficha) ?: null,
            'programa_formacion_id' => $this->editing_group_programa_id ?: null,
            'day_of_week'           => $this->editing_group_day   ?: null,
            'start_time'            => $this->editing_group_start ?: null,
            'end_time'              => $this->editing_group_end   ?: null,
        ]);

        $this->showEditGroupModal = false;
        $this->updatedInstitutionId($this->institution_id);
    }

    public function deleteGroup($id)
    {
        Group::find($id)->delete();
        if ($this->group_id == $id) {
            $this->group_id  = null;
            $this->schedules = [];
        }
        $this->updatedInstitutionId($this->institution_id);
    }

    // ── HORARIOS (MATERIAS) ───────────────────────────────────────────────

    public function addSchedule()
    {
        $this->validate([
            'group_id'    => 'required|exists:groups,id',
            'new_subject' => 'required',
            'new_day'     => 'required',
            'new_start'   => 'required',
            'new_end'     => 'required|after:new_start',
        ], [
            'new_end.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        Schedule::create([
            'group_id'    => $this->group_id,
            'subject'     => $this->new_subject,
            'day_of_week' => $this->new_day,
            'start_time'  => $this->new_start,
            'end_time'    => $this->new_end,
        ]);

        $this->reset('new_subject', 'new_day', 'new_start', 'new_end');
        $this->updatedGroupId($this->group_id);
    }

    public function openEditSchedule($id)
    {
        $schedule = Schedule::find($id);
        $this->editing_schedule_id   = $id;
        $this->editing_subject       = $schedule->subject;
        $this->editing_day           = $schedule->day_of_week;
        $this->editing_start         = $schedule->start_time;
        $this->editing_end           = $schedule->end_time;
        $this->showEditScheduleModal = true;
    }

    public function saveSchedule()
    {
        $this->validate([
            'editing_subject' => 'required',
            'editing_day'     => 'required',
            'editing_start'   => 'required',
            'editing_end'     => 'required|after:editing_start',
        ], [
            'editing_end.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
        ]);

        Schedule::find($this->editing_schedule_id)->update([
            'subject'     => $this->editing_subject,
            'day_of_week' => $this->editing_day,
            'start_time'  => $this->editing_start,
            'end_time'    => $this->editing_end,
        ]);

        $this->showEditScheduleModal = false;
        $this->updatedGroupId($this->group_id);
    }

    public function deleteSchedule($id)
    {
        Schedule::find($id)->delete();
        $this->updatedGroupId($this->group_id);
    }
};
?>

<div class="p-6 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Horarios de Clases</h1>

    @if(session()->has('flash_inst'))
        <div class="bg-green-50 border border-green-200 text-green-800 p-3 mb-4 rounded-lg text-sm">
            {{ session('flash_inst') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">

        {{-- ── INSTITUCIONES ─────────────────────────────── --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-semibold mb-4 text-gray-800">Instituciones Educativas</h2>

            {{-- Formulario nueva institución --}}
            <div class="space-y-2 mb-4">
                <div>
                    <label class="block text-xs text-gray-500 mb-0.5">Nombre *</label>
                    <input type="text" wire:model="new_institution_name"
                           placeholder="Ej: Colegio Nacional"
                           class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('new_institution_name')
                        <span class="text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-0.5">Dirección / Barrio</label>
                    <input type="text" wire:model="new_institution_address"
                           placeholder="Ej: Calle 10 #5-20, Barrio Centro"
                           class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <button wire:click="addInstitution" wire:loading.attr="disabled"
                        class="w-full bg-blue-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-blue-600 transition-colors disabled:opacity-50">
                    <span wire:loading wire:target="addInstitution">Guardando...</span>
                    <span wire:loading.remove wire:target="addInstitution">Añadir Institución</span>
                </button>
            </div>

            {{-- Lista de instituciones --}}
            <div class="space-y-2 mb-4 max-h-52 overflow-y-auto">
                @forelse($institutions as $inst)
                    <div x-data="{ confirmDelete: false }"
                         class="border border-gray-100 rounded-lg px-3 py-2 text-sm bg-gray-50">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 truncate">{{ $inst->name }}</p>
                                @if($inst->address)
                                    <p class="text-xs text-gray-400 truncate mt-0.5">
                                        <svg class="inline w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                        {{ $inst->address }}
                                    </p>
                                @endif
                            </div>
                            <div class="flex gap-2 flex-shrink-0">
                                <button wire:click="openEditInstitution({{ $inst->id }})"
                                        class="text-xs text-blue-500 hover:text-blue-700">Editar</button>
                                <button @click="confirmDelete = true"
                                        class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                            </div>
                        </div>
                        <div x-show="confirmDelete" x-cloak
                             class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                            <p class="mb-2">¿Eliminar "{{ $inst->name }}"? Se perderán todos sus grupos y horarios.</p>
                            <div class="flex gap-2">
                                <button wire:click="deleteInstitution({{ $inst->id }})"
                                        @click="confirmDelete = false"
                                        class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Sí, eliminar</button>
                                <button @click="confirmDelete = false"
                                        class="bg-gray-200 text-gray-600 px-3 py-1 rounded hover:bg-gray-300">Cancelar</button>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-gray-400 text-sm italic text-center py-3">No hay instituciones registradas</p>
                @endforelse
            </div>

            {{-- Selector activo --}}
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Institución activa</label>
                <select wire:model.live="institution_id"
                        class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <option value="">-- Selecciona una institución --</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- ── GRUPOS ────────────────────────────────────── --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
            <h2 class="font-semibold mb-4 text-gray-800">Grupos</h2>
            @if($institution_id)
                {{-- Formulario nuevo grupo --}}
                <div class="space-y-2 mb-4">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Nombre del grupo *</label>
                            <input type="text" wire:model="new_group_name"
                                   placeholder="Ej: ADSO 2026"
                                   class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            @error('new_group_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">N° de Ficha</label>
                            <input type="text" wire:model="new_group_ficha" placeholder="Ej: 2775501"
                                   class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Programa de Formación</label>
                        <select wire:model="new_group_programa_id"
                                class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">Sin programa asignado</option>
                            @foreach($programas as $prog)
                                <option value="{{ $prog->id }}">{{ $prog->name }}{{ $prog->code ? ' ('.$prog->code.')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-0.5">Día de clase del grupo</label>
                        <select wire:model="new_group_day"
                                class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            <option value="">Sin día asignado</option>
                            <option value="Monday">Lunes</option>
                            <option value="Tuesday">Martes</option>
                            <option value="Wednesday">Miércoles</option>
                            <option value="Thursday">Jueves</option>
                            <option value="Friday">Viernes</option>
                            <option value="Saturday">Sábado</option>
                            <option value="Sunday">Domingo</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Hora entrada</label>
                            <input type="time" wire:model="new_group_start"
                                   class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-0.5">Hora salida</label>
                            <input type="time" wire:model="new_group_end"
                                   class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                            @error('new_group_end')
                                <span class="text-red-500 text-xs">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <button wire:click="addGroup" wire:loading.attr="disabled"
                            class="w-full bg-green-500 text-white py-2 rounded-lg text-sm font-medium hover:bg-green-600 transition-colors disabled:opacity-50">
                        <span wire:loading wire:target="addGroup">Guardando...</span>
                        <span wire:loading.remove wire:target="addGroup">Añadir Grupo</span>
                    </button>
                </div>

                {{-- Lista de grupos --}}
                <div class="space-y-2 mb-4 max-h-48 overflow-y-auto">
                    @forelse($groups as $grp)
                        <div x-data="{ confirmDelete: false }"
                             class="border border-gray-100 rounded-lg px-3 py-2 text-sm bg-gray-50">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-gray-800">{{ $grp->name }}
                                        @if($grp->ficha_number)
                                            <span class="text-xs text-gray-400 font-normal ml-1">Ficha {{ $grp->ficha_number }}</span>
                                        @endif
                                    </p>
                                    @if($grp->programa_formacion_id)
                                        @php $prog = collect($programas)->firstWhere('id', $grp->programa_formacion_id); @endphp
                                        @if($prog)
                                        <p class="text-xs text-indigo-600 mt-0.5 truncate">{{ $prog->name }}</p>
                                        @endif
                                    @endif
                                    @if($grp->day_of_week || $grp->start_time)
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @if($grp->day_of_week)
                                                <span class="inline-flex items-center px-1.5 py-0.5 bg-green-100 text-green-700 text-xs rounded-md font-medium">
                                                    {{ $this->getDayName($grp->day_of_week) }}
                                                </span>
                                            @endif
                                            @if($grp->start_time && $grp->end_time)
                                                <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-md font-medium">
                                                    {{ substr($grp->start_time, 0, 5) }} – {{ substr($grp->end_time, 0, 5) }}
                                                </span>
                                            @elseif($grp->start_time)
                                                <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-md font-medium">
                                                    desde {{ substr($grp->start_time, 0, 5) }}
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="flex gap-2 flex-shrink-0">
                                    <button wire:click="openEditGroup({{ $grp->id }})"
                                            class="text-xs text-blue-500 hover:text-blue-700">Editar</button>
                                    <button @click="confirmDelete = true"
                                            class="text-xs text-red-500 hover:text-red-700">Eliminar</button>
                                </div>
                            </div>
                            <div x-show="confirmDelete" x-cloak
                                 class="mt-2 p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700">
                                <p class="mb-2">¿Eliminar "{{ $grp->name }}"? Se perderán sus horarios y estudiantes.</p>
                                <div class="flex gap-2">
                                    <button wire:click="deleteGroup({{ $grp->id }})"
                                            @click="confirmDelete = false"
                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700">Sí, eliminar</button>
                                    <button @click="confirmDelete = false"
                                            class="bg-gray-200 text-gray-600 px-3 py-1 rounded hover:bg-gray-300">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 text-sm italic text-center py-3">No hay grupos en esta institución</p>
                    @endforelse
                </div>

                {{-- Selector activo --}}
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Grupo activo</label>
                    <select wire:model.live="group_id"
                            class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">-- Selecciona un grupo --</option>
                        @foreach($groups as $grp)
                            <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="flex items-center justify-center h-48 text-gray-400 text-sm italic">
                    Selecciona una institución primero
                </div>
            @endif
        </div>
    </div>

    {{-- ── HORARIOS DEL GRUPO ────────────────────────────── --}}
    @if($group_id)
        @php $activeGroup = collect($groups)->firstWhere('id', (int)$group_id); @endphp
        @if($activeGroup && ($activeGroup->day_of_week || $activeGroup->start_time))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800 flex flex-wrap items-center gap-3">
                <span class="font-semibold">Grupo: {{ $activeGroup->name }}</span>
                @if($activeGroup->day_of_week)
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Día de clase: <strong>{{ $this->getDayName($activeGroup->day_of_week) }}</strong>
                    </span>
                @endif
                @if($activeGroup->start_time && $activeGroup->end_time)
                    <span class="inline-flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Franja horaria: <strong>{{ substr($activeGroup->start_time, 0, 5) }} – {{ substr($activeGroup->end_time, 0, 5) }}</strong>
                    </span>
                @endif
            </div>
        @endif

        {{-- Formulario añadir horario / materia --}}
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 mb-6">
            <h2 class="font-semibold mb-1 text-gray-800">Añadir Materia al Horario</h2>
            <p class="text-xs text-gray-400 mb-4">Aquí puedes registrar cada materia/clase con su propio día y horario específico.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div class="lg:col-span-2">
                    <label class="block text-xs text-gray-500 mb-1">Materia / Clase *</label>
                    <input type="text" wire:model="new_subject" placeholder="Ej: Matemáticas"
                           class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('new_subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Día *</label>
                    <select wire:model="new_day"
                            class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Selecciona</option>
                        <option value="Monday">Lunes</option>
                        <option value="Tuesday">Martes</option>
                        <option value="Wednesday">Miércoles</option>
                        <option value="Thursday">Jueves</option>
                        <option value="Friday">Viernes</option>
                        <option value="Saturday">Sábado</option>
                        <option value="Sunday">Domingo</option>
                    </select>
                    @error('new_day') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Hora inicio *</label>
                    <input type="time" wire:model="new_start"
                           class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('new_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Hora fin *</label>
                    <input type="time" wire:model="new_end"
                           class="border border-gray-300 p-2 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('new_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>
            <button wire:click="addSchedule" wire:loading.attr="disabled"
                    class="mt-4 bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50">
                <span wire:loading wire:target="addSchedule">Guardando...</span>
                <span wire:loading.remove wire:target="addSchedule">Añadir al Horario</span>
            </button>
        </div>

        {{-- Tabla de horarios --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Materias del Grupo</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Día</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Franja Horaria</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Materia</th>
                            <th class="px-5 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($schedules as $sched)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded-md font-medium">
                                        {{ $this->getDayName($sched->day_of_week) }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-gray-600 whitespace-nowrap font-medium">
                                    {{ substr($sched->start_time, 0, 5) }} – {{ substr($sched->end_time, 0, 5) }}
                                </td>
                                <td class="px-5 py-3 text-gray-800">{{ $sched->subject }}</td>
                                <td class="px-5 py-3">
                                    <div x-data="{ confirmDelete: false }" class="flex items-center gap-3">
                                        <button wire:click="openEditSchedule({{ $sched->id }})"
                                                class="text-blue-500 hover:text-blue-700 text-sm">Editar</button>
                                        <span x-show="!confirmDelete">
                                            <button @click="confirmDelete = true"
                                                    class="text-red-500 hover:text-red-700 text-sm">Eliminar</button>
                                        </span>
                                        <span x-show="confirmDelete" x-cloak class="inline-flex items-center gap-1 text-xs">
                                            <span class="text-gray-500">¿Confirmar?</span>
                                            <button wire:click="deleteSchedule({{ $sched->id }})"
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
                                <td colspan="4" class="px-5 py-8 text-center text-gray-400 italic">
                                    No hay materias programadas para este grupo
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── MODAL: Editar institución ─────────────────────── --}}
    <div x-data x-show="$wire.showEditInstitutionModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-800">Editar Institución</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                    <input wire:model="editing_institution_name" type="text"
                           class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    @error('editing_institution_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dirección / Barrio</label>
                    <input wire:model="editing_institution_address" type="text"
                           placeholder="Ej: Calle 10 #5-20, Barrio Centro"
                           class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end">
                <button wire:click="$set('showEditInstitutionModal', false)"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="saveInstitution" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50">
                    <span wire:loading wire:target="saveInstitution">Guardando...</span>
                    <span wire:loading.remove wire:target="saveInstitution">Guardar cambios</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Editar grupo ───────────────────────────── --}}
    <div x-data x-show="$wire.showEditGroupModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-800">Editar Grupo</h3>
            </div>
            <div class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nombre del grupo *</label>
                        <input wire:model="editing_group_name" type="text"
                               class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        @error('editing_group_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">N° de Ficha</label>
                        <input wire:model="editing_group_ficha" type="text" placeholder="Ej: 2775501"
                               class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Programa de Formación</label>
                    <select wire:model="editing_group_programa_id"
                            class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Sin programa asignado</option>
                        @foreach($programas as $prog)
                            <option value="{{ $prog->id }}">{{ $prog->name }}{{ $prog->code ? ' ('.$prog->code.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de clase del grupo</label>
                    <select wire:model="editing_group_day"
                            class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        <option value="">Sin día asignado</option>
                        <option value="Monday">Lunes</option>
                        <option value="Tuesday">Martes</option>
                        <option value="Wednesday">Miércoles</option>
                        <option value="Thursday">Jueves</option>
                        <option value="Friday">Viernes</option>
                        <option value="Saturday">Sábado</option>
                        <option value="Sunday">Domingo</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora entrada</label>
                        <input wire:model="editing_group_start" type="time"
                               class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora salida</label>
                        <input wire:model="editing_group_end" type="time"
                               class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
                        @error('editing_group_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end">
                <button wire:click="$set('showEditGroupModal', false)"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="saveGroup" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50">
                    <span wire:loading wire:target="saveGroup">Guardando...</span>
                    <span wire:loading.remove wire:target="saveGroup">Guardar cambios</span>
                </button>
            </div>
        </div>
    </div>

    {{-- ── MODAL: Editar horario/materia ────────────────── --}}
    <div x-data x-show="$wire.showEditScheduleModal" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-lg" @click.stop>
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-bold text-lg text-gray-800">Editar Materia</h3>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Materia / Clase *</label>
                    <input wire:model="editing_subject" type="text"
                           class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    @error('editing_subject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Día de la semana *</label>
                    <select wire:model="editing_day"
                            class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        <option value="">Selecciona un día</option>
                        <option value="Monday">Lunes</option>
                        <option value="Tuesday">Martes</option>
                        <option value="Wednesday">Miércoles</option>
                        <option value="Thursday">Jueves</option>
                        <option value="Friday">Viernes</option>
                        <option value="Saturday">Sábado</option>
                        <option value="Sunday">Domingo</option>
                    </select>
                    @error('editing_day') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora inicio *</label>
                        <input wire:model="editing_start" type="time"
                               class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('editing_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Hora fin *</label>
                        <input wire:model="editing_end" type="time"
                               class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                        @error('editing_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-100 flex gap-3 justify-end">
                <button wire:click="$set('showEditScheduleModal', false)"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                    Cancelar
                </button>
                <button wire:click="saveSchedule" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50">
                    <span wire:loading wire:target="saveSchedule">Guardando...</span>
                    <span wire:loading.remove wire:target="saveSchedule">Guardar cambios</span>
                </button>
            </div>
        </div>
    </div>
</div>
