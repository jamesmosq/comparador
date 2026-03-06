<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">FICHAS / GRUPOS ARTICULACIÓN</td></tr>
</table>
<table class="data-table" style="margin-top:-1px;">
    <thead>
        <tr>
            <th style="width:12%">Ficha #</th>
            <th style="width:35%">Programa de Formación</th>
            <th style="width:28%">Grado / Grupo</th>
            <th style="width:25%">Institución Educativa</th>
        </tr>
    </thead>
    <tbody>
        @forelse($acta->groups as $grp)
        <tr>
            <td>{{ $grp->ficha_number ?? '—' }}</td>
            <td>{{ $grp->programaFormacion?->name ?? '—' }}</td>
            <td>{{ $grp->name }}</td>
            <td>{{ $grp->institution?->name ?? '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;color:#888;">Sin fichas asociadas</td></tr>
        @endforelse
    </tbody>
</table>
