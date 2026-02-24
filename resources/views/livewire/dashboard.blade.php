<?php

use Livewire\Volt\Component;
use App\Models\Institution;
use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Competencia;

new class extends Component
{
    public int    $totalInstitutions = 0;
    public int    $totalGroups       = 0;
    public int    $totalStudents     = 0;
    public string $attendanceToday   = 'Sin datos';
    public int    $presentToday      = 0;
    public int    $absentToday       = 0;
    public array  $recentAttendances = [];
    public array  $riskAlerts        = [];

    public function mount(): void
    {
        $user = auth()->user();

        $institutionIds = Institution::visibleTo($user)->pluck('id');
        $groupIds       = Group::whereIn('institution_id', $institutionIds)->pluck('id');
        $studentIds     = Student::whereIn('group_id', $groupIds)->pluck('id');

        $this->totalInstitutions = $institutionIds->count();
        $this->totalGroups       = $groupIds->count();
        $this->totalStudents     = $studentIds->count();

        // Asistencia de hoy
        $today        = now()->format('Y-m-d');
        $todayBase    = Attendance::whereIn('student_id', $studentIds)->whereDate('attendance_date', $today);
        $todayTotal   = (clone $todayBase)->count();
        $todayPresent = (clone $todayBase)->where('is_present', true)->count();

        $this->presentToday = $todayPresent;
        $this->absentToday  = $todayTotal - $todayPresent;

        if ($todayTotal > 0) {
            $this->attendanceToday = round(($todayPresent / $todayTotal) * 100) . '%';
        }

        // Actividad reciente
        $this->recentAttendances = Attendance::with(['student.group.institution'])
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('attendance_date')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(fn($a) => [
                'date'        => $a->attendance_date,
                'student'     => $a->student->name ?? '-',
                'group'       => $a->student->group->name ?? '-',
                'institution' => $a->student->group->institution->name ?? '-',
                'is_present'  => $a->is_present,
                'is_justified'=> $a->is_justified,
            ])
            ->toArray();

        // Alertas de riesgo: aprendices con ≥15% inasistencia injustificada por competencia
        if ($studentIds->isNotEmpty()) {
            $riskData = Attendance::selectRaw('
                    student_id,
                    competencia_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN is_present = 0 AND is_justified = 0 THEN 1 ELSE 0 END) as unjustified
                ')
                ->whereIn('student_id', $studentIds)
                ->whereNotNull('competencia_id')
                ->groupBy('student_id', 'competencia_id')
                ->havingRaw('COUNT(*) > 0 AND (SUM(CASE WHEN is_present = 0 AND is_justified = 0 THEN 1 ELSE 0 END) / COUNT(*)) >= 0.15')
                ->get();

            if ($riskData->isNotEmpty()) {
                $studentMap    = Student::whereIn('id', $riskData->pluck('student_id')->unique())->pluck('name', 'id');
                $competenciaMap = Competencia::whereIn('id', $riskData->pluck('competencia_id')->unique())->pluck('name', 'id');

                $this->riskAlerts = $riskData->map(fn($a) => [
                    'student'     => $studentMap[$a->student_id] ?? '-',
                    'competencia' => $competenciaMap[$a->competencia_id] ?? '—',
                    'unjustified' => (int) $a->unjustified,
                    'total'       => (int) $a->total,
                    'pct'         => round($a->unjustified / $a->total * 100),
                ])
                ->sortByDesc('pct')
                ->take(10)
                ->values()
                ->toArray();
            }
        }
    }
}; ?>

