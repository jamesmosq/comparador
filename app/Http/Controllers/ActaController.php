<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Table as TableStyle;

class ActaController extends Controller
{
    private function loadActa(Acta $acta): Acta
    {
        $acta->load([
            'groups.institution',
            'groups.programaFormacion',
            'docentePar',
            'competencia',
            'compromisoItems',
            'user',
        ]);
        return $acta;
    }

    public function preview(Acta $acta)
    {
        $acta    = $this->loadActa($acta);
        $logoSrc = asset('images/sena-logo.png');
        return view('print.acta', compact('acta', 'logoSrc'));
    }

    // ── Helpers de estilo ──────────────────────────────────────────────

    private function secHead(): array
    {
        return ['bold' => true, 'size' => 9, 'color' => '1A3A1A', 'allCaps' => true];
    }

    private function lbl(): array
    {
        return ['bold' => true, 'size' => 8, 'color' => '1A3A1A', 'allCaps' => true];
    }

    private function val(): array
    {
        return ['size' => 10];
    }

    private function thStyle(): array
    {
        return ['bold' => true, 'size' => 8, 'color' => '1A3A1A', 'allCaps' => true];
    }

    private function tdStyle(): array
    {
        return ['size' => 10];
    }

    private function cellGreen(): array
    {
        return ['bgColor' => 'C6E0B4'];
    }

    private function cellWhite(): array
    {
        return [];
    }

    private function baseTable(array $extra = []): array
    {
        return array_merge([
            'borderSize'  => 6,
            'borderColor' => '000000',
            'cellMargin'  => 60,
        ], $extra);
    }

    private function addSectionHeader($section, string $text): void
    {
        $tbl = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $row = $tbl->addRow();
        $cell = $row->addCell(9360, $this->cellGreen());
        $cell->addText($text, $this->secHead(), ['spaceAfter' => 0, 'spaceBefore' => 0]);
    }

    // ── Exportar Word ──────────────────────────────────────────────────

