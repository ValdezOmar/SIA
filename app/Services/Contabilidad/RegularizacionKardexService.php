<?php

namespace App\Services\Contabilidad;

use App\Models\Compras\FacturaCompra;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Kardex;
use App\Models\Ventas\Factura;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class RegularizacionKardexService
{
    public function diagnosticar(?int $empresaId = null): array
    {
        $movimientos = Kardex::query()
            ->where('estado', 'confirmado')
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId));
        $sinEfecto = ['transferencia_entrada', 'transferencia_salida', 'consignacion'];

        $sinMovimientoInventario = (clone $movimientos)
            ->whereDoesntHave('movimientoInventario')
            ->count();
        $pendientesContables = (clone $movimientos)
            ->whereNotIn('tipo_movimiento', $sinEfecto)
            ->where('costo_total', '>', 0)
            ->whereDoesntHave('asientoContable')
            ->count();
        $asientosDesbalanceados = AsientoContable::query()
            ->where('estado', 'confirmado')
            ->whereRaw('ABS(COALESCE(total_debe, 0) - COALESCE(total_haber, 0)) >= 0.01')
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->count();
        $ventasSinFactura = (clone $movimientos)
            ->where('documento_tipo', 'venta')
            ->where('documento_id', '>', 0)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('ven_facturas')
                    ->whereColumn('ven_facturas.id', 'alm_kardex.documento_id')
                    ->whereNull('ven_facturas.deleted_at');
            })
            ->count();
        $comprasSinFactura = (clone $movimientos)
            ->where('documento_tipo', 'compra')
            ->where('documento_id', '>', 0)
            ->whereNotExists(function ($query): void {
                $query->selectRaw('1')
                    ->from('cmp_facturas_compra')
                    ->whereColumn('cmp_facturas_compra.id', 'alm_kardex.documento_id')
                    ->whereNull('cmp_facturas_compra.deleted_at');
            })
            ->count();
        $bloqueantes = $sinMovimientoInventario + $asientosDesbalanceados + $ventasSinFactura + $comprasSinFactura;

        return [
            'confirmados' => (clone $movimientos)->count(),
            'sin_movimiento_inventario' => $sinMovimientoInventario,
            'asientos_desbalanceados' => $asientosDesbalanceados,
            'ventas_sin_factura' => $ventasSinFactura,
            'compras_sin_factura' => $comprasSinFactura,
            'pendientes_contables' => $pendientesContables,
            'bloqueantes' => $bloqueantes,
            'integro' => $bloqueantes === 0,
        ];
    }

    public function ejecutar(CarbonInterface $fechaContable, ?int $empresaId = null): array
    {
        $resultado = [
            'contabilizados' => 0,
            'cubiertos' => 0,
            'sin_efecto' => 0,
            'sin_valor' => 0,
            'errores' => [],
        ];

        Kardex::query()
            ->where('estado', 'confirmado')
            ->when($empresaId, fn ($query) => $query->where('empresa_id', $empresaId))
            ->orderBy('id')
            ->chunkById(100, function ($movimientos) use ($fechaContable, &$resultado): void {
                foreach ($movimientos as $kardex) {
                    try {
                        $estado = DB::transaction(fn () => $this->regularizarMovimiento($kardex, $fechaContable));
                        $resultado[$estado]++;
                    } catch (Throwable $exception) {
                        $resultado['errores'][] = [
                            'kardex_id' => $kardex->id,
                            'mensaje' => $exception->getMessage(),
                        ];
                    }
                }
            });

        return $resultado;
    }

    private function regularizarMovimiento(Kardex $kardex, CarbonInterface $fechaContable): string
    {
        if ($kardex->asientoContable()->exists()) {
            return 'cubiertos';
        }

        if (in_array($kardex->tipo_movimiento, ['transferencia_entrada', 'transferencia_salida', 'consignacion'], true)) {
            return 'sin_efecto';
        }

        if ((float) $kardex->costo_total <= 0) {
            return 'sin_valor';
        }

        if (in_array($kardex->documento_tipo, ['venta', 'devolucion_venta'], true)
            && AsientoContable::query()->where('documento_tipo', 'venta')->where('documento_id', $kardex->documento_id)->exists()) {
            return 'cubiertos';
        }

        if ($kardex->tipo_movimiento === 'venta' && $kardex->documento_tipo === 'venta') {
            $factura = Factura::find($kardex->documento_id);
            if ($factura) {
                $existia = AsientoContable::query()->where('documento_tipo', 'venta')->where('documento_id', $factura->id)->exists();
                AsientoContable::crearDesdeVenta($factura, $fechaContable);

                return $existia ? 'cubiertos' : 'contabilizados';
            }
        }

        if ($kardex->tipo_movimiento === 'compra' && in_array($kardex->documento_tipo, ['recepcion', 'compra'], true)) {
            $factura = $kardex->documento_tipo === 'recepcion'
                ? FacturaCompra::query()->where('recepcion_id', $kardex->documento_id)->first()
                : FacturaCompra::find($kardex->documento_id);

            if ($factura) {
                $existia = AsientoContable::query()->where('documento_tipo', 'compra')->where('documento_id', $factura->id)->exists();
                AsientoContable::crearDesdeCompra($factura, $fechaContable);

                return $existia ? 'cubiertos' : 'contabilizados';
            }
        }

        $kardex->fecha_contable = $fechaContable;
        $kardex->saveQuietly();
        if ($kardex->movimiento_relacionado_id) {
            Kardex::query()->whereKey($kardex->movimiento_relacionado_id)
                ->update(['fecha_contable' => $fechaContable]);
        }
        AsientoContable::crearDesdeKardex($kardex);

        return 'contabilizados';
    }
}
