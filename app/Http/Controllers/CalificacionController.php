<?php

namespace App\Http\Controllers;

use App\Models\Calificacion;
use App\Models\Competencia;
use App\Models\Group;
use App\Models\ResultadoAprendizaje;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use Spatie\SimpleExcel\SimpleExcelWriter;

class CalificacionController extends Controller
{
    /**
     * Carga y valida los datos necesarios para los tres formatos de exportación.
     */
    private function loadData(Request $request): array
    {
        $groupId       = $request->query('group_id');
        $competenciaId = $request->query('competencia_id');

        $group = Group::with(['institution', 'programaFormacion'])->findOrFail($groupId);

        abort_if(
            ! auth()->user()->isAdmin() && $group->institution?->user_id !== auth()->id(),
            403
        );

        $competencia = Competencia::findOrFail($competenciaId);
        $ras         = ResultadoAprendizaje::where('competencia_id', $competenciaId)->orderBy('order')->get();
        $students    = Student::where('group_id', $groupId)->orderBy('name')->get();

        $calificaciones = Calificacion::whereIn('student_id', $students->pluck('id'))
            ->whereIn('resultado_aprendizaje_id', $ras->pluck('id'))
            ->where('group_id', $groupId)
            ->get()
            ->keyBy(fn($c) => $c->student_id . '_' . $c->resultado_aprendizaje_id);

        return compact('group', 'competencia', 'ras', 'students', 'calificaciones');
    }

    // ── Excel ──────────────────────────────────────────────────────────────────

    public function exportExcel(Request $request)
    {
        [
            'group'          => $group,
            'competencia'    => $competencia,
            'ras'            => $ras,
            'students'       => $students,
            'calificaciones' => $calificaciones,
        ] = $this->loadData($request);

        $tempPath = sys_get_temp_dir() . '/calificaciones_' . uniqid() . '.xlsx';
        $writer   = SimpleExcelWriter::create($tempPath);

        // Fila de encabezados
        $headers = ['Aprendiz', 'Identificación'];
        foreach ($ras as $ra) {
            $headers[] = ($ra->code ? '[' . $ra->code . '] ' : '') . $ra->name;
            $headers[] = 'Equivalencia';
        }
        $headers[] = 'Promedio';
        $headers[] = 'Observación';
        $writer->addRow($headers);

        // Filas de estudiantes
        foreach ($students as $student) {
            $row          = [$student->name, $student->identifier ?? ''];
            $notasStudent = [];

            foreach ($ras as $ra) {
                $cal  = $calificaciones[$student->id . '_' . $ra->id] ?? null;
                $nota = $cal?->nota;
                $row[] = $nota !== null ? number_format($nota, 1) : '';
                $row[] = $nota !== null ? ($nota >= 3.0 ? 'APROBADO' : 'NO APROBADO') : '';
                if ($nota !== null) {
                    $notasStudent[] = $nota;
                }
            }

            $promedio = count($notasStudent) > 0
                ? array_sum($notasStudent) / count($notasStudent)
                : null;
            $row[] = $promedio !== null ? number_format($promedio, 1) : '';

            // Observación: primer registro encontrado del estudiante
            $obs = null;
            foreach ($ras as $ra) {
                $cal = $calificaciones[$student->id . '_' . $ra->id] ?? null;
                if ($cal?->observacion) {
                    $obs = $cal->observacion;
                    break;
                }
            }
            $row[] = $obs ?? '';

            $writer->addRow($row);
        }

        $writer->close();

        $groupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $group->name);

        return response()->download($tempPath, "calificaciones-{$groupName}.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    // ── PDF ────────────────────────────────────────────────────────────────────

    public function exportPdf(Request $request)
    {
        $data    = $this->loadData($request);
        $logoSrc = 'file://' . public_path('images/sena-logo.png');

        $pdf = Pdf::loadView('print.calificaciones', array_merge($data, ['logoSrc' => $logoSrc]));
        $pdf->setPaper('letter', 'landscape');

        $groupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $data['group']->name);

        return $pdf->download("calificaciones-{$groupName}.pdf");
    }

    // ── Word ───────────────────────────────────────────────────────────────────

