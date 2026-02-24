<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Spatie\SimpleExcel\SimpleExcelWriter;

class ExportController extends Controller
{
    public function report(Request $request)
    {
        $groupId       = $request->query('group_id');
        $start         = $request->query('start', now()->startOfMonth()->format('Y-m-d'));
        $end           = $request->query('end',   now()->format('Y-m-d'));
        $competenciaId = $request->query('competencia_id');

        $group = Group::find($groupId);

        if (! $group) {
            abort(404, 'Grupo no encontrado.');
        }

        $students  = Student::where('group_id', $groupId)->orderBy('name')->get();
        $groupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $group->name);
        $filename  = "reporte-{$groupName}-{$start}-{$end}.xlsx";

        // Escribir en archivo temporal para evitar conflictos de streaming
        // tempnam() no agrega extensión; SimpleExcelWriter la necesita para elegir el driver
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'reporte_' . uniqid() . '.xlsx';

        $writer = SimpleExcelWriter::create($tempPath);

        $writer->addRow([
            'Nombre',
            'Identificación',
            'Correo',
            'Días Asistidos',
            'Inasist. Justificadas',
            'Inasist. Injustificadas',
            'Total Registros',
            '% Asistencia',
            '% Inasist. Injustificada',
            'Período Inicio',
            'Período Fin',
        ]);

        foreach ($students as $student) {
            $query = Attendance::where('student_id', $student->id)
                ->whereBetween('attendance_date', [$start, $end]);

            if ($competenciaId) {
                $query->where('competencia_id', $competenciaId);
            }

            $records     = $query->get();
            $total       = $records->count();
            $attended    = $records->where('is_present', true)->count();
            $justified   = $records->where('is_present', false)->where('is_justified', true)->count();
            $unjustified = $records->where('is_present', false)->where('is_justified', false)->count();

            $attendancePct  = $total > 0 ? round($attended    / $total * 100) : null;
            $unjustifiedPct = $total > 0 ? round($unjustified / $total * 100) : null;

            $writer->addRow([
                $student->name,
                $student->identifier ?? 'N/A',
                $student->email      ?? '—',
                $attended,
                $justified,
                $unjustified,
                $total,
                $attendancePct  !== null ? $attendancePct  . '%' : 'Sin datos',
                $unjustifiedPct !== null ? $unjustifiedPct . '%' : 'Sin datos',
                $start,
                $end,
            ]);
        }

        $writer->close();

        return response()->download($tempPath, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }
}
