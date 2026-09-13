<div class="sia-cliente-comercial" wire:poll.60s>
    @if (! $resumen)
        <div class="sia-cliente-comercial-empty">
            <x-filament::icon icon="heroicon-o-chart-bar" />
            <div>
                <strong>Información comercial disponible al guardar</strong>
                <p>Registre el cliente para consultar su facturación, cartera y pedidos.</p>
            </div>
        </div>
    @else
        <div class="sia-cliente-comercial-heading">
            <div>
                <h3>Información comercial</h3>
                <p>Resumen vivo de facturación, cobros y compromisos del cliente.</p>
            </div>
            <span class="sia-cliente-comercial-live"><x-filament::icon icon="heroicon-m-arrow-path" /> Actualizado en vivo</span>
        </div>

        <div class="sia-cliente-comercial-grid">
            <article class="sia-cliente-metric sia-cliente-metric-primary">
                <span class="sia-cliente-metric-icon"><x-filament::icon icon="heroicon-o-banknotes" /></span>
                <p>Facturado acumulado</p>
                <strong>Bs {{ number_format($resumen['facturado'], 2, ',', '.') }}</strong>
                <small>{{ number_format($resumen['facturas']) }} factura(s) emitida(s)</small>
            </article>
            <article class="sia-cliente-metric sia-cliente-metric-success">
                <span class="sia-cliente-metric-icon"><x-filament::icon icon="heroicon-o-credit-card" /></span>
                <p>Cobrado confirmado</p>
                <strong>Bs {{ number_format($resumen['cobrado'], 2, ',', '.') }}</strong>
                <small>{{ $resumen['cobrado'] >= $resumen['facturado'] && $resumen['facturado'] > 0 ? 'Cartera cobrada por completo' : 'Pagos conciliados' }}</small>
            </article>
            <article @class(['sia-cliente-metric', $resumen['saldo'] > 0 ? 'sia-cliente-metric-warning' : 'sia-cliente-metric-success'])>
                <span class="sia-cliente-metric-icon"><x-filament::icon :icon="$resumen['saldo'] > 0 ? 'heroicon-o-wallet' : 'heroicon-o-check-badge'" /></span>
                <p>Saldo pendiente</p>
                <strong>Bs {{ number_format($resumen['saldo'], 2, ',', '.') }}</strong>
                <small>{{ $resumen['saldo'] > 0 ? 'Importe aún por cobrar' : 'Sin saldo pendiente' }}</small>
            </article>
            <article @class(['sia-cliente-metric', $resumen['pedidos'] > 0 ? 'sia-cliente-metric-info' : 'sia-cliente-metric-neutral'])>
                <span class="sia-cliente-metric-icon"><x-filament::icon icon="heroicon-o-shopping-cart" /></span>
                <p>Pedidos en curso</p>
                <strong>{{ number_format($resumen['pedidos']) }}</strong>
                <small>{{ $resumen['pedidos'] > 0 ? 'Requieren seguimiento comercial' : 'Sin pedidos abiertos' }}</small>
            </article>
        </div>

        <div class="sia-cliente-ultima-factura">
            <x-filament::icon icon="heroicon-o-calendar-days" />
            <span>Última facturación</span>
            <strong>{{ $resumen['ultimaFactura'] ? $resumen['ultimaFactura']->fecha_emision?->format('d/m/Y') : 'Sin facturas' }}</strong>
            @if ($resumen['ultimaFactura'])
                <small>Factura {{ $resumen['ultimaFactura']->numero }}</small>
            @else
                <small>Este cliente todavía no registra ventas.</small>
            @endif
        </div>
    @endif
</div>