    public function exportWord(Request $request)
    {
        [
            'group'          => $group,
            'competencia'    => $competencia,
            'ras'            => $ras,
            'students'       => $students,
            'calificaciones' => $calificaciones,
        ] = $this->loadData($request);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(9);

        $section = $phpWord->addSection([
            'paperSize'    => 'Letter',
            'orientation'  => 'landscape',
            'marginTop'    => 720,
            'marginBottom' => 720,
            'marginLeft'   => 720,
            'marginRight'  => 720,
        ]);

        // ── Título ────────────────────────────────────────────────────────────
        $section->addText(
            'REGISTRO DE CALIFICACIONES',
            ['bold' => true, 'size' => 13, 'color' => '1A5C1A'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 100]
        );

        // ── Info general ──────────────────────────────────────────────────────
        $baseTable = ['borderSize' => 6, 'borderColor' => '000000', 'cellMargin' => 60];
        $green     = ['bgColor' => 'C6E0B4'];
        $lbl       = ['bold' => true, 'size' => 8, 'color' => '1A3A1A', 'allCaps' => true];
        $val       = ['size' => 9];

        $infoTbl = $section->addTable($baseTable);
        $infoTbl->addRow();

        foreach ([
            ['Institución',    $group->institution?->name ?? '—',                               4000],
            ['Grupo / Ficha',  $group->name . ($group->ficha_number ? ' (' . $group->ficha_number . ')' : ''), 3200],
            ['Programa',       $group->programaFormacion?->name ?? '—',                         4800],
        ] as [$label, $value, $width]) {
            $c = $infoTbl->addCell($width);
            $c->addText($label, $lbl, ['spaceAfter' => 20]);
            $c->addText($value, $val, ['spaceAfter' => 0]);
        }

        $infoTbl->addRow();
        $compCell = $infoTbl->addCell(12000, ['gridSpan' => 3]);
        $compRun  = $compCell->addTextRun(['spaceAfter' => 0]);
        $compRun->addText('Competencia: ', $lbl);
        $compRun->addText(
            ($competencia->code ? '[' . $competencia->code . '] ' : '') . $competencia->name,
            $val
        );

        $section->addTextBreak(1);

        // ── Tabla de calificaciones ───────────────────────────────────────────
        // Ancho total en landscape Letter ≈ 12240 twips
        $totalW      = 12240;
        $nameW       = 2200;
        $idW         = 1100;
        $promedioW   = 700;
        $obsW        = 1500;
        $remaining   = $totalW - $nameW - $idW - $promedioW - $obsW;
        $raCount     = count($ras);
        $raNotaW     = $raCount > 0 ? (int) (($remaining / ($raCount * 2)) * 1.3) : 500;
        $raEquivW    = $raCount > 0 ? (int) (($remaining / ($raCount * 2)) * 0.7) : 400;

        $tbl = $section->addTable(array_merge($baseTable, ['width' => $totalW, 'unit' => 'dxa']));

        // Fila de encabezados
        $tbl->addRow(500);
        $tbl->addCell($nameW, $green)->addText('Aprendiz',      $lbl, ['alignment' => Jc::LEFT]);
        $tbl->addCell($idW,   $green)->addText('Identificación', $lbl, ['alignment' => Jc::CENTER]);
        foreach ($ras as $ra) {
            $raLabel = ($ra->code ? '[' . $ra->code . '] ' : '') . $ra->name;
            $raLabel = mb_strlen($raLabel) > 45 ? mb_substr($raLabel, 0, 45) . '…' : $raLabel;
            $tbl->addCell($raNotaW + $raEquivW, array_merge($green, ['gridSpan' => 2]))
                ->addText($raLabel, ['bold' => true, 'size' => 7, 'color' => '1A3A1A'], ['alignment' => Jc::CENTER]);
        }
        $tbl->addCell($promedioW, $green)->addText('Prom.',      $lbl, ['alignment' => Jc::CENTER]);
        $tbl->addCell($obsW,      $green)->addText('Observación', $lbl, ['alignment' => Jc::CENTER]);

        // Sub-fila Nota | Equiv.
        $subGreen = ['bgColor' => 'E8F4E8'];
        $subLbl   = ['bold' => true, 'size' => 7, 'color' => '1A3A1A'];
        $tbl->addRow(280);
        $tbl->addCell($nameW,    $subGreen)->addText('', $subLbl);
        $tbl->addCell($idW,      $subGreen)->addText('', $subLbl);
        foreach ($ras as $ra) {
            $tbl->addCell($raNotaW,  $subGreen)->addText('Nota',  $subLbl, ['alignment' => Jc::CENTER]);
            $tbl->addCell($raEquivW, $subGreen)->addText('Equiv.', $subLbl, ['alignment' => Jc::CENTER]);
        }
        $tbl->addCell($promedioW, $subGreen)->addText('', $subLbl);
        $tbl->addCell($obsW,      $subGreen)->addText('', $subLbl);

        // Filas de estudiantes
        foreach ($students as $student) {
            $tbl->addRow(420);
            $tbl->addCell($nameW)->addText($student->name,             ['size' => 8], ['alignment' => Jc::LEFT]);
            $tbl->addCell($idW  )->addText($student->identifier ?? '', ['size' => 8], ['alignment' => Jc::CENTER]);

            $notasStudent = [];
            foreach ($ras as $ra) {
                $cal  = $calificaciones[$student->id . '_' . $ra->id] ?? null;
                $nota = $cal?->nota;

                $notaCell = $tbl->addCell($raNotaW);
                $notaCell->addText(
                    $nota !== null ? number_format($nota, 1) : '—',
                    ['size' => 9, 'bold' => $nota !== null],
                    ['alignment' => Jc::CENTER]
                );

                $equivCell = $tbl->addCell($raEquivW);
                if ($nota !== null) {
                    $isAp = $nota >= 3.0;
                    $equivCell->addText(
                        $isAp ? 'AP' : 'NA',
                        ['size' => 7, 'bold' => true, 'color' => $isAp ? '1A7A1A' : 'CC0000'],
                        ['alignment' => Jc::CENTER]
                    );
                    $notasStudent[] = $nota;
                } else {
                    $equivCell->addText('—', ['size' => 8, 'color' => '888888'], ['alignment' => Jc::CENTER]);
                }
            }

            $promedio     = count($notasStudent) > 0
                ? array_sum($notasStudent) / count($notasStudent)
                : null;
            $promedioCell = $tbl->addCell($promedioW);
            if ($promedio !== null) {
                $isAp = $promedio >= 3.0;
                $promedioCell->addText(
                    number_format($promedio, 1),
                    ['size' => 9, 'bold' => true, 'color' => $isAp ? '1A7A1A' : 'CC0000'],
                    ['alignment' => Jc::CENTER]
                );
            } else {
                $promedioCell->addText('—', ['size' => 8, 'color' => '888888'], ['alignment' => Jc::CENTER]);
            }

            $obs = null;
            foreach ($ras as $ra) {
                $cal = $calificaciones[$student->id . '_' . $ra->id] ?? null;
                if ($cal?->observacion) {
                    $obs = $cal->observacion;
                    break;
                }
            }
            $tbl->addCell($obsW)->addText($obs ?? '', ['size' => 7]);
        }

        // ── Leyenda ───────────────────────────────────────────────────────────
        $section->addTextBreak(1);
        $section->addText(
            'AP = APROBADO (nota ≥ 3.0)   |   NA = NO APROBADO (nota < 3.0)   |   Escala: 1.0 – 5.0',
            ['size' => 7, 'color' => '555555'],
            ['spaceAfter' => 200]
        );

        // ── Firma ─────────────────────────────────────────────────────────────
        $section->addText(str_repeat('_', 40), ['size' => 9]);
        $section->addText('INSTRUCTOR SENA', ['bold' => true, 'size' => 8, 'color' => '1A3A1A']);

        // ── Guardar y descargar ───────────────────────────────────────────────
        $tempPath  = sys_get_temp_dir() . '/calificaciones_' . uniqid() . '.docx';
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        $groupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $group->name);

        return response()->download($tempPath, "calificaciones-{$groupName}.docx")->deleteFileAfterSend();
    }
}
