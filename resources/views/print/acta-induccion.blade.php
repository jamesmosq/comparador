{{--
    ACTA DE INDUCCIÓN / REINDUCCIÓN
    Tipo: induccion, aprobacion_etapa_practica
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta Inducción {{ $acta->numero_acta }}</title>
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
    <tr><td class="sec-head">JUICIOS EVALUATIVOS — COMPETENCIA DE INDUCCIÓN</td></tr>
</table>
@if($acta->competencia)
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td><span class="lbl">Competencia:</span> <span class="val">{{ $acta->competencia->code ? '[' . $acta->competencia->code . '] ' : '' }}{{ $acta->competencia->name }}</span></td>
    </tr>
</table>
@endif
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

{{-- ── NOTA LEGAL CON CLASIFICACIÓN ────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body" style="font-size:8px;color:#444;">
        De acuerdo con La Ley 1581 de 2012, Protección de Datos Personales se debe garantizar la seguridad y protección de los datos personales que se encuentran almacenados en este documento. El Servicio Nacional de Aprendizaje SENA solicita la siguiente clasificación de la información:
    </td></tr>
</table>
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td style="width:25%;text-align:center;"><span class="lbl">Pública</span><br><span style="font-size:16px;">☑</span></td>
        <td style="width:25%;text-align:center;"><span class="lbl">Privado</span><br><span style="font-size:16px;">☑</span></td>
        <td style="width:25%;text-align:center;"><span class="lbl">Semiprivado</span><br><span style="font-size:16px;">☑</span></td>
        <td style="width:25%;text-align:center;"><span class="lbl">Sensible</span><br><span style="font-size:16px;">☑</span></td>
    </tr>
</table>

{{-- ── COMPROMISOS ──────────────────────────────────────────────── --}}
@include('print.partials.acta-compromisos', ['acta' => $acta, 'conFirma' => false])

{{-- ── ASISTENTES ───────────────────────────────────────────────── --}}
@include('print.partials.acta-asistentes', ['acta' => $acta])

{{-- ── BOTONES ──────────────────────────────────────────────────── --}}
@include('print.partials.acta-botones', ['acta' => $acta])

</body>
</html>