    public function exportWord(Acta $acta)
    {
        $acta = $this->loadActa($acta);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'paperSize'   => 'Letter',
            'marginTop'   => 720,
            'marginBottom'=> 720,
            'marginLeft'  => 900,
            'marginRight' => 900,
        ]);

        // ── 1. Encabezado SENA ─────────────────────────────────────────
        $hdr = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));

        // Fila 1: logo | título | código/versión/fecha
        $hdr->addRow(900);

        // Logo (rowspan no disponible nativamente, usamos vmerge)
        $logoCell = $hdr->addCell(1000, ['vMerge' => 'restart', 'valign' => 'center']);
        $logoPath = public_path('images/sena-logo.png');
        if (file_exists($logoPath)) {
            $logoCell->addImage($logoPath, ['width' => 55, 'height' => 55, 'alignment' => Jc::CENTER]);
        } else {
            $logoCell->addText('SENA', ['bold' => true, 'size' => 11, 'color' => '1A5C1A'], ['alignment' => Jc::CENTER]);
        }

        // Título central (rowspan: restart)
        $titleCell = $hdr->addCell(6760, ['vMerge' => 'restart', 'valign' => 'center']);
        $titleCell->addText('SISTEMA DE GESTIÓN DE LA CALIDAD',
            ['size' => 8, 'color' => '555555'], ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        $titleCell->addText($acta->tipo_label,
            ['bold' => true, 'size' => 11, 'color' => '1A5C1A', 'allCaps' => true],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);

        // Metadata derecha (fila 1)
        $metaCell1 = $hdr->addCell(1600);
        $r = $metaCell1->addTextRun(['spaceAfter' => 20]);
        $r->addText('Código: ', ['bold' => true, 'size' => 9]);
        $r->addText($acta->tipo_codigo, ['size' => 9]);

        $r2 = $metaCell1->addTextRun(['spaceAfter' => 20]);
        $r2->addText('Versión: ', ['bold' => true, 'size' => 9]);
        $r2->addText('02', ['size' => 9]);

        $r3 = $metaCell1->addTextRun(['spaceAfter' => 0]);
        $r3->addText('Fecha: ', ['bold' => true, 'size' => 9]);
        $r3->addText($acta->fecha->format('d/m/Y'), ['size' => 9]);

        // Fila 2: logo (cont) | título (cont) | nº acta / estado
        $hdr->addRow();
        $hdr->addCell(1000, ['vMerge' => 'continue']);
        $hdr->addCell(6760, ['vMerge' => 'continue']);

        $metaCell2 = $hdr->addCell(1600);
        $r4 = $metaCell2->addTextRun(['spaceAfter' => 20]);
        $r4->addText('N° Acta: ', ['bold' => true, 'size' => 9]);
        $r4->addText($acta->numero_acta, ['size' => 9]);

        $r5 = $metaCell2->addTextRun(['spaceAfter' => 0]);
        $r5->addText('Estado: ', ['bold' => true, 'size' => 9]);
        $r5->addText(ucfirst($acta->estado), ['size' => 9]);

        $section->addTextBreak(0);

        // ── 2. Datos Generales ─────────────────────────────────────────
        $this->addSectionHeader($section, '1. Datos Generales');

        $dg = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $dg->addRow();

        foreach ([
            ['Fecha',            $acta->fecha->locale('es')->isoFormat('D [de] MMMM [de] YYYY'), 1900],
            ['Lugar',            $acta->lugar ?? '—', 2600],
            ['Hora Inicio',      $acta->hora_inicio ?? '—', 1350],
            ['Hora Fin',         $acta->hora_fin ?? '—', 1310],
            ['Instructor SENA',  $acta->user?->name ?? '—', 2200],
        ] as [$label, $value, $width]) {
            $c = $dg->addCell($width);
            $c->addText($label, $this->lbl(), ['spaceAfter' => 30]);
            $c->addText($value, $this->val(), ['spaceAfter' => 0]);
        }

        $section->addTextBreak(0);

        // ── 3. Fichas / Grupos ─────────────────────────────────────────
        $this->addSectionHeader($section, '2. Fichas / Grupos Articulación');

        $gt = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $gt->addRow();
        foreach (['Ficha #' => 1000, 'Programa de Formación' => 4000, 'Grado / Grupo' => 2360, 'Institución Educativa' => 2000] as $th => $w) {
            $c = $gt->addCell($w, $this->cellGreen());
            $c->addText($th, $this->thStyle(), ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        if ($acta->groups->isEmpty()) {
            $gt->addRow();
            $c = $gt->addCell(9360, ['gridSpan' => 4]);
            $c->addText('Sin fichas asociadas', ['size' => 10, 'color' => '888888'], ['alignment' => Jc::CENTER]);
        } else {
            foreach ($acta->groups as $grp) {
                $gt->addRow();
                $gt->addCell(1000)->addText($grp->ficha_number ?? '—', $this->tdStyle());
                $gt->addCell(4000)->addText($grp->programaFormacion?->name ?? '—', $this->tdStyle());
                $gt->addCell(2360)->addText($grp->name, $this->tdStyle());
                $gt->addCell(2000)->addText($grp->institution?->name ?? '—', $this->tdStyle());
            }
        }

        $section->addTextBreak(0);

        // ── 4. Docente Par ─────────────────────────────────────────────
        $this->addSectionHeader($section, '3. Docente Par / Institución de Articulación');

        $dp = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $dp->addRow();
        foreach ([
            ['Nombre Docente Par',       $acta->docentePar?->name ?? '—',             2800],
            ['Documento',                $acta->docentePar?->document_number ?? '—',   1700],
            ['Cargo',                    $acta->docentePar?->position ?? '—',           2060],
            ['Institución Articulación', $acta->docentePar?->institution_name ?? '—',  2800],
        ] as [$label, $value, $width]) {
            $c = $dp->addCell($width);
            $c->addText($label, $this->lbl(), ['spaceAfter' => 30]);
            $c->addText($value, $this->val(), ['spaceAfter' => 0]);
        }

        if ($acta->docentePar?->email) {
            $dp->addRow();
            $emailCell = $dp->addCell(9360, ['gridSpan' => 4]);
            $emailRun = $emailCell->addTextRun(['spaceAfter' => 0]);
            $emailRun->addText('Correo electrónico: ', $this->lbl());
            $emailRun->addText($acta->docentePar->email, $this->val());
        }

        if ($acta->competencia) {
            $dp->addRow();
            $compCell = $dp->addCell(9360, ['gridSpan' => 4]);
            $compRun = $compCell->addTextRun(['spaceAfter' => 0]);
            $compRun->addText('Competencia: ', $this->lbl());
            $label = ($acta->competencia->code ? '[' . $acta->competencia->code . '] ' : '') . $acta->competencia->name;
            $compRun->addText($label, $this->val());
        }

        $section->addTextBreak(0);

        // ── 5. Agenda ──────────────────────────────────────────────────
        if ($acta->agenda) {
            $this->addSectionHeader($section, '4. Agenda / Orden del Día');
            $at = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
            $at->addRow();
            $at->addCell(9360)->addText($acta->agenda, $this->val(), ['spaceAfter' => 0]);
            $section->addTextBreak(0);
        }

        // ── 6. Objetivo ────────────────────────────────────────────────
        if ($acta->objetivo) {
            $this->addSectionHeader($section, '5. Objetivo');
            $ot = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
            $ot->addRow();
            $ot->addCell(9360)->addText($acta->objetivo, $this->val(), ['spaceAfter' => 0]);
            $section->addTextBreak(0);
        }

        // ── 7. Desarrollo ──────────────────────────────────────────────
        $devNum = $acta->objetivo ? '6' : '5';
        $this->addSectionHeader($section, "{$devNum}. Desarrollo");
        $devt = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $devt->addRow(800);
        $devt->addCell(9360)->addText($acta->desarrollo ?? '', $this->val(), ['spaceAfter' => 0]);
        $section->addTextBreak(0);

        // ── 8. Compromisos ─────────────────────────────────────────────
        $this->addSectionHeader($section, 'Compromisos / Acuerdos');

        $ct = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $ct->addRow();
        foreach (['Actividad / Compromiso' => 5100, 'Responsable' => 2460, 'Fecha Límite' => 1800] as $th => $w) {
            $c = $ct->addCell($w, $this->cellGreen());
            $c->addText($th, $this->thStyle(), ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        }

        if ($acta->compromisoItems->isEmpty()) {
            $ct->addRow(300); $ct->addCell(5100); $ct->addCell(2460); $ct->addCell(1800);
            $ct->addRow(300); $ct->addCell(5100); $ct->addCell(2460); $ct->addCell(1800);
        } else {
            foreach ($acta->compromisoItems as $c) {
                $ct->addRow();
                $ct->addCell(5100)->addText($c->descripcion, $this->tdStyle());
                $ct->addCell(2460)->addText($c->responsable_label, $this->tdStyle());
                $ct->addCell(1800)->addText(
                    $c->fecha_limite ? $c->fecha_limite->format('d/m/Y') : '—',
                    $this->tdStyle(),
                    ['alignment' => Jc::CENTER]
                );
            }
        }

        $section->addTextBreak(0);

        // ── 9. Observaciones ───────────────────────────────────────────
        $this->addSectionHeader($section, 'Observaciones');
        $obs = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $obs->addRow(500);
        $obs->addCell(9360)->addText($acta->observaciones ?? '', $this->val(), ['spaceAfter' => 0]);
        $section->addTextBreak(0);

        // ── 10. Firmas ─────────────────────────────────────────────────
        $this->addSectionHeader($section, 'Firmas de Asistentes');

        $ft = $section->addTable($this->baseTable(['width' => 9360, 'unit' => 'dxa']));
        $ft->addRow(1200);

        $firmaInstructor = $ft->addCell(4680, ['valign' => 'bottom']);
        $firmaInstructor->addText('', $this->val());  // espacio firma
        $firmaInstructor->addText(str_repeat('_', 45), ['size' => 9]);
        $firmaInstructor->addText($acta->user?->name ?? 'Instructor', ['bold' => true, 'size' => 10], ['spaceAfter' => 20]);
        if ($acta->user?->document_number ?? false) {
            $firmaInstructor->addText('C.C. ' . $acta->user->document_number, ['size' => 9, 'color' => '444444'], ['spaceAfter' => 20]);
        }
        $firmaInstructor->addText('INSTRUCTOR DE FORMACIÓN SENA', ['bold' => true, 'size' => 8, 'color' => '1A3A1A'], ['spaceAfter' => 0]);

        $firmaPar = $ft->addCell(4680, ['valign' => 'bottom']);
        $firmaPar->addText('', $this->val());
        $firmaPar->addText(str_repeat('_', 45), ['size' => 9]);
        $firmaPar->addText($acta->docentePar?->name ?? '—', ['bold' => true, 'size' => 10], ['spaceAfter' => 20]);
        if ($acta->docentePar?->document_number) {
            $firmaPar->addText('C.C. ' . $acta->docentePar->document_number, ['size' => 9, 'color' => '444444'], ['spaceAfter' => 20]);
        }
        $firmaPar->addText(
            strtoupper($acta->docentePar?->position ?? 'DOCENTE PAR'),
            ['bold' => true, 'size' => 8, 'color' => '1A3A1A'],
            ['spaceAfter' => 0]
        );

        // ── Guardar y descargar ────────────────────────────────────────
        $tempPath = sys_get_temp_dir() . '/acta_' . uniqid() . '.docx';
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return response()->download($tempPath, "acta-{$acta->numero_acta}.docx")->deleteFileAfterSend();
    }

    public function exportPdf(Acta $acta)
    {
        $acta    = $this->loadActa($acta);
        $logoSrc = 'file://' . public_path('images/sena-logo.png');
        $isPdf   = true;
        $pdf     = Pdf::loadView('print.acta', compact('acta', 'logoSrc', 'isPdf'));
        $pdf->setPaper('letter', 'portrait');
        return $pdf->download("acta-{$acta->numero_acta}.pdf");
    }
}
