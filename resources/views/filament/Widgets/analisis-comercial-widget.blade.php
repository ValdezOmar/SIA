<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Análisis comercial mensual</x-slot>
        <x-slot name="description">Ventas contabilizadas. Los importes se expresan en bolivianos.</x-slot>

        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap gap-2" role="tablist" aria-label="Análisis comercial">
                @foreach ($this->pestanas() as $clave => $etiqueta)
                    <x-filament::button
                        size="sm"
                        :color="$pestana === $clave ? 'primary' : 'gray'"
                        wire:click="seleccionarPestana('{{ $clave }}')"
                    >
                        {{ $etiqueta }}
                    </x-filament::button>
                @endforeach
            </div>

            <select wire:model.live="periodo" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @foreach ($this->periodos() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                    <tr>
                        @foreach ($this->columnas() as $columna)
                            <th class="px-3 py-3 font-semibold">{{ $columna }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                    @forelse ($filas as $fila)
                        <tr class="text-gray-700 dark:text-gray-200">
                            @if ($pestana === 'productos_vendidos')
                                <td class="px-3 py-3 font-medium">{{ $fila['producto'] }}</td>
                                <td class="px-3 py-3 text-right">{{ number_format($fila['unidades'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right font-medium">Bs {{ number_format($fila['venta_neta'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-warning-600 dark:text-warning-400">Bs {{ number_format($fila['descuentos'], 2, ',', '.') }}</td>
                            @elseif ($pestana === 'productos_rentables')
                                <td class="px-3 py-3 font-medium">{{ $fila['producto'] }}</td>
                                <td class="px-3 py-3 text-right">{{ number_format($fila['unidades'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">Bs {{ number_format($fila['venta_neta'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right text-danger-600 dark:text-danger-400">Bs {{ number_format($fila['costo'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right font-medium text-success-600 dark:text-success-400">Bs {{ number_format($fila['ganancia'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">{{ number_format($fila['margen'], 2, ',', '.') }} %</td>
                            @elseif ($pestana === 'clientes')
                                <td class="px-3 py-3 font-medium">{{ $fila['cliente'] }}</td>
                                <td class="px-3 py-3 text-right">{{ $fila['facturas'] }}</td>
                                <td class="px-3 py-3 text-right font-medium">Bs {{ number_format($fila['compra_neta'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">{{ $fila['ultima_compra'] }}</td>
                            @else
                                <td class="px-3 py-3 font-medium">{{ $fila['indicador'] }}</td>
                                <td class="px-3 py-3 text-right font-medium">
                                    @if (in_array($fila['indicador'], ['Clientes activos'], true))
                                        {{ number_format($fila['valor']) }}
                                    @elseif ($fila['indicador'] === 'Unidades vendidas')
                                        {{ number_format($fila['valor'], 2, ',', '.') }}
                                    @else
                                        Bs {{ number_format($fila['valor'], 2, ',', '.') }}
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ $fila['detalle'] }}</td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($this->columnas()) }}" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                                {{ $mensajeError ?? 'No hay ventas contabilizadas para este mes.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
