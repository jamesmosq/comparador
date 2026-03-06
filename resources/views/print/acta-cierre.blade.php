{{--
    ACTA DE CIERRE DEL PROCESO FORMATIVO
    Tipo: cierre
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta Cierre {{ $acta->numero_acta }}</title>
    @include('print.partials.acta-styles')
</head>
<body>

{{-- ── ENCABEZADO OFICIAL DEL ACTA ──────────────────────────────── --}}
@include('print.partials.acta-header', ['acta' => $acta, 'logoSrc' => $logoSrc])

{{-- ── DATOS GENERALES ──────────────────────────────────────────── --}}
@include('print.partials.acta-datos-generales', ['acta' => $acta])

{{-- ── FICHAS / GRUPOS ──────────────────────────────────────────── --}}
@include('print.partials.acta-fichas', ['acta' => $acta])

{{-- ── DOCENTE PAR ──────────────────────────────────────────────── --}}
@include('print.partials.acta-docente-par', ['acta' => $acta])

{{-- ── AGENDA ───────────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">AGENDA O PUNTOS PARA DESARROLLAR</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body">{{ $acta->agenda ?? '' }}</td></tr>
</table>

{{-- ── OBJETIVO ─────────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">OBJETIVO(S) DE LA REUNIÓN</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body">{{ $acta->objetivo ?? '' }}</td></tr>
</table>

{{-- ── DESARROLLO ───────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">DESARROLLO DE LA REUNIÓN</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body tall">{{ $acta->desarrollo ?? '' }}</td></tr>
</table>

{{-- ── LISTA DE APRENDICES CON JUICIO EVALUATIVO ───────────────── --}}
@if($acta->juicios->isNotEmpty())
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">ESTADO DE APRENDICES — JUICIOS EVALUATIVOS</td></tr>
</table>
<table class="data-table" style="margin-top:-1px;font-size:9px;">
    <thead>
        <tr>
            <th style="width:12%">Tipo Doc.</th>
            <th style="width:18%">No. Documento</th>
            <th style="width:40%">Nombre y Apellidos</th>
            <th style="width:30%">Juicio de Evaluación</th>
        </tr>
    </thead>
    <tbody>
        @foreach($acta->juicios as $j)
        <tr>
            <td>{{ $j->student?->identifier ? (strlen($j->student->identifier) <= 8 ? 'TI' : 'CC') : '—' }}</td>
            <td>{{ $j->student?->identifier ?? '—' }}</td>
            <td>{{ $j->student?->name ?? '—' }}</td>
            <td style="text-align:center;font-weight:bold;
                color:{{ $j->juicio === 'aprobado' ? '#065f46' : ($j->juicio === 'no_aprobado' ? '#991b1b' : '#92400e') }}">
                {{ strtoupper(str_replace('_', ' ', $j->juicio)) }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ── CONCLUSIONES ─────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">CONCLUSIONES</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body min">{{ $acta->conclusiones ?? '' }}</td></tr>
</table>

{{-- ── COMPROMISOS ──────────────────────────────────────────────── --}}
@include('print.partials.acta-compromisos', ['acta' => $acta, 'conFirma' => true])

{{-- ── ASISTENTES CON APROBACIÓN (SI/NO) ──────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">ASISTENTES Y APROBACIÓN DE DECISIONES</td></tr>
</table>
<table class="data-table" style="margin-top:-1px;">
    <thead>
        <tr>
            <th style="width:30%">Nombre</th>
            <th style="width:25%">Dependencia / Empresa</th>
            <th style="width:10%;text-align:center;">Aprueba (SI/NO)</th>
            <th style="width:20%">Observación</th>
            <th style="width:15%">Firma</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>{{ $acta->docentePar?->name ?? '—' }}</td>
            <td>{{ $acta->docentePar?->institution_name ?? '—' }}</td>
            <td style="text-align:center;">SI</td>
            <td></td>
            <td style="height:30px;"></td>
        </tr>
        <tr>
            <td>{{ $acta->user?->name ?? '—' }}</td>
            <td>Instructor SENA – {{ $acta->user?->centro_formacion ?? 'Centro de Formación' }}</td>
            <td style="text-align:center;">SI</td>
            <td></td>
            <td style="height:30px;"></td>
        </tr>
        @for($i = 0; $i < 2; $i++)
        <tr><td style="height:24px;"></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

{{-- ── NOTA LEGAL ───────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body" style="font-size:8px;color:#444;">
        De acuerdo con La Ley 1581 de 2012, Protección de Datos Personales, el Servicio Nacional de Aprendizaje SENA, se compromete a garantizar la seguridad y protección de los datos personales que se encuentran almacenados en este documento, y les dará el tratamiento correspondiente en cumplimiento de lo establecido legalmente.
    </td></tr>
</table>

{{-- ── ANEXOS ───────────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">ANEXOS</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body" style="min-height:30px;">{{ $acta->observaciones ?? '' }}</td></tr>
</table>

<div style="text-align:right;font-size:9px;color:#888;margin-top:8px;">
    Generado: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; {{ config('app.name') }}
</div>

{{-- ── BOTONES ──────────────────────────────────────────────────── --}}
@include('print.partials.acta-botones', ['acta' => $acta])

</body>
</html>
