<div class="p-3 bg-white dark:bg-gray-800 rounded-xl shadow-xs border border-gray-200 dark:border-gray-700 space-y-1">
    <h4 class="text-md font-semibold text-gray-900 dark:text-gray-400 mb-2">Resumen de Vencimiento</h4>

    @php
        $items = [
            ['label' => 'Vencidos', 'valor' => $vencido, 'bg' => 'bg-red-100 dark:bg-red-900/30', 'bar' => 'bg-red-600 dark:bg-red-500'],
            ['label' => 'Menor a 4 meses', 'valor' => $menos4, 'bg' => 'bg-orange-100 dark:bg-orange-900/30', 'bar' => 'bg-orange-500 dark:bg-orange-400'],
            ['label' => 'Entre 4–8 meses', 'valor' => $entre4y8, 'bg' => 'bg-amber-100 dark:bg-amber-900/30', 'bar' => 'bg-amber-500 dark:bg-amber-400'],
            ['label' => 'Mayor a 8 meses', 'valor' => $mas8, 'bg' => 'bg-green-100 dark:bg-green-900/30', 'bar' => 'bg-green-500 dark:bg-green-400'],
            ['label' => 'Sin fecha', 'valor' => $sinFecha, 'bg' => 'bg-gray-200 dark:bg-gray-800', 'bar' => 'bg-gray-500 dark:bg-gray-400'],
        ];
    @endphp

    @foreach ($items as $item)
        <div class="flex justify-between items-center gap-2">
            <div class="text-xs text-blue-600 dark:text-white whitespace-nowrap min-w-0 truncate flex-shrink">
                {{ $item['label'] }}:
            </div>
            <div class="flex items-center justify-end gap-2 min-w-0 flex-1">
                <span class="font-semibold text-xs text-gray-800 dark:text-gray-100 whitespace-nowrap">
                    {{ $item['valor'] }}
                </span>
                <span class="text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    ({{ $total ? round($item['valor'] / $total * 100, 1) : 0 }}%)
                </span>
            </div>
        </div>
    @endforeach
</div>