<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inventario {{ $inventario->codigo }}</title>
    <style>
        @page { margin: 28px 34px 44px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #243447; line-height: 1.4; }
        h1 { font-size: 20px; margin: 0 0 4px; color: #173b59; }
        h2 { font-size: 13px; margin: 18px 0 8px; color: #173b59; page-break-after: avoid; }
        h3 { font-size: 10px; margin: 12px 0 4px; page-break-after: avoid; }
        p { margin: 4px 0 8px; overflow-wrap: break-word; word-wrap: break-word; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th { background: #173b59; color: white; font-weight: bold; text-align: left; padding: 7px 5px; }
        td { padding: 6px 5px; vertical-align: top; border-bottom: 1px solid #dce3e9; word-wrap: break-word; }
        .header td { border: 0; padding: 0 0 12px; }
        .logo { max-width: 125px; max-height: 65px; }
        .muted { color: #617284; }
        .right { text-align: right; }
        .number { text-align: right; white-space: nowrap; }
        .small { font-size: 8px; }
        .metadata td { background: #f2f6f9; border: 3px solid white; padding: 8px; }
        .label { font-size: 8px; color: #617284; display: block; margin-bottom: 3px; }
        .stats td { border: 1px solid #dce3e9; padding: 9px; text-align: center; }
        .stat { font-size: 17px; font-weight: bold; color: #173b59; }
        .negative { color: #b42318; font-weight: bold; }
        .positive { color: #986800; font-weight: bold; }
        .pending { color: #617284; font-style: italic; }
        .notice { padding: 8px 10px; background: #f2f6f9; border-left: 3px solid #648baa; }
        .detail { border-top: 1px solid #dce3e9; padding-top: 4px; }
        .signatures { margin-top: 28px; page-break-inside: avoid; }
        .signatures td { text-align: center; border: 0; padding: 24px 12px 0; }
        .signature-line { border-top: 1px solid #738294; padding-top: 5px; }
        .section-break { page-break-before: always; }
        .stripe { background: #f8fafc; }
        .audit p { margin: 2px 0; }
    </style>
</head>
<body>
@php
    $empresa = $inventario->empresa;
    $total = $inventario->conteos_count;
    $contados = $inventario->contados_count;
    $registrados = $inventario->conteos->filter(fn ($linea) => $linea->cantidad_contada !== null || $linea->observaciones || $linea->nota_revision);
    $cantidad = fn ($valor) => $valor === null ? 'Pendiente' : rtrim(rtrim(number_format((float) $valor, 6, '.', ','), '0'), '.');
    $acciones = ['programado' => 'Programación', 'iniciado' => 'Inicio del conteo', 'conteo' => 'Conteo', 'reconteo' => 'Reconteo', 'enviado_revision' => 'Envío a revisión', 'revisado' => 'Revisión', 'cerrar' => 'Cierre', 'devolver' => 'Devolución a conteo', 'cancelar' => 'Cancelación'];
@endphp
<table class="header">
    <tr>
        <td style="width: 19%">@if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo de empresa">@endif</td>
        <td style="width: 52%">
            <h1>Inventario físico</h1>
            <strong>{{ $empresa?->razon_social ?: $empresa?->nombre_comercial }}</strong><br>
            @if($empresa?->nombre_comercial && $empresa->nombre_comercial !== $empresa->razon_social){{ $empresa->nombre_comercial }}<br>@endif
            NIT: {{ $empresa?->nit ?: 'No registrado' }}<br>
            <span class="muted">{{ collect([$empresa?->direccion, $empresa?->ciudad, $empresa?->pais])->filter()->implode(' · ') }}</span><br>
            <span class="muted">{{ collect([$empresa?->telefono ?: $empresa?->celular, $empresa?->email])->filter()->implode(' · ') }}</span>
        </td>
        <td class="right" style="width: 29%">
            <strong>{{ $inventario->codigo }}</strong><br>
            {{ \App\Models\Inventario\InventarioFisico::ESTADOS[$inventario->estado] }}<br>
            <span class="muted">Emitido: {{ $generadoEl->format('d/m/Y H:i') }}<br>Por: {{ $generadoPor }}</span>
        </td>
    </tr>
</table>
<table class="metadata">
    <tr>
        <td><span class="label">SUCURSAL</span>{{ $inventario->sucursal?->nombre }}<br>{{ $inventario->sucursal?->direccion }}</td>
        <td><span class="label">ALMACÉN</span>{{ $inventario->almacen?->codigo }} · {{ $inventario->almacen?->nombre }}<br>{{ $inventario->almacen?->direccion }}</td>
        <td><span class="label">RESPONSABLE</span>{{ $inventario->responsable?->name }}</td>
    </tr>
    <tr>
        <td><span class="label">FECHA PROGRAMADA</span>{{ $inventario->fecha_programada->format('d/m/Y') }}</td>
        <td><span class="label">REFERENCIA TOMADA AL INICIAR</span>{{ $inventario->iniciado_at?->format('d/m/Y H:i') ?: 'Aún no iniciado' }}</td>
        <td><span class="label">FINALIZACIÓN</span>{{ $inventario->cerrado_at?->format('d/m/Y H:i') ?: 'Inventario abierto' }}</td>
    </tr>
</table>
<h2>Resumen de avance</h2>
<table class="stats"><tr>
    <td><span class="stat">{{ $total }}</span><br>Artículos</td>
    <td><span class="stat">{{ $contados }}</span><br>Contados</td>
    <td><span class="stat">{{ $total - $contados }}</span><br>Pendientes</td>
    <td><span class="stat">{{ $inventario->progreso }}%</span><br>Avance del conteo</td>
    <td><span class="stat">{{ $inventario->revisados_count }}</span><br>Revisados</td>
    <td><span class="stat">{{ $inventario->diferencias_count }}</span><br>Con diferencias</td>
</tr></table>
@if($inventario->observaciones)<h3>Alcance e instrucciones</h3><p style="white-space: pre-line">{{ $inventario->observaciones }}</p>@endif
<p class="notice">{{ $inventario->estado === 'cerrado' ? 'Inventario cerrado.' : 'Reporte del estado actual de la sesión; no constituye un cierre de auditoría.' }}
    Referencia = stock físico al iniciar. Incluye unidades reservadas. Diferencia = contado − referencia. Un conteo pendiente no equivale a cero. El cierre no aplica ajustes de stock; estos se registran por Kardex.</p>
<h2>Detalle de existencias y conteos</h2>
<table>
    <thead><tr>
        <th style="width: 4%">N.º</th><th style="width: 13%">Código</th><th style="width: 28%">Artículo</th>
        <th style="width: 7%">Unidad</th><th class="right" style="width: 10%">Referencia</th>
        <th class="right" style="width: 9%">Reservado</th><th class="right" style="width: 9%">Contado</th>
        <th class="right" style="width: 9%">Diferencia</th><th style="width: 11%">Revisión</th>
    </tr></thead>
    <tbody>
    @forelse($inventario->conteos as $linea)
        <tr class="{{ $loop->even ? 'stripe' : '' }}">
            <td>{{ $loop->iteration }}</td><td>{{ $linea->codigo }}</td><td>{{ $linea->nombre }}</td><td>{{ $linea->unidad ?: '—' }}</td>
            <td class="number">{{ $cantidad($linea->stock_sistema) }}</td><td class="number">{{ $cantidad($linea->stock_reservado) }}</td>
            <td class="number {{ $linea->cantidad_contada === null ? 'pending' : '' }}">{{ $cantidad($linea->cantidad_contada) }}</td>
            <td class="number {{ $linea->diferencia < 0 ? 'negative' : ($linea->diferencia > 0 ? 'positive' : '') }}">{{ $cantidad($linea->diferencia) }}</td>
            <td>{{ $linea->revisado_at ? 'Revisado' : ($linea->cantidad_contada === null ? 'Sin conteo' : 'Por revisar') }}</td>
        </tr>
    @empty
        <tr><td colspan="9">No hay artículos preparados. Inicie el inventario para generar el stock de referencia.</td></tr>
    @endforelse
    </tbody>
</table>
@if($registrados->isNotEmpty())
<h2 class="section-break">Registro de conteos y conclusiones</h2>
@foreach($registrados as $linea)
    <h3 class="detail">{{ $linea->codigo }} · {{ $linea->nombre }}</h3>
    <p class="small">Contado por: {{ $linea->contador?->name ?: 'Pendiente' }} · Fecha: {{ $linea->contado_at?->format('d/m/Y H:i') ?: '—' }} · Versión: {{ $linea->version }}<br>
        Revisado por: {{ $linea->revisor?->name ?: 'Pendiente' }} · Fecha: {{ $linea->revisado_at?->format('d/m/Y H:i') ?: '—' }}</p>
    @if($linea->codigo_leido)<p><strong>QR / barras leído:</strong> {{ $linea->codigo_leido }}</p>@endif
    @if($linea->observaciones)<p style="white-space: pre-line"><strong>Observaciones:</strong> {{ $linea->observaciones }}</p>@endif
    @if($linea->nota_revision)<p style="white-space: pre-line"><strong>Conclusión:</strong> {{ $linea->nota_revision }}</p>@endif
@endforeach
@endif
<div class="audit">
<h2>Bitácora de auditoría</h2>
@forelse($inventario->eventos as $evento)
    <h3 class="detail">{{ $evento->created_at->format('d/m/Y H:i:s') }} · {{ $acciones[$evento->accion] ?? $evento->accion }} · {{ $evento->usuario?->name }}</h3>
    @if($evento->conteo)<p><strong>Artículo:</strong> {{ $evento->conteo->codigo }}</p>@endif
    @if($evento->motivo)<p style="white-space: pre-line"><strong>Motivo / conclusión:</strong> {{ $evento->motivo }}</p>@endif
    @foreach(['Antes' => $evento->antes, 'Después' => $evento->despues] as $etiqueta => $valores)
        @if($valores)
            <p class="small"><strong>{{ $etiqueta }}:</strong></p>
            @foreach($valores as $campo => $valor)
                <p class="small">{{ str_replace('_', ' ', ucfirst($campo)) }}: {{ is_array($valor) ? json_encode($valor, JSON_UNESCAPED_UNICODE) : ($valor ?? 'Sin registro') }}</p>
            @endforeach
        @endif
    @endforeach
@empty
    <p>No hay eventos registrados.</p>
@endforelse
</div>
<div class="signatures">
    <h2>Firmas de conformidad</h2>
    <table><tr>
        <td><div class="signature-line">{{ $inventario->responsable?->name }}<br>Responsable del inventario</div></td>
        <td><div class="signature-line">Nombre, firma y sello<br>Revisor / auditor</div></td>
        <td><div class="signature-line">Nombre, firma y sello<br>Encargado del almacén</div></td>
    </tr></table>
</div>
</body>
</html>
