<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $acta->tipo_codigo }} — Acta {{ $acta->numero_acta }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10.5px;
            color: #000;
            background: #fff;
            padding: 18px 22px;
        }

        /* ── Encabezado oficial SENA ─────────────────────────────── */
        .sena-header {
            width: 100%;
            border-collapse: collapse;
            border: 1.5px solid #000;
            margin-bottom: 0;
        }
        .sena-header td {
            border: 1px solid #000;
            padding: 4px 7px;
            vertical-align: middle;
        }
        .h-logo { width: 90px; text-align: center; padding: 6px; }
        .h-logo img { width: 72px; height: auto; }
        .h-title {
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            line-height: 1.4;
        }
        .h-title .main-title {
            font-size: 11.5px;
            color: #1a5c1a;
        }
        .h-meta { width: 130px; font-size: 9.5px; }
        .h-meta div { margin-bottom: 2px; }
        .h-meta strong { font-weight: bold; }

        /* ── Título de sección ───────────────────────────────────── */
        .sec {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
        }
        .sec td { border: 1px solid #000; }
        .sec-head {
            background: #c6e0b4;
            font-weight: bold;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 3px 7px;
            color: #1a3a1a;
        }
        .sec-body { padding: 5px 7px; font-size: 10.5px; line-height: 1.5; }
        .sec-body.min { min-height: 38px; }
        .sec-body.tall { min-height: 60px; white-space: pre-wrap; }

        /* ── Grilla de campos ────────────────────────────────────── */
        .fields {
            width: 100%;
            border-collapse: collapse;
        }
        .fields td {
            border: 1px solid #000;
            padding: 3px 7px;
            font-size: 10px;
            vertical-align: top;
        }
        .lbl {
            font-weight: bold;
            color: #1a3a1a;
            font-size: 9px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .val { font-size: 10.5px; }

        /* ── Tabla datos ─────────────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #c6e0b4;
            border: 1px solid #000;
            padding: 3px 7px;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            color: #1a3a1a;
        }
        .data-table td {
            border: 1px solid #000;
            padding: 4px 7px;
            font-size: 10.5px;
            vertical-align: top;
        }
        .data-table tr:nth-child(even) td { background: #f5f5f5; }

        /* ── Firmas ──────────────────────────────────────────────── */
        .firma-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .firma-table td {
            border: 1px solid #000;
            padding: 8px 12px 6px;
            width: 50%;
            vertical-align: bottom;
            text-align: center;
        }
        .firma-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 4px;
            font-size: 10px;
        }
        .firma-cc   { font-size: 9.5px; color: #444; margin-top: 2px; }
        .firma-role { font-size: 9px; color: #1a3a1a; font-weight: bold; text-transform: uppercase; margin-top: 1px; }

        /* ── Estado badge ────────────────────────────────────────── */
        .badge-borrador   { color: #92400e; font-weight: bold; }
        .badge-finalizada { color: #065f46; font-weight: bold; }

        /* ── Botones (solo pantalla) ─────────────────────────────── */
        .no-print {
            margin-top: 22px; text-align: center;
            padding: 12px; background: #f3f4f6; border-radius: 8px;
        }
        .btn {
            padding: 8px 20px; border: none; border-radius: 6px;
            cursor: pointer; font-size: 13px; margin: 0 4px;
            text-decoration: none; display: inline-block;
        }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-green   { background: #059669; color: #fff; }
        .btn-red     { background: #dc2626; color: #fff; }
        .btn-gray    { background: #d1d5db; color: #374151; }

        @@media print {
            body { padding: 8px 12px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

{{-- ── ENCABEZADO OFICIAL SENA ───────────────────────────────────── --}}
<table class="sena-header">
    <tr>
        <td class="h-logo" rowspan="2">
            <img src="{{ $logoSrc ?? asset('images/sena-logo.png') }}" alt="SENA">
        </td>
        <td class="h-title" rowspan="2">
            <div style="font-size:8.5px;color:#555;margin-bottom:3px;">SISTEMA DE GESTIÓN DE LA CALIDAD</div>
            <div class="main-title">{{ $acta->tipo_label }}</div>
        </td>
        <td class="h-meta">
            <div><strong>Código:</strong> {{ $acta->tipo_codigo }}</div>
            <div><strong>Versión:</strong> 02</div>
            <div><strong>Fecha:</strong> {{ $acta->fecha->format('d/m/Y') }}</div>
        </td>
    </tr>
    <tr>
        <td class="h-meta">
            <div><strong>N° Acta:</strong> {{ $acta->numero_acta }}</div>
            <div><strong>Estado:</strong>
                <span class="badge-{{ $acta->estado }}">{{ ucfirst($acta->estado) }}</span>
            </div>
        </td>
    </tr>
</table>

{{-- ── DATOS GENERALES ──────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">1. Datos Generales</td></tr>
</table>
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td style="width:18%"><span class="lbl">Fecha</span><br><span class="val">{{ $acta->fecha->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span></td>
        <td style="width:28%"><span class="lbl">Lugar</span><br><span class="val">{{ $acta->lugar }}</span></td>
        <td style="width:14%"><span class="lbl">Hora Inicio</span><br><span class="val">{{ $acta->hora_inicio ?? '—' }}</span></td>
        <td style="width:14%"><span class="lbl">Hora Fin</span><br><span class="val">{{ $acta->hora_fin ?? '—' }}</span></td>
        <td style="width:26%"><span class="lbl">Instructor SENA</span><br><span class="val">{{ $acta->user?->name ?? '—' }}</span></td>
    </tr>
</table>

{{-- ── FICHAS / GRUPOS ──────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">2. Fichas / Grupos Articulación</td></tr>
</table>
<table class="data-table" style="margin-top:-1px;">
    <thead>
        <tr>
            <th>Ficha #</th>
            <th>Programa de Formación</th>
            <th>Grado / Grupo</th>
            <th>Institución Educativa</th>
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

{{-- ── DOCENTE PAR ──────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">3. Docente Par / Institución de Articulación</td></tr>
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
        <td colspan="4"><span class="lbl">Correo electrónico</span> &nbsp;<span class="val">{{ $acta->docentePar->email }}</span></td>
    </tr>
    @endif
</table>

@if($acta->competencia)
<table class="fields" style="margin-top:-1px;">
    <tr>
        <td><span class="lbl">Competencia</span><br>
            <span class="val">{{ $acta->competencia->code ? '[' . $acta->competencia->code . '] ' : '' }}{{ $acta->competencia->name }}</span>
        </td>
    </tr>
</table>
@endif

{{-- ── AGENDA ───────────────────────────────────────────────────── --}}
@if($acta->agenda)
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">4. Agenda / Orden del Día</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body">{{ $acta->agenda }}</td></tr>
</table>
@endif

{{-- ── OBJETIVO ─────────────────────────────────────────────────── --}}
@if($acta->objetivo)
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">5. Objetivo</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body">{{ $acta->objetivo }}</td></tr>
</table>
@endif

{{-- ── DESARROLLO ───────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">{{ $acta->objetivo ? '6' : '5' }}. Desarrollo</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body tall">{{ $acta->desarrollo ?? '' }}</td></tr>
</table>

{{-- ── COMPROMISOS (tabla estructurada) ───────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">Compromisos / Acuerdos</td></tr>
</table>
<table class="data-table" style="margin-top:-1px;">
    <thead>
        <tr>
            <th style="width:55%">Actividad / Compromiso</th>
            <th style="width:25%">Responsable</th>
            <th style="width:20%">Fecha Límite</th>
        </tr>
    </thead>
    <tbody>
        @forelse($acta->compromisoItems as $c)
        <tr>
            <td>{{ $c->descripcion }}</td>
            <td>{{ $c->responsable_label }}</td>
            <td style="text-align:center;">
                {{ $c->fecha_limite ? $c->fecha_limite->format('d/m/Y') : '—' }}
            </td>
        </tr>
        @empty
        <tr>
            <td style="height:24px;"></td><td></td><td></td>
        </tr>
        <tr>
            <td style="height:24px;"></td><td></td><td></td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- ── OBSERVACIONES ────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">Observaciones</td></tr>
</table>
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-body min">{{ $acta->observaciones ?? '' }}</td></tr>
</table>

{{-- ── FIRMAS ───────────────────────────────────────────────────── --}}
<table class="sec" style="margin-top:-1px;">
    <tr><td class="sec-head">Firmas de Asistentes</td></tr>
</table>
<table class="firma-table" style="margin-top:-1px;">
    <tr>
        <td>
            <div class="firma-line">{{ $acta->user?->name ?? 'Instructor' }}</div>
            @if($acta->user?->document_number ?? false)
            <div class="firma-cc">C.C. {{ $acta->user->document_number }}</div>
            @endif
            <div class="firma-role">Instructor de Formación SENA</div>
        </td>
        <td>
            <div class="firma-line">{{ $acta->docentePar?->name ?? '—' }}</div>
            @if($acta->docentePar?->document_number)
            <div class="firma-cc">C.C. {{ $acta->docentePar->document_number }}</div>
            @endif
            <div class="firma-role">{{ $acta->docentePar?->position ?? 'Docente Par' }}</div>
        </td>
    </tr>
</table>

<div style="text-align:right;font-size:9px;color:#888;margin-top:8px;">
    Generado: {{ now()->format('d/m/Y H:i') }} &nbsp;|&nbsp; {{ config('app.name') }}
</div>

{{-- ── Botones (solo pantalla) ─────────────────────────────────── --}}
<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">Imprimir / Guardar PDF</button>
    <a href="{{ route('actas.export.word', $acta->id) }}" class="btn btn-green">Descargar Word</a>
    <a href="{{ route('actas.export.pdf',  $acta->id) }}" class="btn btn-red">Descargar PDF</a>
    <button class="btn btn-gray" onclick="window.close()">Cerrar</button>
</div>

</body>
</html>
