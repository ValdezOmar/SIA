@php
    $stock = $this->existencia;
    $articulo = $stock->articulo;
    $fisico = (float) $stock->cantidad_disponible;
    $reservado = (float) $stock->cantidad_comprometida;
    $libre = max(0, $fisico - $reservado);
    $minimo = (float) $stock->cantidad_minima;
    [$estado, $color, $icono] = $libre <= 0
        ? ['Sin disponibilidad', 'danger', 'heroicon-m-exclamation-triangle']
        : ($libre <= $minimo ? ['Reposición requerida', 'warning', 'heroicon-m-exclamation-circle'] : ['Disponible para venta', 'success', 'heroicon-m-check-circle']);
    $cobertura = $minimo > 0 ? min(100, round(($libre / $minimo) * 100)) : 100;
@endphp

<div wire:poll.15s="refrescar" class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-primary-200 bg-gradient-to-br from-primary-50 via-white to-primary-100/50 shadow-sm dark:border-primary-800 dark:from-primary-950/40 dark:via-gray-950 dark:to-primary-950/20">
        <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex min-w-0 gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary-600 text-white shadow-lg shadow-primary-600/20"><x-filament::icon icon="heroicon-m-cube" class="size-6" /></div>
                <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="truncate text-lg font-bold text-gray-950 dark:text-white">{{ $articulo?->nombre_comercial ?: $articulo?->descripcion ?: 'Artículo sin nombre' }}</h3><x-filament::badge :color="$color" :icon="$icono">{{ $estado }}</x-filament::badge></div><p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $articulo?->codigo ?: 'Sin código' }} · {{ $articulo?->fabricante?->nombre ?: 'Sin marca' }} · <span class="font-medium">{{ $stock->almacen?->nombre }}</span></p><p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Ficha viva del inventario: se sincroniza automáticamente con los movimientos registrados.</p></div>
            </div>
            <x-filament::button wire:click="refrescar" wire:loading.attr="disabled" icon="heroicon-m-arrow-path" color="gray" size="sm"><span wire:loading.remove>Actualizar</span><span wire:loading>Actualizando</span></x-filament::button>
        </div>
        <div class="flex gap-1 border-t border-primary-100 bg-white/70 px-3 pt-2 dark:border-primary-900 dark:bg-gray-950/40">
            <button wire:click="mostrar('resumen')" @class(['rounded-t-xl px-4 py-2.5 text-sm font-semibold transition', 'bg-primary-600 text-white shadow-sm' => $vista === 'resumen', 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $vista !== 'resumen'])>Resumen de stock</button>
            <button wire:click="mostrar('actividad')" @class(['rounded-t-xl px-4 py-2.5 text-sm font-semibold transition', 'bg-primary-600 text-white shadow-sm' => $vista === 'actividad', 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $vista !== 'actividad'])>Actividad reciente <span class="ml-1 rounded-full bg-gray-200 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $this->movimientos->count() }}</span></button>
            <button wire:click="mostrar('reservas')" @class(['rounded-t-xl px-4 py-2.5 text-sm font-semibold transition', 'bg-primary-600 text-white shadow-sm' => $vista === 'reservas', 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' => $vista !== 'reservas'])>Reservas comerciales <span class="ml-1 rounded-full bg-gray-200 px-1.5 py-0.5 text-xs text-gray-700 dark:bg-gray-700 dark:text-gray-200">{{ $this->reservasComerciales->count() }}</span></button>
        </div>
    </section>

    @if ($vista === 'resumen')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-primary-200 bg-primary-50 p-4 shadow-sm dark:border-primary-800 dark:bg-primary-950/30"><div class="flex items-center justify-between"><span class="text-sm font-medium text-primary-800 dark:text-primary-200">Stock físico</span><div class="rounded-xl bg-primary-600/10 p-2 text-primary-700 dark:text-primary-300"><x-filament::icon icon="heroicon-m-cube" class="size-5" /></div></div><p class="mt-4 text-3xl font-bold tracking-tight text-primary-950 dark:text-white">{{ number_format($fisico, 2) }}</p><p class="mt-1 text-xs text-primary-700 dark:text-primary-300">{{ $articulo?->unidadMedida?->abreviatura ?: 'unidades' }} registrados en almacén</p></article>
            <article class="rounded-2xl border border-warning-200 bg-warning-50 p-4 shadow-sm dark:border-warning-800 dark:bg-warning-950/30"><div class="flex items-center justify-between"><span class="text-sm font-medium text-warning-800 dark:text-warning-200">Reservado</span><div class="rounded-xl bg-warning-600/10 p-2 text-warning-700 dark:text-warning-300"><x-filament::icon icon="heroicon-m-shopping-cart" class="size-5" /></div></div><p class="mt-4 text-3xl font-bold tracking-tight text-warning-950 dark:text-white">{{ number_format($reservado, 2) }}</p><p class="mt-1 text-xs text-warning-700 dark:text-warning-300">Comprometido en pedidos</p></article>
            <article class="rounded-2xl border border-success-200 bg-success-50 p-4 shadow-sm dark:border-success-800 dark:bg-success-950/30"><div class="flex items-center justify-between"><span class="text-sm font-medium text-success-800 dark:text-success-200">Libre para vender</span><div class="rounded-xl bg-success-600/10 p-2 text-success-700 dark:text-success-300"><x-filament::icon icon="heroicon-m-check-circle" class="size-5" /></div></div><p class="mt-4 text-3xl font-bold tracking-tight text-success-950 dark:text-white">{{ number_format($libre, 2) }}</p><p class="mt-1 text-xs text-success-700 dark:text-success-300">Puede comprometerse ahora</p></article>
            <article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"><div class="flex items-center justify-between"><span class="text-sm font-medium text-gray-700 dark:text-gray-200">Valor disponible</span><div class="rounded-xl bg-gray-100 p-2 text-gray-700 dark:bg-gray-800 dark:text-gray-200"><x-filament::icon icon="heroicon-m-banknotes" class="size-5" /></div></div><p class="mt-4 text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Bs {{ number_format((float) $stock->costo_acumulado, 2) }}</p><p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Costo promedio: Bs {{ number_format((float) $stock->costo_promedio, 2) }}</p></article>
        </div>

        <div class="grid gap-5 lg:grid-cols-5">
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-3"><div class="flex items-center justify-between"><div><h4 class="font-bold text-gray-950 dark:text-white">Control de reposición</h4><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Mida la disponibilidad contra el mínimo definido.</p></div><x-filament::badge :color="$color">{{ $estado }}</x-filament::badge></div><div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4"><div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Mínimo</p><p class="mt-1 font-bold">{{ number_format($minimo, 2) }}</p></div><div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Por recibir</p><p class="mt-1 font-bold">{{ number_format((float) $stock->cantidad_pedida, 2) }}</p></div><div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Última entrada</p><p class="mt-1 text-sm font-bold">{{ $stock->ultima_entrada?->format('d/m/Y H:i') ?: 'Sin registro' }}</p></div><div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800"><p class="text-xs text-gray-500">Última salida</p><p class="mt-1 text-sm font-bold">{{ $stock->ultima_salida?->format('d/m/Y H:i') ?: 'Sin registro' }}</p></div></div><div class="mt-5"><div class="mb-2 flex justify-between text-xs font-medium text-gray-500"><span>Cobertura frente al mínimo</span><span>{{ $cobertura }}%</span></div><div class="h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><div @class(['h-full rounded-full transition-all duration-500', 'bg-danger-500' => $color === 'danger', 'bg-warning-500' => $color === 'warning', 'bg-success-500' => $color === 'success']) style="width: {{ $cobertura }}%"></div></div></div></section>
            <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 lg:col-span-2"><h4 class="font-bold text-gray-950 dark:text-white">Identificación y venta</h4><dl class="mt-4 space-y-3 text-sm"><div class="flex justify-between gap-3"><dt class="text-gray-500">Grupo</dt><dd class="text-right font-medium">{{ $articulo?->grupoArticulo?->nombre ?: 'Sin grupo' }}</dd></div><div class="flex justify-between gap-3"><dt class="text-gray-500">Modelo</dt><dd class="text-right font-medium">{{ $articulo?->codigo_alterno ?: 'Sin modelo' }}</dd></div><div class="flex justify-between gap-3"><dt class="text-gray-500">Código principal</dt><dd class="max-w-44 truncate text-right font-medium">{{ $articulo?->codigosBarras?->firstWhere('principal', true)?->codigo_barras ?: 'Sin código de barras' }}</dd></div><div class="border-t border-gray-100 pt-3 dark:border-gray-800"><dt class="mb-2 text-gray-500">Precios activos</dt><dd class="flex flex-wrap gap-1">@forelse($articulo?->precios ?? [] as $precio)<x-filament::badge color="info">{{ $precio->listaPrecio?->nombre }}: {{ number_format((float) $precio->precio, 2) }} {{ $precio->listaPrecio?->moneda }}</x-filament::badge>@empty<span class="text-gray-500">Sin precio configurado</span>@endforelse</dd></div></dl></section>
        </div>
    @elseif ($vista === 'actividad')
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><div><h4 class="font-bold text-gray-950 dark:text-white">Actividad reciente</h4><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Movimientos del artículo dentro de {{ $stock->almacen?->nombre }}.</p></div><x-filament::badge color="gray">Actualización en vivo</x-filament::badge></div>
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($this->movimientos as $movimiento)
                    <article @class(['rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-md', 'border-success-200 bg-success-50/60 dark:border-success-900 dark:bg-success-950/20' => $movimiento->direccion === 'entrada', 'border-danger-200 bg-danger-50/60 dark:border-danger-900 dark:bg-danger-950/20' => $movimiento->direccion === 'salida'])>
                        <div class="flex items-start justify-between gap-3"><div><x-filament::badge :color="$movimiento->direccion === 'entrada' ? 'success' : 'danger'" :icon="$movimiento->direccion === 'entrada' ? 'heroicon-m-arrow-down-left' : 'heroicon-m-arrow-up-right'">{{ str($movimiento->tipo_movimiento)->headline() }}</x-filament::badge><p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $movimiento->fecha_movimiento?->format('d/m/Y · H:i') }}</p></div><p @class(['text-xl font-bold', 'text-success-700 dark:text-success-300' => $movimiento->direccion === 'entrada', 'text-danger-700 dark:text-danger-300' => $movimiento->direccion === 'salida'])>{{ $movimiento->direccion === 'entrada' ? '+' : '−' }}{{ number_format((float) $movimiento->cantidad, 2) }}</p></div>
                        <div class="mt-4 grid grid-cols-2 gap-2 border-t border-gray-200/70 pt-3 text-sm dark:border-gray-700"><div><p class="text-xs text-gray-500 dark:text-gray-400">Saldo final</p><p class="font-semibold text-gray-950 dark:text-white">{{ number_format((float) $movimiento->cantidad_posterior, 2) }}</p></div><div><p class="text-xs text-gray-500 dark:text-gray-400">Documento</p><p class="truncate font-semibold text-gray-950 dark:text-white">{{ $movimiento->documento_codigo ?: 'Movimiento manual' }}</p></div></div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">No hay movimientos registrados para este artículo en este almacén.</div>
                @endforelse
            </div>
        </section>
    @else
        @php($reservas = $this->reservasComerciales)
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div><h4 class="font-bold text-gray-950 dark:text-white">Compromisos comerciales</h4><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Reservas activas de este artículo. Revise estos compromisos antes de ofrecer nuevas unidades.</p></div>
                <x-filament::badge color="warning">{{ number_format($reservas->sum('cantidad'), 2) }} unidades reservadas</x-filament::badge>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl bg-warning-50 p-4 dark:bg-warning-950/30"><p class="text-xs font-medium">Unidades reservadas</p><p class="mt-1 text-2xl font-bold">{{ number_format($reservas->sum('cantidad'), 2) }}</p></div>
                <div class="rounded-xl bg-success-50 p-4 dark:bg-success-950/30"><p class="text-xs font-medium">Pagos confirmados</p><p class="mt-1 text-2xl font-bold">Bs {{ number_format($reservas->sum('pagado'), 2) }}</p></div>
                <div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-950/30"><p class="text-xs font-medium">Libre para nueva venta</p><p class="mt-1 text-2xl font-bold">{{ number_format($libre, 2) }}</p></div>
            </div>
            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse($reservas as $reserva)
                    <article class="rounded-2xl border border-warning-200 bg-warning-50/50 p-4 shadow-sm dark:border-warning-900 dark:bg-warning-950/20">
                        <div class="flex items-start justify-between gap-2"><div><x-filament::badge :color="$reserva['color']">{{ $reserva['origen'] }} · {{ $reserva['estado'] }}</x-filament::badge><p class="mt-3 font-bold text-gray-950 dark:text-white">{{ $reserva['cliente'] }}</p><p class="mt-1 text-sm text-gray-500">{{ $reserva['codigo'] }}{{ $reserva['celular'] ? ' · '.$reserva['celular'] : '' }}</p></div><p class="text-xl font-bold text-warning-700">{{ number_format($reserva['cantidad'], 2) }}</p></div>
                        <div class="mt-4 grid grid-cols-2 gap-2 border-t border-warning-200/70 pt-3 text-sm"><div><p class="text-xs text-gray-500">Reserva</p><p class="font-medium">{{ $reserva['fecha_reserva']?->format('d/m/Y H:i') ?: 'Sin fecha' }}</p></div><div><p class="text-xs text-gray-500">Entrega</p><p class="font-medium">{{ $reserva['entrega']?->format('d/m/Y') ?: 'Sin fecha' }}</p></div><div><p class="text-xs text-gray-500">Pagado</p><p class="font-medium text-success-700">Bs {{ number_format($reserva['pagado'], 2) }}</p></div><div><p class="text-xs text-gray-500">Último pago</p><p class="font-medium">{{ $reserva['fecha_pago']?->format('d/m/Y') ?: 'Sin pago' }}</p></div></div>
                    </article>
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-success-300 bg-success-50 p-10 text-center"><p class="font-semibold text-success-800">No hay reservas activas</p><p class="mt-1 text-sm text-success-700">Las unidades libres pueden ofrecerse a nuevos clientes.</p></div>
                @endforelse
            </div>
        </section>
    @endif
</div>
