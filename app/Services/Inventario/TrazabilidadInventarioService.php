<?php

namespace App\Services\Inventario;

use App\Models\Inventario\Articulo;
use App\Models\Inventario\Lote;
use App\Models\Inventario\LoteStock;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Inventario\MovimientoLote;
use App\Models\Inventario\MovimientoSerie;
use App\Models\Inventario\Serie;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TrazabilidadInventarioService
{
    public function registrarEntrada(MovimientoInventario $movimiento, Articulo $articulo, array $data): void
    {
        DB::transaction(function () use ($movimiento, $articulo, $data) {
            $almacenId = $movimiento->almacen_id;
            $cantidad = (float) ($data['cantidad'] ?? 0);
            $series = $this->normalizarSeries($data['series'] ?? []);
            $lotes = $this->normalizarLotes($data['lotes'] ?? []);

            if ($articulo->maneja_series && count($series) !== (int) $cantidad) {
                throw new RuntimeException('La cantidad de series debe coincidir con la cantidad aceptada.');
            }

            if ($articulo->maneja_series) {
                foreach ($series as $numeroSerie) {
                    $serie = Serie::where('numero_serie', $numeroSerie)->lockForUpdate()->first();
                    if ($serie && in_array($serie->estado, ['vendido', 'baja'], true)) {
                        throw new RuntimeException('La serie '.$numeroSerie.' no está disponible para ingreso.');
                    }

                    $serie ??= new Serie;
                    $serie->fill([
                        'articulo_id' => $articulo->id,
                        'almacen_id' => $almacenId,
                        'numero_serie' => $numeroSerie,
                        'estado' => 'disponible',
                        'estado_actual' => 'disponible',
                    ]);
                    $serie->save();

                    MovimientoSerie::create([
                        'serie_id' => $serie->id,
                        'movimiento_inventario_id' => $movimiento->id,
                        'tipo' => 'entrada',
                        'observaciones' => 'Entrada de inventario',
                    ]);
                }
            }

            if ($articulo->maneja_lotes) {
                if (abs(array_sum($lotes) - $cantidad) > 0.000001) {
                    throw new RuntimeException('La suma de cantidades por lote debe coincidir con la cantidad aceptada.');
                }

                foreach ($lotes as $numeroLote => $cantidadLote) {
                    $lote = Lote::firstOrCreate([
                        'articulo_id' => $articulo->id,
                        'numero_lote' => $numeroLote,
                    ]);
                    $stock = LoteStock::where('lote_id', $lote->id)
                        ->where('almacen_id', $almacenId)
                        ->lockForUpdate()
                        ->first();
                    $stock ??= new LoteStock(['lote_id' => $lote->id, 'almacen_id' => $almacenId, 'cantidad' => 0]);
                    $stock->cantidad += $cantidadLote;
                    $stock->save();

                    MovimientoLote::create([
                        'movimiento_id' => $movimiento->id,
                        'lote_id' => $lote->id,
                        'cantidad' => $cantidadLote,
                    ]);
                }
            }
        });
    }

    public function registrarSalida(MovimientoInventario $movimiento, Articulo $articulo, array $data): void
    {
        DB::transaction(function () use ($movimiento, $articulo, $data) {
            $almacenId = $movimiento->almacen_id;
            $cantidad = (float) ($data['cantidad'] ?? 0);
            $series = $this->normalizarSeries($data['series'] ?? []);
            $lotes = $this->normalizarLotes($data['lotes'] ?? []);
            $esTransferencia = $movimiento->tipo === 'transferencia_salida';

            if ($articulo->maneja_series && ($articulo->requiere_serie_en_salida || $series)) {
                if (count($series) !== (int) $cantidad) {
                    throw new RuntimeException('Debe indicar una serie disponible por cada unidad vendida.');
                }

                foreach ($series as $numeroSerie) {
                    $serie = Serie::where('numero_serie', $numeroSerie)
                        ->where('articulo_id', $articulo->id)
                        ->where('almacen_id', $almacenId)
                        ->lockForUpdate()
                        ->first();
                    if (! $serie || $serie->estado !== 'disponible') {
                        throw new RuntimeException('La serie '.$numeroSerie.' no está disponible en este almacén.');
                    }
                    $serie->update($esTransferencia ? [
                        // El enum actual no contempla "en_transito". Se reserva la serie
                        // para impedir nuevas salidas y el estado descriptivo conserva el flujo.
                        'estado' => 'reservado',
                        'estado_actual' => 'en_transito',
                        'cliente_id' => null,
                        'fecha_venta' => null,
                    ] : [
                        'estado' => 'vendido',
                        'estado_actual' => 'vendido',
                        'cliente_id' => $data['cliente_id'] ?? null,
                        'fecha_venta' => now()->toDateString(),
                    ]);
                    MovimientoSerie::create([
                        'serie_id' => $serie->id,
                        'movimiento_inventario_id' => $movimiento->id,
                        'tipo' => 'salida',
                        'observaciones' => $esTransferencia ? 'Salida por transferencia entre almacenes' : 'Salida por venta',
                    ]);
                }
            }

            if ($articulo->maneja_lotes) {
                if (abs(array_sum($lotes) - $cantidad) > 0.000001) {
                    throw new RuntimeException('La suma de cantidades por lote debe coincidir con la cantidad vendida.');
                }
                foreach ($lotes as $numeroLote => $cantidadLote) {
                    $lote = Lote::where('articulo_id', $articulo->id)->where('numero_lote', $numeroLote)->first();
                    $stock = $lote ? LoteStock::where('lote_id', $lote->id)->where('almacen_id', $almacenId)->lockForUpdate()->first() : null;
                    if (! $stock || (float) $stock->cantidad < $cantidadLote) {
                        throw new RuntimeException('Stock insuficiente en el lote '.$numeroLote.'.');
                    }
                    $stock->cantidad -= $cantidadLote;
                    $stock->save();
                    MovimientoLote::create([
                        'movimiento_id' => $movimiento->id,
                        'lote_id' => $lote->id,
                        'cantidad' => -$cantidadLote,
                    ]);
                }
            }
        });
    }

    public function revertirSalida(MovimientoInventario $original, MovimientoInventario $reversion): void
    {
        DB::transaction(function () use ($original, $reversion) {
            foreach ($original->series()->with('serie')->lockForUpdate()->get() as $movimientoSerie) {
                $serie = $movimientoSerie->serie;
                if (! $serie) {
                    continue;
                }

                $serie->update([
                    'estado' => 'disponible',
                    'estado_actual' => 'disponible',
                    'cliente_id' => null,
                    'fecha_venta' => null,
                ]);

                MovimientoSerie::create([
                    'serie_id' => $serie->id,
                    'movimiento_inventario_id' => $reversion->id,
                    'tipo' => 'entrada',
                    'observaciones' => 'Reversión de salida de venta',
                ]);
            }

            foreach ($original->lotes()->with('lote')->lockForUpdate()->get() as $movimientoLote) {
                $lote = $movimientoLote->lote;
                if (! $lote) {
                    continue;
                }

                $stock = LoteStock::where('lote_id', $lote->id)
                    ->where('almacen_id', $original->almacen_id)
                    ->lockForUpdate()
                    ->first();
                $stock ??= new LoteStock([
                    'lote_id' => $lote->id,
                    'almacen_id' => $original->almacen_id,
                    'cantidad' => 0,
                ]);
                $cantidad = abs((float) $movimientoLote->cantidad);
                $stock->cantidad += $cantidad;
                $stock->save();

                MovimientoLote::create([
                    'movimiento_id' => $reversion->id,
                    'lote_id' => $lote->id,
                    'cantidad' => $cantidad,
                ]);
            }
        });
    }

    private function normalizarSeries(array|string|null $series): array
    {
        if (is_string($series)) {
            $series = preg_split('/[,\n]+/', $series);
        }

        return array_values(array_filter(array_map('trim', $series ?? [])));
    }

    private function normalizarLotes(array|string|null $lotes): array
    {
        if (is_string($lotes)) {
            $lotes = preg_split('/[,\n]+/', $lotes);
        }
        $resultado = [];
        foreach ($lotes ?? [] as $lote) {
            if (is_string($lote) && str_contains($lote, ':')) {
                [$numero, $cantidad] = array_map('trim', explode(':', $lote, 2));
                $resultado[$numero] = ($resultado[$numero] ?? 0) + (float) $cantidad;
            } elseif (is_array($lote) && isset($lote['numero_lote'], $lote['cantidad'])) {
                $resultado[$lote['numero_lote']] = ($resultado[$lote['numero_lote']] ?? 0) + (float) $lote['cantidad'];
            }
        }

        return $resultado;
    }
}