<div class="p-6 max-w-5xl mx-auto">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 text-sm mt-1">Bienvenido, {{ auth()->user()->name }}. Hoy es {{ now()->translatedFormat('l, d \de F \de Y') }}.</p>
    </div>

    <!-- Tarjetas de estadísticas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Instituciones</p>
            <p class="text-4xl font-bold text-indigo-600">{{ $totalInstitutions }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Fichas / Grupos</p>
            <p class="text-4xl font-bold text-indigo-600">{{ $totalGroups }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Aprendices</p>
            <p class="text-4xl font-bold text-indigo-600">{{ $totalStudents }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Asistencia Hoy</p>
            <p class="text-4xl font-bold {{ $attendanceToday === 'Sin datos' ? 'text-gray-400' : 'text-green-600' }}">
                {{ $attendanceToday }}
            </p>
            @if($presentToday + $absentToday > 0)
            <p class="text-xs text-gray-400 mt-1">{{ $presentToday }} pres. / {{ $absentToday }} aus.</p>
            @endif
        </div>
    </div>

    <!-- Alertas de riesgo -->
    @if(count($riskAlerts) > 0)
    <div class="mb-8 bg-red-50 border border-red-200 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-red-200 flex items-center gap-2">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div>
                <h2 class="font-semibold text-red-800">Alertas de Inasistencia Injustificada</h2>
                <p class="text-xs text-red-600">Aprendices con ≥ 15% de inasistencia injustificada por competencia</p>
            </div>
            <span class="ml-auto text-xs font-bold text-red-700 bg-red-100 px-2.5 py-1 rounded-full">
                {{ count($riskAlerts) }}
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-red-200 bg-red-100/50">
                        <th class="px-5 py-2 text-left text-xs font-semibold text-red-700 uppercase tracking-wider">Aprendiz</th>
                        <th class="px-5 py-2 text-left text-xs font-semibold text-red-700 uppercase tracking-wider hidden md:table-cell">Competencia</th>
                        <th class="px-5 py-2 text-center text-xs font-semibold text-red-700 uppercase tracking-wider">Injustif.</th>
                        <th class="px-5 py-2 text-center text-xs font-semibold text-red-700 uppercase tracking-wider">Total</th>
                        <th class="px-5 py-2 text-center text-xs font-semibold text-red-700 uppercase tracking-wider">%</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-100">
                    @foreach($riskAlerts as $alert)
                    <tr class="hover:bg-red-50 transition-colors">
                        <td class="px-5 py-2.5 font-medium text-gray-900">{{ $alert['student'] }}</td>
                        <td class="px-5 py-2.5 text-gray-600 hidden md:table-cell text-xs">{{ $alert['competencia'] }}</td>
                        <td class="px-5 py-2.5 text-center text-red-600 font-semibold">{{ $alert['unjustified'] }}</td>
                        <td class="px-5 py-2.5 text-center text-gray-500">{{ $alert['total'] }}</td>
                        <td class="px-5 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold
                                {{ $alert['pct'] >= 20 ? 'bg-red-200 text-red-900' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $alert['pct'] }}%
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-red-200 text-right">
            <a href="{{ route('reportes') }}" class="text-xs text-red-600 hover:text-red-800 font-medium hover:underline">
                Ver reporte completo →
            </a>
        </div>
    </div>
    @endif

    <!-- Accesos rápidos -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('horarios') }}"
           class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 hover:shadow-md hover:border-indigo-200 transition-all group">
            <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center group-hover:bg-indigo-200 transition-colors">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-gray-800 text-sm">Gestionar Horarios</p>
                <p class="text-xs text-gray-400">Instituciones, fichas y horarios</p>
            </div>
        </a>
        <a href="{{ route('asistencia') }}"
           class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 hover:shadow-md hover:border-green-200 transition-all group">
            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-gray-800 text-sm">Tomar Asistencia</p>
                <p class="text-xs text-gray-400">Registrar presencia diaria</p>
            </div>
        </a>
        <a href="{{ route('reportes') }}"
           class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 hover:shadow-md hover:border-purple-200 transition-all group">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center group-hover:bg-purple-200 transition-colors">
                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="font-semibold text-gray-800 text-sm">Ver Reportes</p>
                <p class="text-xs text-gray-400">Estadísticas y exportación</p>
            </div>
        </a>
    </div>

    <!-- Actividad reciente -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Actividad Reciente</h2>
            <p class="text-xs text-gray-400 mt-0.5">Últimos 10 registros de asistencia</p>
        </div>
        @if(count($recentAttendances) > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Aprendiz</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden md:table-cell">Grupo</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider hidden lg:table-cell">Institución</th>
                            <th class="px-6 py-3 text-xs font-semibold text-gray-400 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($recentAttendances as $att)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $att['date'] }}</td>
                            <td class="px-6 py-3 font-medium text-gray-900">{{ $att['student'] }}</td>
                            <td class="px-6 py-3 text-gray-500 hidden md:table-cell">{{ $att['group'] }}</td>
                            <td class="px-6 py-3 text-gray-500 hidden lg:table-cell">{{ $att['institution'] }}</td>
                            <td class="px-6 py-3">
                                @if($att['is_present'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Presente
                                    </span>
                                @elseif($att['is_justified'])
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        Justificado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        Ausente
                                    </span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="px-6 py-12 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-gray-400 text-sm">No hay registros de asistencia todavía.</p>
                <a href="{{ route('asistencia') }}" class="text-indigo-600 text-sm mt-1 inline-block hover:underline">
                    Tomar la primera asistencia
                </a>
            </div>
        @endif
    </div>
</div>
