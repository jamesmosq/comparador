<?php

use Livewire\Volt\Component;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Competencia;

new class extends Component
{
    public $institutions;
    public $groups       = [];
    public $competencias = [];
    public $report       = [];

    public string $filter_institution_id = '';
    public string $filter_group_id       = '';
    public string $filter_competencia_id = '';
    public string $filter_start          = '';
    public string $filter_end            = '';

    public function mount(): void
    {
        $this->institutions = Institution::visibleTo(auth()->user())->get();
        $this->filter_start = now()->startOfMonth()->format('Y-m-d');
        $this->filter_end   = now()->format('Y-m-d');
    }

    public function updatedFilterInstitutionId($value): void
    {
        $this->groups               = Group::where('institution_id', $value)->get();
        $this->filter_group_id      = '';
        $this->filter_competencia_id = '';
        $this->competencias         = [];
        $this->report               = [];
    }

    public function updatedFilterGroupId($value): void
    {
        $this->filter_competencia_id = '';
        $this->report               = [];
        if ($value) {
            $group = Group::with('programaFormacion.competencias')->find($value);
            $this->competencias = $group?->programaFormacion?->competencias ?? collect([]);
        } else {
            $this->competencias = [];
        }
    }

    public function generateReport(): void
    {
        $this->validate([
            'filter_group_id' => 'required|exists:groups,id',
            'filter_start'    => 'required|date',
            'filter_end'      => 'required|date|after_or_equal:filter_start',
        ], [
            'filter_group_id.required'  => 'Debes seleccionar un grupo.',
            'filter_end.after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha inicio.',
        ]);

        $students = Student::where('group_id', $this->filter_group_id)
            ->orderBy('name')
            ->get();

        $this->report = $students->map(function (Student $student) {
            $query = Attendance::where('student_id', $student->id)
                ->whereBetween('attendance_date', [$this->filter_start, $this->filter_end]);

            if ($this->filter_competencia_id) {
                $query->where('competencia_id', $this->filter_competencia_id);
            }

            $records    = $query->get();
            $total      = $records->count();
            $attended   = $records->where('is_present', true)->count();
            $justified  = $records->where('is_present', false)->where('is_justified', true)->count();
            $unjustified = $records->where('is_present', false)->where('is_justified', false)->count();

            $attendancePct   = $total > 0 ? round(($attended  / $total) * 100) : null;
            $unjustifiedPct  = $total > 0 ? round(($unjustified / $total) * 100) : null;

            return [
                'id'               => $student->id,
                'name'             => $student->name,
                'identifier'       => $student->identifier ?? 'N/A',
                'email'            => $student->email      ?? '—',
                'attended'         => $attended,
                'justified'        => $justified,
                'unjustified'      => $unjustified,
                'total'            => $total,
                'percentage'       => $attendancePct,
                'percentage_str'   => $attendancePct !== null ? $attendancePct . '%' : 'Sin datos',
                'unjustified_pct'  => $unjustifiedPct,
            ];
        })->toArray();
    }

    public function getExportUrlProperty(): string
    {
        return route('export.report', array_filter([
            'group_id'       => $this->filter_group_id,
            'start'          => $this->filter_start,
            'end'            => $this->filter_end,
            'competencia_id' => $this->filter_competencia_id ?: null,
        ]));
    }
}; ?>

