<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Student;
use App\Models\Attendance;
use App\Models\Competencia;
use Illuminate\Http\Request;

class PrintController extends Controller
{
    public function attendance(Request $request)
    {
        $groupId       = $request->query('group_id');
        $date          = $request->query('date', date('Y-m-d'));
        $competenciaId = $request->query('competencia_id');

        $group = Group::with(['institution', 'programaFormacion'])->find($groupId);

        if (! $group) {
            abort(404, 'Grupo no encontrado.');
        }

        $students = Student::where('group_id', $groupId)->orderBy('name')->get();

        $attendanceMap = [];
        if ($students->isNotEmpty()) {
            $query = Attendance::where('attendance_date', $date)
                ->whereIn('student_id', $students->pluck('id'));

            if ($competenciaId) {
                $query->where('competencia_id', $competenciaId);
            } else {
                $query->whereNull('competencia_id');
            }

            $records = $query->get()->keyBy('student_id');

            foreach ($students as $student) {
                $rec = $records->get($student->id);
                if ($rec) {
                    $attendanceMap[$student->id] = $rec->is_present
                        ? 'present'
                        : ($rec->is_justified ? 'justified' : 'absent');
                } else {
                    $attendanceMap[$student->id] = null;
                }
            }
        }

        $competencia = $competenciaId ? Competencia::find($competenciaId) : null;

        return view('print.attendance', compact('group', 'students', 'date', 'competencia', 'attendanceMap'));
    }
}
