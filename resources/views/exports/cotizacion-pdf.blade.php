<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Proforma {{ $cotizacion->codigo }}</title>
    <style>
        @page { margin: 30px 42px 70px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #171717; }
        table { border-collapse: collapse; width: 100%; }
        .header { margin-bottom: 24px; }
        .header td { border: 0; vertical-align: middle; }
        .logo { max-width: 205px; max-height: 102px; }
        .title { text-align: right; color: #0b2e4c; }
        .number { color: #d83d42; font-size: 14px; font-weight: bold; margin-bottom: 8px; word-wrap: break-word; }
        h1 { margin: 0; font-size: 37px; line-height: 1.2; }
        .parties { margin-bottom: 42px; table-layout: fixed; }
        .parties td { vertical-align: top; padding-right: 18px; word-wrap: break-word; }
        h2 { color: #0b2e4c; margin: 0 0 8px; font-size: 14px; }
        .line { margin: 0 0 3px; }
        .date { font-size: 14px; font-weight: bold; margin-bottom: 16px; }
        .items { table-layout: fixed; }
        .items th { background: #0b2d50; color: white; padding: 16px 6px; font-size: 10px; }
        .items td { padding: 12px 8px; vertical-align: middle; word-wrap: break-word; }
        .items th, .items td { border: 1px solid #333; }
        .items thead { display: table-header-group; }
        .items tr { page-break-inside: avoid; }
        .product-photo { max-width: 48px; max-height: 48px; }
        .items td.photo-cell { padding: 6px; }
        .center { text-align: center; }
        .money { text-align: right; }
        .muted { font-size: 8px; color: #555; margin-top: 4px; }
        .totals { margin-left: auto; width: 48%; page-break-inside: avoid; }
        .totals td { border: 1px solid #333; padding: 12px 8px; font-weight: bold; }
        .totals .grand-total td { background: #0b2d50; color: white; }
        .conditions { margin-top: 18px; font-size: 9px; }
    </style>
</head>
<body>
    @php
        $empresa = $cotizacion->empresa;
        $cliente = $cotizacion->cliente;
        $moneda = $cotizacion->moneda ?? 'BOB';
        $numero = fn ($valor) => number_format((float) $valor, 2, ',', '.');
        $total = $cotizacion->detalles->sum('total');
        $impuesto = $cotizacion->detalles->sum('impuesto');
        $descuento = $cotizacion->detalles->sum('descuento');
        // El subtotal guardado de cada línea ya incluye el descuento.
        $subtotal = $cotizacion->detalles->sum('subtotal') + $descuento;
        $vencimiento = $cotizacion->fecha_validez ?? $cotizacion->fecha_emision?->copy()->addDays(7);
    @endphp
    <table class="header">
        <tr>
            <td style="width: 42%">
                @if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo del sistema">@endif
            </td>
            <td class="title">
                <div class="number">N° {{ $cotizacion->codigo }}</div>
                <h1>PROFORMA</h1>
            </td>
        </tr>
    </table>
    <table class="parties">
        <tr>
            <td style="width: 58%">
                <h2>Datos de la empresa</h2>
                <div class="line"><strong>Nombre:</strong> {{ $empresa?->nombre_comercial ?: $empresa?->razon_social ?: 'Sin empresa' }}</div>
                <div class="line"><strong>Sucursal:</strong> {{ $cotizacion->sucursal?->nombre ?: 'Sin sucursal asignada' }}</div>
                <div class="line"><strong>NIT:</strong> {{ $empresa?->nit ?: 'No registrado' }}</div>
                <div class="line"><strong>Teléfono:</strong> {{ implode(' - ', array_filter([$empresa?->telefono, $empresa?->celular])) ?: 'No registrado' }}</div>
                <div class="line"><strong>Correo:</strong> {{ $empresa?->email ?: 'No registrado' }}</div>
            </td>
            <td>
                <h2>Datos del cliente</h2>
                <div class="line"><strong>Nombre:</strong> {{ $cliente?->nombre ?: 'Sin cliente' }}</div>
                <div class="line"><strong>Teléfono:</strong> {{ $cliente?->celular ?: $cliente?->telefono ?: 'No registrado' }}</div>
            </td>
        </tr>
    </table>
    <div class="date">Fecha: {{ $cotizacion->fecha_emision?->format('d/m/Y') ?: 'No registrada' }}<br>
        <span style="font-size: 11px">Vencimiento de la cotización: {{ $vencimiento?->format('d/m/Y') ?: 'No registrado' }}</span>
    </div>
    <table class="items">
        <thead><tr>
            <th style="width: 14%">Foto</th>
            <th style="width: 46%">Descripción</th>
            <th style="width: 12%">Cantidad</th>
            <th style="width: 14%">Precio</th>
            <th style="width: 14%">Total</th>
        </tr></thead>
        <tbody>
            @forelse($cotizacion->detalles as $detalle)
                <tr>
                    <td class="center photo-cell">
                        @if($fotos[$detalle->id] ?? null)
                            <img class="product-photo" src="{{ $fotos[$detalle->id] }}" alt="Foto del producto">
                        @else
                            <span class="muted">Sin foto</span>
                        @endif
                    </td>
                    <td>{{ $detalle->descripcion_articulo ?: $detalle->articulo?->nombre_comercial }}
                        @if((float) $detalle->descuento > 0)<div class="muted">Descuento: {{ $numero($detalle->descuento) }} {{ $moneda }}</div>@endif
                        @if((float) $detalle->impuesto > 0)<div class="muted">Impuesto incluido en total: {{ $numero($detalle->impuesto) }} {{ $moneda }}</div>@endif
                    </td>
                    <td class="center">{{ rtrim(rtrim(number_format((float) $detalle->cantidad, 2, ',', '.'), '0'), ',') }}</td>
                    <td class="money">{{ $numero($detalle->precio_unitario) }}</td>
                    <td class="money">{{ $numero($detalle->total) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="center">Sin conceptos registrados</td></tr>
            @endforelse
        </tbody>
    </table>
    <table class="totals">
        <tr><td style="width: 65%">Subtotal ({{ $moneda }})</td><td class="money">{{ $numero($subtotal) }}</td></tr>
        <tr><td>Impuesto ({{ $moneda }})</td><td class="money">{{ $numero($impuesto) }}</td></tr>
        <tr><td>Descuento ({{ $moneda }})</td><td class="money">{{ $numero($descuento) }}</td></tr>
        <tr class="grand-total"><td>TOTAL ({{ $moneda }})</td><td class="money">{{ $numero($total) }}</td></tr>
    </table>
    @if(filled(trim($cotizacion->observaciones ?? '')))<div class="conditions"><strong>Observaciones:</strong><br>{!! nl2br(e($cotizacion->observaciones)) !!}</div>@endif
</body>
</html>
