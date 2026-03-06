<?php

use Livewire\Volt\Component;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Competencia;
use App\Models\ResultadoAprendizaje;
use App\Models\Calificacion;

new class extends Component {

    public $institutions           = [];
    public $groups                 = [];
    public $competencias           = [];
    public $resultadosAprendizaje  = [];
    public $students               = [];

    public $institution_id  = null;
    public $group_id        = null;
    public $competencia_id  = null;

    public array $notas        = [];
    public array $observaciones = [];

    public bool    $saved        = false;
    public ?string $errorMessage = null;

    public function mount(): void
    {
        $this->institutions = Institution::visibleTo(auth()->user())->orderBy('name')->get();
    }

    public function updatedInstitutionId(): void
    {
        $this->group_id            = null;
        $this->competencia_id      = null;
        $this->groups              = collect();
        $this->competencias        = collect();
        $this->students            = collect();
        $this->resultadosAprendizaje = collect();
        $this->notas               = [];
        $this->observaciones       = [];
        $this->saved               = false;
        $this->errorMessage        = null;

        if ($this->institution_id) {
            $this->groups = Group::where('institution_id', $this->institution_id)
                ->orderBy('name')
                ->get();
        }
    }

    public function updatedGroupId(): void
    {
        $this->competencia_id        = null;
        $this->competencias          = collect();
        $this->resultadosAprendizaje = collect();
        $this->notas                 = [];
        $this->observaciones         = [];
        $this->saved                 = false;
        $this->errorMessage          = null;

        if ($this->group_id) {
            $group = Group::with('programaFormacion.competencias')->find($this->group_id);
            $this->competencias = $group?->programaFormacion?->competencias ?? collect();
            $this->students     = Student::where('group_id', $this->group_id)->orderBy('name')->get();
        }
    }

    public function updatedCompetenciaId(): void
    {
        $this->resultadosAprendizaje = collect();
        $this->notas                 = [];
        $this->observaciones         = [];
        $this->saved                 = false;
        $this->errorMessage          = null;

        if ($this->competencia_id) {
            $this->resultadosAprendizaje = ResultadoAprendizaje::where('competencia_id', $this->competencia_id)
                ->orderBy('order')
                ->get();

            $this->cargarCalificaciones();
        }
    }

    private function cargarCalificaciones(): void
    {
        if (
            ! $this->group_id ||
            ! $this->competencia_id ||
            $this->resultadosAprendizaje->isEmpty() ||
            $this->students->isEmpty()
        ) {
            return;
        }

        $raIds      = $this->resultadosAprendizaje->pluck('id');
        $studentIds = $this->students->pluck('id');

        $calificaciones = Calificacion::whereIn('student_id', $studentIds)
            ->whereIn('resultado_aprendizaje_id', $raIds)
            ->where('group_id', $this->group_id)
            ->get();

        foreach ($calificaciones as $cal) {
            $key = $cal->student_id . '_' . $cal->resultado_aprendizaje_id;
            $this->notas[$key] = $cal->nota !== null ? number_format($cal->nota, 1) : null;

            if (! isset($this->observaciones[$cal->student_id]) && $cal->observacion) {
                $this->observaciones[$cal->student_id] = $cal->observacion;
            }
        }
    }

    public function saveNotasData(array $notas, array $observaciones): void
    {
        $this->notas        = $notas;
        $this->observaciones = $observaciones;
        $this->guardar();
    }

    public function guardar(): void
    {
        $this->saved        = false;
        $this->errorMessage = null;

        if (! $this->group_id || ! $this->competencia_id) {
            $this->errorMessage = 'Seleccione grupo y competencia antes de guardar.';
            return;
        }

        // Validar rango de notas
        foreach ($this->notas as $nota) {
            if ($nota !== null && $nota !== '') {
                $notaFloat = (float) $nota;
                if ($notaFloat < 1.0 || $notaFloat > 5.0) {
                    $this->errorMessage = 'Las notas deben estar entre 1.0 y 5.0.';
                    return;
                }
            }
        }

        foreach ($this->resultadosAprendizaje as $ra) {
            foreach ($this->students as $student) {
                $key  = $student->id . '_' . $ra->id;
                $nota = $this->notas[$key] ?? null;
                $obs  = $this->observaciones[$student->id] ?? null;

                if ($nota !== null && $nota !== '') {
                    Calificacion::updateOrCreate(
                        [
                            'student_id'               => $student->id,
                            'resultado_aprendizaje_id' => $ra->id,
                            'group_id'                 => $this->group_id,
                        ],
                        [
                            'nota'        => (float) $nota,
                            'observacion' => $obs ?: null,
                            'user_id'     => auth()->id(),
                        ]
                    );
                }
            }
        }

        $this->saved = true;
    }
}; ?>

