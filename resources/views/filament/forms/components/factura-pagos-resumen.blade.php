@php
    $factura = $record ?? (isset($getRecord) ? $getRecord() : null);
    $moneda = $factura?->moneda ?? 'BOB';
    $simbolo = match ($moneda) { 'BOB' => 'Bs', 'USD' => '$', 'EUR' => '€', default => $moneda };
    $format = fn ($amount) => $simbolo.' '.number_format((float) $amount, 2, ',', '.');
    $pagos = $factura ? $factura->pagos()->latest('fecha_pago')->latest('id')->get() : collect();
    $confirmados = $pagos->whereNotIn('estado', ['anulado', 'rechazado']);
    $pagado = $confirmados->sum('monto');
    $total = (float) ($factura?->total ?? 0);
    $saldo = max(0, $total - $pagado);
    $tipos = ['efectivo' => 'Efectivo', 'qr' => 'QR', 'mixto' => 'Pago mixto', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque', 'tarjeta' => 'Tarjeta', 'deposito' => 'Depósito', 'nota_credito' => 'Nota de crédito', 'otros' => 'Otros'];
@endphp

<div class="sia-invoice-payments" wire:poll.30s>
    @if (! $factura)
        <div class="sia-invoice-payments__empty">
            <x-filament::icon icon="heroicon-m-document-arrow-down" />
            <div><strong>Guarda la factura para gestionar los pagos</strong><p>Al crearla, podrás registrar cobros y consultar su historial desde esta pestaña.</p></div>
        </div>
    @else
        <div class="sia-invoice-payments__hero">
            <div><span>Estado de cobranza</span><h3>{{ $saldo > 0 ? 'Saldo pendiente' : 'Factura cubierta' }}</h3><p>{{ $pagos->count() }} movimiento{{ $pagos->count() === 1 ? '' : 's' }} registrado{{ $pagos->count() === 1 ? '' : 's' }}</p></div>
            <div class="sia-invoice-payments__hero-amount"><span>Saldo actual</span><strong class="{{ $saldo > 0 ? 'is-pending' : 'is-paid' }}">{{ $format($saldo) }}</strong></div>
        </div>

        <div class="sia-invoice-payments__metrics">
            <div><span>Total facturado</span><strong>{{ $format($total) }}</strong></div>
            <div><span>Total cobrado</span><strong class="is-paid">{{ $format($pagado) }}</strong></div>
            <div><span>Avance de cobro</span><strong>{{ $total > 0 ? number_format(min(100, ($pagado / $total) * 100), 0) : 0 }}%</strong><i><b style="width: {{ $total > 0 ? min(100, ($pagado / $total) * 100) : 0 }}%"></b></i></div>
        </div>

        <div class="sia-invoice-payments__heading"><div><h3>Movimientos de pago</h3><p>Actualizado automáticamente cuando se registra o modifica un pago.</p></div><span>{{ $moneda }}</span></div>

        @if ($pagos->isEmpty())
            <div class="sia-invoice-payments__empty"><x-filament::icon icon="heroicon-m-credit-card" /><div><strong>Aún no hay pagos registrados</strong><p>Usa la acción “Nuevo pago” en esta factura para registrar el primer cobro.</p></div></div>
        @else
            <div class="sia-invoice-payments__list">
                @foreach ($pagos as $pago)
                    @php $activo = ! in_array($pago->estado, ['anulado', 'rechazado'], true); @endphp
                    <article class="sia-invoice-payments__payment {{ $activo ? '' : 'is-void' }}">
                        <div class="sia-invoice-payments__payment-icon"><x-filament::icon icon="heroicon-m-credit-card" /></div>
                        <div class="sia-invoice-payments__payment-main"><strong>{{ $tipos[$pago->tipo_pago] ?? ucfirst($pago->tipo_pago ?? 'Pago') }}</strong><span>{{ $pago->fecha_pago?->format('d/m/Y') ?? 'Sin fecha' }} · {{ $pago->numero ?: 'Sin número' }}</span>@if ($pago->referencia)<small>Referencia: {{ $pago->referencia }}</small>@endif</div>
                        <div class="sia-invoice-payments__payment-amount"><strong>{{ $format($pago->monto) }}</strong><span class="sia-invoice-payments__badge is-{{ $pago->estado ?? 'confirmado' }}">{{ ucfirst($pago->estado ?? 'confirmado') }}</span></div>
                    </article>
                @endforeach
            </div>
        @endif
    @endif
</div>

<style>
 .sia-invoice-payments{display:grid;gap:1rem;color:#0f172a}.sia-invoice-payments__hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1.3rem 1.4rem;border:1px solid #dbe5f0;border-radius:1rem;background:linear-gradient(135deg,#fff 0%,#f4f8ff 100%)}.sia-invoice-payments__hero span,.sia-invoice-payments__metrics span{display:block;color:#64748b;font-size:.72rem;font-weight:750;text-transform:uppercase;letter-spacing:.045em}.sia-invoice-payments__hero h3,.sia-invoice-payments__heading h3{margin:.25rem 0;color:#0f172a;font-size:1.05rem;font-weight:800}.sia-invoice-payments__hero p,.sia-invoice-payments__heading p{margin:0;color:#64748b;font-size:.8rem}.sia-invoice-payments__hero-amount{text-align:right}.sia-invoice-payments__hero-amount strong{display:block;margin-top:.22rem;font-size:1.5rem}.is-paid{color:#15803d!important}.is-pending{color:#b45309!important}.sia-invoice-payments__metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:.8rem}.sia-invoice-payments__metrics>div{padding:1rem;border:1px solid #e2e8f0;border-radius:.8rem;background:#fff}.sia-invoice-payments__metrics strong{display:block;margin-top:.35rem;color:#0f172a;font-size:1.05rem}.sia-invoice-payments__metrics i{display:block;overflow:hidden;height:.35rem;margin-top:.65rem;border-radius:999px;background:#e2e8f0}.sia-invoice-payments__metrics i b{display:block;height:100%;border-radius:inherit;background:var(--sia-primary)}.sia-invoice-payments__heading{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-top:.3rem}.sia-invoice-payments__heading>span{padding:.25rem .55rem;border-radius:.45rem;background:#eaf1fb;color:#35516f;font-size:.72rem;font-weight:800}.sia-invoice-payments__list{overflow:hidden;border:1px solid #e2e8f0;border-radius:.85rem;background:#fff}.sia-invoice-payments__payment{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:.8rem;padding:.85rem 1rem;border-bottom:1px solid #e2e8f0}.sia-invoice-payments__payment:last-child{border-bottom:0}.sia-invoice-payments__payment-icon{display:grid;place-items:center;width:2.15rem;height:2.15rem;border-radius:.6rem;background:#edf7f0;color:#15803d}.sia-invoice-payments__payment-icon svg{width:1.1rem;height:1.1rem}.sia-invoice-payments__payment-main strong,.sia-invoice-payments__payment-main span,.sia-invoice-payments__payment-main small{display:block}.sia-invoice-payments__payment-main strong{font-size:.85rem;font-weight:800}.sia-invoice-payments__payment-main span,.sia-invoice-payments__payment-main small{margin-top:.12rem;color:#64748b;font-size:.75rem}.sia-invoice-payments__payment-main small{color:#475569}.sia-invoice-payments__payment-amount{text-align:right}.sia-invoice-payments__payment-amount>strong{display:block;color:#15803d;font-size:.9rem}.sia-invoice-payments__badge{display:inline-block;margin-top:.25rem;padding:.16rem .45rem;border-radius:999px;background:#dcfce7;color:#166534;font-size:.68rem;font-weight:800}.sia-invoice-payments__badge.is-pendiente{background:#fef3c7;color:#92400e}.sia-invoice-payments__badge.is-rechazado,.sia-invoice-payments__badge.is-anulado{background:#fee2e2;color:#b91c1c}.sia-invoice-payments__payment.is-void{opacity:.58}.sia-invoice-payments__empty{display:flex;align-items:flex-start;gap:.85rem;padding:1rem;border:1px dashed #cbd5e1;border-radius:.8rem;background:#f8fafc;color:#475569}.sia-invoice-payments__empty svg{flex:0 0 auto;width:1.35rem;height:1.35rem;color:var(--sia-primary)}.sia-invoice-payments__empty strong{display:block;color:#334155;font-size:.86rem}.sia-invoice-payments__empty p{margin:.18rem 0 0;font-size:.78rem;line-height:1.45}.dark .sia-invoice-payments{color:#f8fafc}.dark .sia-invoice-payments__hero,.dark .sia-invoice-payments__metrics>div,.dark .sia-invoice-payments__list{border-color:#475569;background:#1e293b}.dark .sia-invoice-payments__hero{background:linear-gradient(135deg,#1e293b,#172033)}.dark .sia-invoice-payments__hero h3,.dark .sia-invoice-payments__heading h3,.dark .sia-invoice-payments__metrics strong,.dark .sia-invoice-payments__payment-main strong,.dark .sia-invoice-payments__empty strong{color:#f8fafc}.dark .sia-invoice-payments__hero span,.dark .sia-invoice-payments__hero p,.dark .sia-invoice-payments__heading p,.dark .sia-invoice-payments__metrics span,.dark .sia-invoice-payments__payment-main span,.dark .sia-invoice-payments__payment-main small{color:#94a3b8}.dark .sia-invoice-payments__payment{border-color:#475569}.dark .sia-invoice-payments__empty{border-color:#475569;background:#172033;color:#cbd5e1}@media(max-width:640px){.sia-invoice-payments__hero{align-items:flex-start;flex-direction:column}.sia-invoice-payments__hero-amount{text-align:left}.sia-invoice-payments__metrics{grid-template-columns:1fr}.sia-invoice-payments__payment{grid-template-columns:auto minmax(0,1fr)}.sia-invoice-payments__payment-amount{grid-column:2;text-align:left}}
</style>
