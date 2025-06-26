<div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 space-y-5">
    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-500">Resumen de Vencimiento</h4>

    @php
        $items = [
            ['label' => 'Vencidos', 'valor' => $vencido, 'bg' => 'bg-red-100', 'bar' => 'bg-red-600'],
            ['label' => 'Menor a 4 meses', 'valor' => $menos4, 'bg' => 'bg-orange-100', 'bar' => 'bg-orange-500'],
            ['label' => 'Entre 4–8 meses', 'valor' => $entre4y8, 'bg' => 'bg-amber-100', 'bar' => 'bg-amber-500'],
            ['label' => 'Mayor a 8 meses', 'valor' => $mas8, 'bg' => 'bg-green-100', 'bar' => 'bg-green-500'],
            ['label' => 'Sin fecha', 'valor' => $sinFecha, 'bg' => 'bg-gray-200', 'bar' => 'bg-gray-500'],
        ];
    @endphp

    @foreach ($items as $item)
        <div class="flex justify-between items-center">
            <span class="text-xs text-blue-600 dark:text-blue-300">{{ $item['label'] }}:</span>
            <div class="flex items-center space-x-3 w-3/4 justify-end">
                <span class="font-semibold text-xs text-gray-800 dark:text-white">{{ $item['valor'] }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">({{ $total ? round($item['valor'] / $total * 100, 1) : 0 }}%)</span>                
            </div>
        </div>
    @endforeach
</div>