<div class="max-w-7xl mx-auto px-4 pb-12">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Registro de Calificaciones</h1>
        <p class="text-sm text-gray-500 mt-1">Registra las notas de los aprendices por Resultado de Aprendizaje (escala 1.0 – 5.0).</p>
    </div>

    {{-- Filtros de selección --}}
    <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Institución</label>
                <select wire:model.live="institution_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">— Seleccionar —</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Grupo / Ficha</label>
                <select wire:model.live="group_id"
                        @disabled(!$institution_id)
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">— Seleccionar —</option>
                    @foreach($groups as $group)
                        <option value="{{ $group->id }}">
                            {{ $group->name }}{{ $group->ficha_number ? ' (' . $group->ficha_number . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Competencia</label>
                <select wire:model.live="competencia_id"
                        @disabled(!$group_id)
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-100 disabled:text-gray-400">
                    <option value="">— Seleccionar —</option>
                    @foreach($competencias as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->code ? '[' . $comp->code . '] ' : '' }}{{ $comp->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    {{-- Alertas --}}
    @if($saved)
        <div class="mb-4 flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            Calificaciones guardadas correctamente.
        </div>
    @endif

    @if($errorMessage)
        <div class="mb-4 flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Tabla de calificaciones --}}
    @if($competencia_id && count($resultadosAprendizaje) > 0 && count($students) > 0)

        {{-- Alpine gestiona el estado local de los inputs para evitar requests por cada tecla --}}
        <div x-data="{
                notas: @json($notas),
                observaciones: @json($observaciones),
                equivalencia(nota) {
                    const n = parseFloat(nota);
                    if (isNaN(n)) return null;
                    return n >= 3.0 ? 'APROBADO' : 'NO APROBADO';
                },
                promedioFor(studentId, raIds) {
                    const vals = raIds
                        .map(raId => parseFloat(this.notas[studentId + '_' + raId]))
                        .filter(v => !isNaN(v));
                    if (!vals.length) return null;
                    return (vals.reduce((a, b) => a + b, 0) / vals.length).toFixed(1);
                },
                save() {
                    $wire.saveNotasData(this.notas, this.observaciones);
                }
            }">

            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-4">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-600 whitespace-nowrap sticky left-0 bg-gray-50 z-10 min-w-[200px] border-r border-gray-200">
                                    Aprendiz
                                </th>
                                @foreach($resultadosAprendizaje as $ra)
                                    <th class="px-3 py-3 text-center font-semibold text-gray-600 min-w-[170px] border-r border-gray-100">
                                        @if($ra->code)
                                            <span class="block text-xs text-indigo-600 font-bold mb-1">{{ $ra->code }}</span>
                                        @endif
                                        <span class="block text-xs leading-tight text-gray-500 font-normal">
                                            {{ mb_strlen($ra->name) > 55 ? mb_substr($ra->name, 0, 55) . '…' : $ra->name }}
                                        </span>
                                    </th>
                                @endforeach
                                <th class="px-3 py-3 text-center font-semibold text-gray-600 min-w-[80px]">Promedio</th>
                                <th class="px-3 py-3 text-center font-semibold text-gray-600 min-w-[150px]">Observación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($students as $student)
                                @php
                                    $raIds = $resultadosAprendizaje->pluck('id')->all();
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    {{-- Nombre del aprendiz --}}
                                    <td class="px-4 py-3 sticky left-0 bg-white border-r border-gray-200 z-10">
                                        <span class="font-medium text-gray-800">{{ $student->name }}</span>
                                        @if($student->identifier)
                                            <span class="block text-xs text-gray-400">{{ $student->identifier }}</span>
                                        @endif
                                    </td>

                                    {{-- Celdas de nota por RA --}}
                                    @foreach($resultadosAprendizaje as $ra)
                                        @php $notaKey = $student->id . '_' . $ra->id; @endphp
                                        <td class="px-3 py-2 text-center border-r border-gray-100">
                                            <input
                                                type="number"
                                                min="1.0" max="5.0" step="0.1"
                                                placeholder="—"
                                                :value="notas['{{ $notaKey }}'] ?? ''"
                                                @input="notas['{{ $notaKey }}'] = $event.target.value !== '' ? parseFloat($event.target.value) : null"
                                                class="w-20 text-center border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                            >
                                            <div class="mt-1 min-h-[18px]">
                                                <template x-if="notas['{{ $notaKey }}'] !== null && notas['{{ $notaKey }}'] !== '' && !isNaN(parseFloat(notas['{{ $notaKey }}']))">
                                                    <span
                                                        :class="parseFloat(notas['{{ $notaKey }}']) >= 3.0
                                                            ? 'bg-green-100 text-green-700'
                                                            : 'bg-red-100 text-red-700'"
                                                        class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold"
                                                        x-text="parseFloat(notas['{{ $notaKey }}']) >= 3.0 ? 'APROBADO' : 'NO APROBADO'"
                                                    ></span>
                                                </template>
                                            </div>
                                        </td>
                                    @endforeach

                                    {{-- Promedio --}}
                                    <td class="px-3 py-2 text-center">
                                        <template x-if="promedioFor('{{ $student->id }}', @json($raIds)) !== null">
                                            <span
                                                :class="parseFloat(promedioFor('{{ $student->id }}', @json($raIds))) >= 3.0
                                                    ? 'text-green-700'
                                                    : 'text-red-700'"
                                                class="font-bold text-sm"
                                                x-text="promedioFor('{{ $student->id }}', @json($raIds))"
                                            ></span>
                                        </template>
                                        <template x-if="promedioFor('{{ $student->id }}', @json($raIds)) === null">
                                            <span class="text-gray-300 text-sm">—</span>
                                        </template>
                                    </td>

                                    {{-- Observación --}}
                                    <td class="px-3 py-2">
                                        <input
                                            type="text"
                                            :value="observaciones['{{ $student->id }}'] ?? ''"
                                            @input="observaciones['{{ $student->id }}'] = $event.target.value"
                                            placeholder="Observación..."
                                            class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-400"
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Barra de acciones --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                    {{-- Exportaciones --}}
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('calificaciones.export.excel', ['group_id' => $group_id, 'competencia_id' => $competencia_id]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                            </svg>
                            Excel
                        </a>
                        <a href="{{ route('calificaciones.export.pdf', ['group_id' => $group_id, 'competencia_id' => $competencia_id]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-red-600 hover:bg-red-700 text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                            PDF
                        </a>
                        <a href="{{ route('calificaciones.export.word', ['group_id' => $group_id, 'competencia_id' => $competencia_id]) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Word
                        </a>
                    </div>

                    {{-- Guardar --}}
                    <button
                        @click="save()"
                        class="inline-flex items-center gap-2 px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg transition-colors shadow-sm"
                    >
                        <svg class="w-4 h-4" wire:loading.remove wire:target="saveNotasData" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span wire:loading.remove wire:target="saveNotasData">Guardar Calificaciones</span>
                        <span wire:loading wire:target="saveNotasData">Guardando...</span>
                    </button>
                </div>
            </div>
        </div>

    @elseif($competencia_id && count($resultadosAprendizaje) === 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
            <p class="text-yellow-700 text-sm font-medium">La competencia seleccionada no tiene Resultados de Aprendizaje registrados.</p>
            <p class="text-yellow-600 text-xs mt-1">Agrégalos desde el módulo de Programas.</p>
        </div>

    @elseif($group_id && count($students) === 0)
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-8 text-center">
            <p class="text-yellow-700 text-sm font-medium">El grupo seleccionado no tiene aprendices registrados.</p>
        </div>

    @else
        <div class="bg-gray-50 rounded-xl border-2 border-dashed border-gray-200 p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <p class="text-gray-400 text-sm">Seleccione institución, grupo y competencia para registrar calificaciones.</p>
        </div>
    @endif
</div>
