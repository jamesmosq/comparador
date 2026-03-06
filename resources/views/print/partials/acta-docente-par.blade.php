<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">DOCENTE PAR / INSTITUCIÓN DE ARTICULACIÓN</td></tr>
</table>
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td style="width:30%"><span class="lbl">Nombre Docente Par</span><br><span class="val">{{ $acta->docentePar?->name ?? '—' }}</span></td>
        <td style="width:18%"><span class="lbl">Documento</span><br><span class="val">{{ $acta->docentePar?->document_number ?? '—' }}</span></td>
        <td style="width:22%"><span class="lbl">Cargo</span><br><span class="val">{{ $acta->docentePar?->position ?? '—' }}</span></td>
        <td style="width:30%"><span class="lbl">Institución Articulación</span><br><span class="val">{{ $acta->docentePar?->institution_name ?? '—' }}</span></td>
    </tr>
    @if($acta->docentePar?->email)
    <tr>
        <td colspan="4"><span class="lbl">Correo electrónico: </span><span class="val">{{ $acta->docentePar->email }}</span></td>
    </tr>
    @endif
</table>
@if($acta->competencia)
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td><span class="lbl">Competencia: </span>
            <span class="val">{{ $acta->competencia->code ? '[' . $acta->competencia->code . '] ' : '' }}{{ $acta->competencia->name }}</span>
        </td>
    </tr>
</table>
@endif
