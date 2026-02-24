<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Asistencia — {{ $group->name }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 12px; color: #111; background: #fff; padding: 24px; }
        h1 { font-size: 17px; font-weight: bold; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #555; }
        .header { border-bottom: 2px solid #111; padding-bottom: 12px; margin-bottom: 14px; }
        .meta-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px 12px; margin-top: 10px; }
        .meta-item { font-size: 11px; }
        .meta-item strong { font-weight: bold; }
        .meta-wide { grid-column: span 2; }
        table { width: 100%; border-collapse: collapse; margin-top: 14px; }
        thead th {
            background: #e8e8e8;
            border: 1px solid #888;
            padding: 5px 7px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        tbody td { border: 1px solid #bbb; padding: 5px 7px; vertical-align: middle; }
        tbody tr:nth-child(even) { background: #fafafa; }
        .col-num  { width: 28px; text-align: center; }
        .col-id   { width: 110px; }
        .col-status { width: 36px; text-align: center; }
        .col-firma { width: 90px; }
        .status-box {
            display: inline-block;
            width: 18px; height: 18px;
            border: 1px solid #888;
            border-radius: 2px;
            text-align: center;
            line-height: 18px;
            font-size: 11px;
            font-weight: bold;
        }
        .present-box  { background: #c8f0d0; }
        .absent-box   { background: #fcd5d5; }
        .justified-box{ background: #fef3c7; }
        .footer {
            margin-top: 36px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .signature-block { text-align: center; }
        .signature-line  { width: 220px; border-top: 1px solid #333; padding-top: 4px; font-size: 11px; color: #444; }
        .legend { font-size: 10px; color: #666; }
        .generated { font-size: 10px; color: #888; text-align: right; }

        .no-print { margin-top: 20px; text-align: center; padding: 12px; background: #f3f4f6; border-radius: 8px; }
        .btn { padding: 8px 20px; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; margin: 0 4px; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-secondary { background: #d1d5db; color: #374151; }

        @@media print {
            body { padding: 12px; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Lista de Asistencia</h1>
    <p class="subtitle">{{ config('app.name') }}</p>
    <div class="meta-grid">
        <div class="meta-item">
            Institución: <strong>{{ $group->institution->name ?? '—' }}</strong>
        </div>
        <div class="meta-item">
            Ficha / Grupo: <strong>{{ $group->ficha_number ? $group->ficha_number . ' — ' : '' }}{{ $group->name }}</strong>
        </div>
        <div class="meta-item">
            Fecha: <strong>{{ \Carbon\Carbon::parse($date)->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</strong>
        </div>
        @if($group->programaFormacion)
        <div class="meta-item">
            Programa: <strong>{{ $group->programaFormacion->name }}</strong>
            @if($group->programaFormacion->code)
                ({{ $group->programaFormacion->code }})
            @endif
        </div>
        @endif
        @if($competencia)
        <div class="meta-item meta-wide">
            Competencia: <strong>{{ $competencia->code ? '['.$competencia->code.'] ' : '' }}{{ $competencia->name }}</strong>
            @if($competencia->total_hours)
                — <em>{{ $competencia->total_hours }} horas</em>
            @endif
        </div>
        @endif
        <div class="meta-item">
            Total aprendices: <strong>{{ $students->count() }}</strong>
        </div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th class="col-num">#</th>
            <th class="col-id">Identificación</th>
            <th>Nombre del Aprendiz</th>
            <th class="col-status" title="Presente">P</th>
            <th class="col-status" title="Ausente">A</th>
            <th class="col-status" title="Justificado">J</th>
            <th class="col-firma">Firma</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $i => $student)
        @php $status = $attendanceMap[$student->id] ?? null; @endphp
        <tr>
            <td class="col-num">{{ $i + 1 }}</td>
            <td class="col-id">{{ $student->identifier ?: '—' }}</td>
            <td>{{ $student->name }}</td>
            <td class="col-status">
                <span class="status-box {{ $status === 'present' ? 'present-box' : '' }}">
                    {{ $status === 'present' ? '✓' : '' }}
                </span>
            </td>
            <td class="col-status">
                <span class="status-box {{ $status === 'absent' ? 'absent-box' : '' }}">
                    {{ $status === 'absent' ? '✗' : '' }}
                </span>
            </td>
            <td class="col-status">
                <span class="status-box {{ $status === 'justified' ? 'justified-box' : '' }}">
                    {{ $status === 'justified' ? 'J' : '' }}
                </span>
            </td>
            <td class="col-firma"></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="footer">
    <div class="signature-block">
        <div class="signature-line">Firma del Instructor</div>
    </div>
    <div>
        <div class="legend">P = Presente &nbsp;·&nbsp; A = Ausente &nbsp;·&nbsp; J = Justificado</div>
        <div class="generated">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<div class="no-print">
    <button class="btn btn-primary" onclick="window.print()">🖨 Imprimir / Guardar PDF</button>
    <button class="btn btn-secondary" onclick="window.close()">Cerrar</button>
</div>

</body>
</html>