<div class="p-6 max-w-5xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Reportes de Asistencia</h1>
        <p class="text-gray-500 text-sm mt-1">Genera reportes por grupo, competencia y período.</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
        <h2 class="font-semibold text-gray-800 mb-4">Filtros</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Institución</label>
                <select wire:model.live="filter_institution_id"
                        class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">Todas las instituciones</option>
                    @foreach($institutions as $inst)
                        <option value="{{ $inst->id }}">{{ $inst->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Grupo *</label>
                <select wire:model.live="filter_group_id"
                        class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    <option value="">-- Selecciona un grupo --</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}">{{ $grp->name }}</option>
                    @endforeach
                </select>
                @error('filter_group_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    Competencia
                    <span class="text-gray-400 font-normal">(opcional)</span>
                </label>
                <select wire:model="filter_competencia_id"
                        class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        @if(count($competencias) === 0) disabled @endif>
                    <option value="">— Todas las competencias —</option>
                    @foreach($competencias as $comp)
                        <option value="{{ $comp->id }}">
                            {{ $comp->code ? '['.$comp->code.'] ' : '' }}{{ $comp->name }}
                        </option>
                    @endforeach
                </select>
                @if($filter_group_id && count($competencias) === 0)
                    <p class="text-xs text-amber-600 mt-1">Este grupo no tiene programa asignado.</p>
                @endif
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio *</label>
                <input type="date" wire:model="filter_start"
                       class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @error('filter_start') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha fin *</label>
                <input type="date" wire:model="filter_end"
                       class="border border-gray-300 p-2.5 rounded-lg w-full text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400">
                @error('filter_end') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <button wire:click="generateReport" wire:loading.attr="disabled"
                    class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-indigo-700 transition-colors disabled:opacity-50">
                <span wire:loading wire:target="generateReport">Generando...</span>
                <span wire:loading.remove wire:target="generateReport">Generar Reporte</span>
            </button>
            @if(count($report) > 0)
            <a href="{{ $this->exportUrl }}" target="_blank"
               class="bg-green-600 text-white px-6 py-2.5 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportar Excel
            </a>
            @endif
        </div>
    </div>

    <!-- Tabla de resultados -->
    @if(count($report) > 0)
        @php
            $totalStudents  = count($report);
            $withData       = collect($report)->filter(fn($r) => $r['total'] > 0)->count();
            $avgPct         = $withData > 0
                ? round(collect($report)->filter(fn($r) => $r['percentage'] !== null)->avg('percentage'))
                : null;
            $atRisk = collect($report)->filter(fn($r) => ($r['unjustified_pct'] ?? 0) >= 20)->count();
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4">
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Aprendices</p>
                <p class="text-2xl font-bold text-indigo-600">{{ $totalStudents }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Con registros</p>
                <p class="text-2xl font-bold text-gray-700">{{ $withData }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">% Asistencia prom.</p>
                <p class="text-2xl font-bold {{ $avgPct >= 80 ? 'text-green-600' : ($avgPct >= 60 ? 'text-yellow-600' : 'text-red-600') }}">
                    {{ $avgPct !== null ? $avgPct . '%' : '—' }}
                </p>
            </div>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 text-center">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">En riesgo (≥20%)</p>
                <p class="text-2xl font-bold {{ $atRisk > 0 ? 'text-red-600' : 'text-green-600' }}">{{ $atRisk }}</p>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-gray-800">Resultados del Reporte</h2>
                    <p class="text-xs text-gray-400 mt-0.5">
                        Período: {{ $filter_start }} al {{ $filter_end }}
                        @if($filter_competencia_id)
                            &nbsp;·&nbsp;
                            {{ collect($competencias)->firstWhere('id', $filter_competencia_id)?->name ?? 'Competencia seleccionada' }}
                        @endif
                    </p>
                </div>
                <span class="text-xs text-gray-400">{{ count($report) }} aprendices</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Nombre</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Identificación</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Asistió</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Justif.</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Injustif.</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">Total</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">% Asistencia</th>
                            <th class="px-4 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider text-center">% Inasist. Injustif.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($report as $row)
                            <tr class="hover:bg-gray-50 transition-colors {{ ($row['unjustified_pct'] ?? 0) >= 20 ? 'bg-red-50/40' : (($row['unjustified_pct'] ?? 0) >= 15 ? 'bg-yellow-50/40' : '') }}">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $row['identifier'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-700">{{ $row['attended'] }}</td>
                                <td class="px-4 py-3 text-center text-yellow-700">{{ $row['justified'] }}</td>
                                <td class="px-4 py-3 text-center text-red-600 font-medium">{{ $row['unjustified'] }}</td>
                                <td class="px-4 py-3 text-center text-gray-500">{{ $row['total'] }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($row['percentage'] !== null)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                            {{ $row['percentage'] >= 80
                                               ? 'bg-green-100 text-green-800'
                                               : ($row['percentage'] >= 60
                                                  ? 'bg-yellow-100 text-yellow-800'
                                                  : 'bg-red-100 text-red-800') }}">
                                            {{ $row['percentage'] }}%
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs italic">Sin datos</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($row['unjustified_pct'] !== null && $row['total'] > 0)
                                        @php $upct = $row['unjustified_pct']; @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                            {{ $upct >= 20
                                               ? 'bg-red-200 text-red-900'
                                               : ($upct >= 15
                                                  ? 'bg-yellow-100 text-yellow-800'
                                                  : 'bg-green-100 text-green-700') }}">
                                            {{ $upct }}%
                                        </span>
                                        @if($upct >= 20)
                                            <span class="block text-xs text-red-600 font-semibold mt-0.5">⚠ Riesgo</span>
                                        @elseif($upct >= 15)
                                            <span class="block text-xs text-yellow-600 mt-0.5">Alerta</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400 text-xs italic">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="mt-4 flex flex-wrap gap-4 text-xs text-gray-500">
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 bg-green-100 border border-green-200 rounded-full inline-block"></span>
                Asistencia ≥ 80%
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 bg-yellow-100 border border-yellow-200 rounded-full inline-block"></span>
                Asistencia 60–79% / Inasistencia injustificada 15–19%
            </span>
            <span class="inline-flex items-center gap-1.5">
                <span class="w-3 h-3 bg-red-100 border border-red-200 rounded-full inline-block"></span>
                Asistencia &lt; 60% / Inasistencia injustificada ≥ 20% (riesgo)
            </span>
        </div>

    @elseif($filter_group_id && empty($report))
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
            <p class="text-sm">Haz clic en <strong>Generar Reporte</strong> para ver los resultados.</p>
        </div>
    @else
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-10 text-center text-gray-400">
            <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="text-sm">Selecciona institución, grupo y rango de fechas para generar el reporte.</p>
        </div>
    @endif
</div>
