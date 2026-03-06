<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 8pt; color: #222; }

        /* ─── Encabezado ─── */
        .header { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
        .header .logo-cell { width: 65px; text-align: center; }
        .header .title-cell { text-align: center; }
        .header .title-cell .sgc { font-size: 7pt; color: #555; }
        .header .title-cell h1 { font-size: 12pt; font-weight: bold; color: #1A5C1A; text-transform: uppercase; }

        /* ─── Info general ─── */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .info-table td { border: 1px solid #000; padding: 4px 6px; }
        .lbl { font-size: 7pt; font-weight: bold; color: #1A3A1A; text-transform: uppercase; display: block; }
        .val { font-size: 8.5pt; display: block; }

        /* ─── Tabla de calificaciones ─── */
        .grades-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        .grades-table th,
        .grades-table td { border: 1px solid #000; padding: 3px 5px; }
        .grades-table thead th { background-color: #C6E0B4; font-size: 7pt; font-weight: bold; text-align: center; }
        .grades-table thead .th-sub { background-color: #E8F4E8; font-size: 6.5pt; text-align: center; }
        .grades-table tbody td { font-size: 8pt; text-align: center; }
        .grades-table tbody .td-name { text-align: left; font-size: 8pt; }
        .aprobado    { color: #1A7A1A; font-weight: bold; }
        .no-aprobado { color: #CC0000; font-weight: bold; }

        /* ─── Firma ─── */
        .firma-block { margin-top: 24px; }
        .firma-line  { display: inline-block; width: 220px; border-bottom: 1px solid #000; }
        .firma-label { font-size: 7.5pt; font-weight: bold; margin-top: 3px; }

        /* ─── Leyenda ─── */
        .leyenda { font-size: 7pt; color: #555; margin-top: 6px; }
    </style>
</head>
<body>

    {{-- Encabezado SENA --}}
    <table class="header">
        <tr>
            <td class="logo-cell">
                @if(file_exists(public_path('images/sena-logo.png')))
                    <img src="{{ $logoSrc }}" width="52" height="52" alt="SENA">
                @else
                    <strong style="color:#1A5C1A;font-size:11pt;">SENA</strong>
                @endif
            </td>
            <td class="title-cell">
                <div class="sgc">SISTEMA DE GESTIÓN DE LA CALIDAD</div>
                <h1>Registro de Calificaciones</h1>
            </td>
        </tr>
    </table>

    {{-- Información general --}}
    <table class="info-table">
        <tr>
            <td style="width:33%">
                <span class="lbl">Institución</span>
                <span class="val">{{ $group->institution?->name ?? '—' }}</span>
            </td>
            <td style="width:33%">
                <span class="lbl">Grupo / Ficha</span>
                <span class="val">{{ $group->name }}{{ $group->ficha_number ? ' (' . $group->ficha_number . ')' : '' }}</span>
            </td>
            <td style="width:34%">
                <span class="lbl">Programa de Formación</span>
                <span class="val">{{ $group->programaFormacion?->name ?? '—' }}</span>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <span class="lbl">Competencia</span>
                <span class="val">{{ $competencia->code ? '[' . $competencia->code . '] ' : '' }}{{ $competencia->name }}</span>
            </td>
            <td>
                <span class="lbl">Fecha de impresión</span>
                <span class="val">{{ now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
            </td>
        </tr>
    </table>

    {{-- Tabla de calificaciones --}}
    <table class="grades-table">
        <thead>
            <tr>
                <th rowspan="2" style="text-align:left; width:140px;">Aprendiz</th>
                <th rowspan="2" style="width:75px;">Identificación</th>
                @foreach($ras as $ra)
                    <th colspan="2">
                        {{ $ra->code ? '[' . $ra->code . '] ' : '' }}{{ mb_strlen($ra->name) > 38 ? mb_substr($ra->name, 0, 38) . '…' : $ra->name }}
                    </th>
                @endforeach
                <th rowspan="2" style="width:45px;">Prom.</th>
                <th rowspan="2" style="width:110px; text-align:left;">Observación</th>
            </tr>
            <tr>
                @foreach($ras as $ra)
                    <th class="th-sub" style="width:38px;">Nota</th>
                    <th class="th-sub" style="width:48px;">Equiv.</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($students as $student)
                @php
                    $notasStudent = [];
                    $obsStudent   = null;
                    foreach ($ras as $ra) {
                        $cal = $calificaciones[$student->id . '_' . $ra->id] ?? null;
                        if ($cal?->nota !== null) {
                            $notasStudent[] = $cal->nota;
                        }
                        if (! $obsStudent && $cal?->observacion) {
                            $obsStudent = $cal->observacion;
                        }
                    }
                    $promedio = count($notasStudent) > 0
                        ? array_sum($notasStudent) / count($notasStudent)
                        : null;
                @endphp
                <tr>
                    <td class="td-name">{{ $student->name }}</td>
                    <td>{{ $student->identifier ?? '' }}</td>

                    @foreach($ras as $ra)
                        @php
                            $cal  = $calificaciones[$student->id . '_' . $ra->id] ?? null;
                            $nota = $cal?->nota;
                        @endphp
                        <td>{{ $nota !== null ? number_format($nota, 1) : '—' }}</td>
                        <td>
                            @if($nota !== null)
                                <span class="{{ $nota >= 3.0 ? 'aprobado' : 'no-aprobado' }}">
                                    {{ $nota >= 3.0 ? 'AP' : 'NA' }}
                                </span>
                            @else
                                <span style="color:#aaa">—</span>
                            @endif
                        </td>
                    @endforeach

                    <td>
                        @if($promedio !== null)
                            <span class="{{ $promedio >= 3.0 ? 'aprobado' : 'no-aprobado' }}">
                                {{ number_format($promedio, 1) }}
                            </span>
                        @else
                            <span style="color:#aaa">—</span>
                        @endif
                    </td>

                    <td style="text-align:left; font-size:7pt;">{{ $obsStudent ?? '' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Leyenda --}}
    <div class="leyenda">
        <strong>AP</strong> = APROBADO (nota ≥ 3.0) &nbsp;|&nbsp;
        <strong>NA</strong> = NO APROBADO (nota &lt; 3.0) &nbsp;|&nbsp;
        Escala de calificación: 1.0 – 5.0
    </div>

    {{-- Firma --}}
    <div class="firma-block">
        <div class="firma-line"></div>
        <div class="firma-label">INSTRUCTOR SENA</div>
    </div>

</body>
</html>
