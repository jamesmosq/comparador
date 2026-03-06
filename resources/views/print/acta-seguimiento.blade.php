{{--
    ACTA DE SEGUIMIENTO AL PROCESO FORMATIVO
    Tipos: seguimiento, visita_seguimiento
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta Seguimiento {{ $acta->numero_acta }}</title>
    @include('print.partials.acta-styles')
</head>
<body>

{{-- ── REGISTRO DE ASISTENCIA Y APROBACIÓN ─────────────────────── --}}
<table class="sena-header">
    <tr>
        <td class="h-logo" rowspan="2">
            <img src="{{ $logoSrc ?? asset('images/sena-logo.png') }}" alt="SENA">
        </td>
        <td class="h-title" rowspan="2">
            <div style="font-size:8px;color:#555;margin-bottom:2px;">SISTEMA DE GESTIÓN DE LA CALIDAD</div>
            <div style="font-size:9px;font-weight:bold;">REGISTRO DE ASISTENCIA Y APROBACIÓN DEL ACTA No. {{ $acta->numero_acta }}</div>
            <div style="font-size:8.5px;margin-top:2px;">
                DEL DÍA {{ $acta->fecha->day }} DEL MES DE {{ strtoupper($acta->fecha->locale('es')->isoFormat('MMMM')) }} DEL AÑO {{ $acta->fecha->year }}
            </div>
        </td>
        <td class="h-meta">
            <div><strong>Código:</strong> {{ $acta->tipo_codigo }}</div>
            <div><strong>Versión:</strong> 02</div>
        </td>
    </tr>
    <tr>
        <td class="h-meta">
            <div><strong>Fecha:</strong> {{ $acta->fecha->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

@if($acta->objetivo)
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body"><strong>OBJETIVO(S):</strong> {{ $acta->objetivo }}</td></tr>
</table>
@endif

{{-- Tabla de registro de asistencia --}}
<table class="data-table" style="margin-top:-1px;font-size:8.5px;">
    <thead>
        <tr>
            <th style="width:4%">No.</th>
            <th style="width:20%">Nombres y Apellidos</th>
            <th style="width:12%">No. Documento</th>
            <th style="width:7%">Planta</th>
            <th style="width:9%">Contratista</th>
            <th style="width:17%">Dependencia / Empresa</th>
            <th style="width:17%">Correo Electrónico</th>
            <th style="width:7%">Teléfono</th>
            <th style="width:7%">Autoriza Grabación</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="text-align:center;">1</td>
            <td>{{ $acta->user?->name ?? '—' }}</td>
            <td>{{ $acta->user?->document_number ?? '' }}</td>
            <td style="text-align:center;">X</td>
            <td></td>
            <td>SENA</td>
            <td>{{ $acta->user?->email ?? '' }}</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td style="text-align:center;">2</td>
            <td>{{ $acta->docentePar?->name ?? '—' }}</td>
            <td>{{ $acta->docentePar?->document_number ?? '' }}</td>
            <td></td>
            <td style="text-align:center;">X</td>
            <td>{{ $acta->docentePar?->institution_name ?? '' }}</td>
            <td>{{ $acta->docentePar?->email ?? '' }}</td>
            <td></td>
            <td></td>
        </tr>
        @for($i = 0; $i < 3; $i++)
        <tr><td style="height:18px;text-align:center;">{{ $i + 3 }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
        @endfor
    </tbody>
</table>

<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body" style="font-size:8px;color:#444;">
        De acuerdo con La Ley 1581 de 2012, Protección de Datos Personales, el Servicio Nacional de Aprendizaje SENA, se compromete a garantizar la seguridad y protección de los datos personales que se encuentran almacenados en este documento, y les dará el tratamiento correspondiente en cumplimiento de lo establecido legalmente.
    </td></tr>
</table>

<br>

{{-- ── ENCABEZADO OFICIAL DEL ACTA ──────────────────────────────── --}}
<table class="sena-header">
    <tr>
        <td class="h-logo" rowspan="2">
            <img src="{{ $logoSrc ?? asset('images/sena-logo.png') }}" alt="SENA">
        </td>
        <td class="h-title" rowspan="2">
            <div style="font-size:8px;color:#555;margin-bottom:2px;">PROGRAMA ARTICULACIÓN SENA CON LA EDUCACIÓN MEDIA</div>
            <div class="main-title">{{ $acta->tipo_label }}</div>
        </td>
        <td class="h-meta">
            <div><strong>Código:</strong> {{ $acta->tipo_codigo }}</div>
            <div><strong>Versión:</strong> 02</div>
            <div><strong>N° Acta:</strong> {{ $acta->numero_acta }}</div>
        </td>
    </tr>
    <tr>
        <td class="h-meta">
            <div><strong>Estado:</strong> <span class="badge-{{ $acta->estado }}">{{ ucfirst($acta->estado) }}</span></div>
        </td>
    </tr>
</table>

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

{{-- ── CONCLUSIONES ─────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">CONCLUSIONES</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body min">{{ $acta->conclusiones ?? '' }}</td></tr>
</table>

{{-- ── CLASIFICACIÓN DE LA INFORMACIÓN ─────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">CLASIFICACIÓN DE LA INFORMACIÓN</td></tr>
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
