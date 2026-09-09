<div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-white/10">
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Análisis comercial mensual</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ventas contabilizadas, expresadas en bolivianos.</p>
    </div>

    <div class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-2">
            @foreach ($this->pestanas() as $clave => $etiqueta)
                <button type="button" wire:click="seleccionarPestana('{{ $clave }}')" class="rounded-lg px-3 py-2 text-sm font-medium {{ $pestana === $clave ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $etiqueta }}
                </button>
            @endforeach
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
            <span class="whitespace-nowrap">Mes</span>
            <select wire:model.live="periodo" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @foreach ($this->periodos() as $valor => $etiqueta)
                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-y border-gray-200 bg-gray-50 text-xs uppercase text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                <tr>
                    @foreach ($this->columnas() as $columna)
                        <th class="px-6 py-3 font-semibold">{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                @forelse ($filas as $fila)
                    <tr class="text-gray-700 dark:text-gray-200">
                        @if ($pestana === 'productos_vendidos')
                            <td class="px-6 py-3 font-medium">{{ $fila['producto'] }}</td><td class="px-6 py-3 text-right">{{ number_format($fila['unidades'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right font-medium">Bs {{ number_format($fila['venta_neta'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right text-warning-600">Bs {{ number_format($fila['descuentos'], 2, ',', '.') }}</td>
                        @elseif ($pestana === 'productos_rentables')
                            <td class="px-6 py-3 font-medium">{{ $fila['producto'] }}</td><td class="px-6 py-3 text-right">{{ number_format($fila['unidades'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right">Bs {{ number_format($fila['venta_neta'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right text-danger-600">Bs {{ number_format($fila['costo'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right font-medium text-success-600">Bs {{ number_format($fila['ganancia'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right">{{ number_format($fila['margen'], 2, ',', '.') }} %</td>
                        @elseif ($pestana === 'clientes')
                            <td class="px-6 py-3 font-medium">{{ $fila['cliente'] }}</td><td class="px-6 py-3 text-right">{{ $fila['facturas'] }}</td><td class="px-6 py-3 text-right font-medium">Bs {{ number_format($fila['compra_neta'], 2, ',', '.') }}</td><td class="px-6 py-3 text-right">{{ $fila['ultima_compra'] }}</td>
                        @else
                            <td class="px-6 py-3 font-medium">{{ $fila['indicador'] }}</td><td class="px-6 py-3 text-right font-medium">@if ($fila['indicador'] === 'Clientes activos') {{ number_format($fila['valor']) }} @elseif ($fila['indicador'] === 'Unidades vendidas') {{ number_format($fila['valor'], 2, ',', '.') }} @else Bs {{ number_format($fila['valor'], 2, ',', '.') }} @endif</td><td class="px-6 py-3 text-gray-500 dark:text-gray-400">{{ $fila['detalle'] }}</td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ count($this->columnas()) }}" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">{{ $mensajeError ?? 'No hay ventas contabilizadas para este mes.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
