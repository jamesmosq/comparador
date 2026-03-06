<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">ESTABLECIMIENTO Y ACEPTACIÓN DE COMPROMISOS</td></tr>
</table>
<table class="data-table" style="margin-top:-1px;">
    <thead>
        <tr>
            <th style="width:{{ $conFirma ? '45%' : '55%' }}">Actividad / Decisión</th>
            <th style="width:20%">Fecha</th>
            <th style="width:{{ $conFirma ? '20%' : '25%' }}">Responsable</th>
            @if($conFirma)
            <th style="width:15%">Firma</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($acta->compromisoItems as $c)
        <tr>
            <td>{{ $c->descripcion }}</td>
            <td style="text-align:center;">{{ $c->fecha_limite ? $c->fecha_limite->format('d/m/Y') : '—' }}</td>
            <td>{{ $c->responsable_label }}</td>
            @if($conFirma)
            <td style="height:28px;"></td>
            @endif
        </tr>
        @empty
        @for($i = 0; $i < 3; $i++)
        <tr>
            <td style="height:24px;"></td>
            <td></td>
            <td></td>
            @if($conFirma)<td></td>@endif
        </tr>
        @endfor
        @endforelse
    </tbody>
</table>
