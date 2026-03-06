{{--
    ACTA DE INICIO DE FICHA Y DISTRIBUCIÓN DE TEMARIO
    Tipos: inicio_ficha
--}}
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Acta Inicio {{ $acta->numero_acta }}</title>
    @include('print.partials.acta-styles')
</head>
<body>

{{-- ── REGISTRO DE ASISTENCIA (cabecera tipo SENA) ──────────────── --}}
<table class="sena-header">
    <tr>
        <td class="h-logo" rowspan="2">
            <img src="{{ $logoSrc ?? asset('images/sena-logo.png') }}" alt="SENA">
        </td>
        <td class="h-title" rowspan="2">
            <div style="font-size:8px;color:#555;margin-bottom:2px;">SISTEMA DE GESTIÓN DE LA CALIDAD</div>
            <div style="font-size:9px;font-weight:bold;">REGISTRO DE ASISTENCIA</div>
        </td>
        <td class="h-meta">
            <div><strong>Código:</strong> GD-F-001</div>
            <div><strong>Versión:</strong> 02</div>
        </td>
    </tr>
    <tr>
        <td class="h-meta">
            <div><strong>Fecha:</strong> {{ $acta->fecha->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

<table class="sec" style="margin-top:-1px;">
    <tr>
        <td class="sec-body" colspan="2">
            <strong>DÍA:</strong> {{ $acta->fecha->day }} &nbsp;&nbsp;
            <strong>MES:</strong> {{ $acta->fecha->locale('es')->isoFormat('MMMM') }} &nbsp;&nbsp;
            <strong>AÑO:</strong> {{ $acta->fecha->year }}
        </td>
    </tr>
    <tr>
        <td class="sec-body" colspan="2">
            <strong>OBJETIVO(S):</strong> {{ $acta->objetivo ?? '' }}
        </td>
    </tr>
</table>

{{-- Tabla de registro de asistencia --}}
<table class="data-table" style="margin-top:-1px;font-size:8.5px;">
    <thead>
        <tr>
            <th style="width:4%">No.</th>
            <th style="width:22%">Nombres y Apellidos</th>
            <th style="width:13%">No. Documento</th>
            <th style="width:8%">Planta</th>
            <th style="width:10%">Contratista</th>
            <th style="width:18%">Dependencia / Empresa</th>
            <th style="width:18%">Correo Electrónico</th>
            <th style="width:7%">Teléfono</th>
        </tr>
    </thead>
    <tbody>
        {{-- Instructor SENA --}}
        <tr>
            <td style="text-align:center;">1</td>
            <td>{{ $acta->user?->name ?? '—' }}</td>
            <td>{{ $acta->user?->document_number ?? '' }}</td>
            <td style="text-align:center;">X</td>
            <td></td>
            <td>SENA</td>
            <td>{{ $acta->user?->email ?? '' }}</td>
            <td></td>
        </tr>
        {{-- Docente Par --}}
        <tr>
            <td style="text-align:center;">2</td>
            <td>{{ $acta->docentePar?->name ?? '—' }}</td>
            <td>{{ $acta->docentePar?->document_number ?? '' }}</td>
            <td></td>
            <td style="text-align:center;">X</td>
            <td>{{ $acta->docentePar?->institution_name ?? '' }}</td>
            <td>{{ $acta->docentePar?->email ?? '' }}</td>
            <td></td>
        </tr>
        {{-- Filas vacías para más asistentes --}}
        @for($i = 0; $i < 4; $i++)
        <tr><td style="height:18px;text-align:center;">{{ $i + 3 }}</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
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

{{-- ── CONCLUSIONES ─────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">CONCLUSIONES</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body min">{{ $acta->conclusiones ?? '' }}</td></tr>
</table>

{{-- ── COMPROMISOS ──────────────────────────────────────────────── --}}
@include('print.partials.acta-compromisos', ['acta' => $acta, 'conFirma' => true])

{{-- ── ASISTENTES Y APROBACIÓN ─────────────────────────────────── --}}
@include('print.partials.acta-asistentes', ['acta' => $acta])

{{-- ── BOTONES ──────────────────────────────────────────────────── --}}
@include('print.partials.acta-botones', ['acta' => $acta])

</body>
</html>
