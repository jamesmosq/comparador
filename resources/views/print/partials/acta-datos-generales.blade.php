<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">DATOS GENERALES</td></tr>
</table>
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td style="width:20%"><span class="lbl">Ciudad y Fecha</span><br><span class="val">{{ $acta->fecha->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span></td>
        <td style="width:14%"><span class="lbl">Hora Inicio</span><br><span class="val">{{ $acta->hora_inicio ?? '—' }}</span></td>
        <td style="width:14%"><span class="lbl">Hora Fin</span><br><span class="val">{{ $acta->hora_fin ?? '—' }}</span></td>
        <td style="width:26%"><span class="lbl">Lugar / Enlace</span><br><span class="val">{{ $acta->lugar ?? '—' }}</span></td>
        <td style="width:26%"><span class="lbl">Dirección / Regional / Centro</span><br>
            <span class="val">{{ $acta->user?->regional ?? '' }}{{ $acta->user?->regional && $acta->user?->centro_formacion ? ' – ' : '' }}{{ $acta->user?->centro_formacion ?? '—' }}</span>
        </td>
    </tr>
</table>
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td><span class="lbl">Instructor SENA</span><br><span class="val">{{ $acta->user?->name ?? '—' }}</span></td>
    </tr>
</table>
