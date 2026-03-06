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
