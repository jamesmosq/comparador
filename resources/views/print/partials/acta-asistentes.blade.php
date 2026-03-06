<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">ASISTENTES</td></tr>
</table>
<table class="firma-table" style="margin-top:-1px;">
    <tr>
        <td>
            <div class="firma-line">{{ $acta->user?->name ?? 'Instructor' }}</div>
            @if($acta->user?->document_number)
            <div class="firma-cc">C.C. {{ $acta->user->document_number }}</div>
            @endif
            <div class="firma-role">Instructor de Formación SENA</div>
            @if($acta->user?->centro_formacion)
            <div class="firma-cc">{{ $acta->user->centro_formacion }}</div>
            @endif
        </td>
        <td>
            <div class="firma-line">{{ $acta->docentePar?->name ?? '—' }}</div>
            @if($acta->docentePar?->document_number)
            <div class="firma-cc">C.C. {{ $acta->docentePar->document_number }}</div>
            @endif
            <div class="firma-role">{{ $acta->docentePar?->position ?? 'Docente Par' }}</div>
            @if($acta->docentePar?->institution_name)
            <div class="firma-cc">{{ $acta->docentePar->institution_name }}</div>
            @endif
        </td>
    </tr>
</table>
<div style="text-align:right;font-size:9px;color:#888;margin-top:8px;">
    Generado: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; {{ config('app.name') }}
</div>
