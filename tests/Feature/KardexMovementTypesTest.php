<?php

namespace Tests\Feature;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class KardexMovementTypesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('movementTypes')]
    public function test_every_movement_type_updates_stock_auxiliary_ledger_and_accounting_as_expected(
        string $type,
        string $direction,
        string $auxiliaryType,
        bool $requiresStock,
        bool $createsAccountingEntry,
    ): void {
        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa Kardex',
            'nombre_comercial' => 'Kardex',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $almacen = Almacen::create([
            'codigo' => 'ALM-'.strtoupper(str_replace('_', '-', $type)).'-'.$direction,
            'nombre' => 'Almacen '.$type,
            'empresa_id' => $empresaId,
            'activo' => true,
            'permite_inventario_negativo' => false,
        ]);
        $articulo = Articulo::create([
            'codigo' => 'ART-'.strtoupper(str_replace('_', '-', $type)).'-'.$direction,
            'nombre_comercial' => 'Articulo '.$type,
            'empresa_id' => $empresaId,
            'inventariable' => true,
            'metodo_costo' => 'promedio',
            'activo' => true,
        ]);

        if ($requiresStock) {
            Kardex::registrarEntrada([
                'articulo_id' => $articulo->id,
                'almacen_id' => $almacen->id,
                'tipo_movimiento' => 'inventario_inicial',
                'direccion' => 'entrada',
                'cantidad' => 10,
                'costo_unitario' => 10,
                'documento_tipo' => 'manual',
                'documento_id' => 0,
                'empresa_id' => $empresaId,
                'fecha_movimiento' => now(),
            ]);
        }

        $kardex = Kardex::registrarMovimiento([
            'articulo_id' => $articulo->id,
            'almacen_id' => $almacen->id,
            'tipo_movimiento' => $type,
            'direccion' => $direction,
            'cantidad' => 2,
            'costo_unitario' => 10,
            'documento_tipo' => 'manual',
            'documento_id' => 0,
            'empresa_id' => $empresaId,
            'fecha_movimiento' => now(),
            'fecha_contable' => now(),
            'motivo' => 'Prueba automática de '.$type,
        ]);

        $this->assertSame($direction, $kardex->direccion);
        $this->assertSame('confirmado', $kardex->estado);
        $this->assertSame($direction === 'entrada' ? 2.0 : -2.0, (float) MovimientoInventario::query()
            ->where('kardex_id', $kardex->id)->value('cantidad'));
        $this->assertSame($auxiliaryType, MovimientoInventario::query()
            ->where('kardex_id', $kardex->id)->value('tipo'));
        $this->assertSame($direction === 'entrada' ? 2.0 : 8.0, (float) Existencia::query()
            ->where('articulo_id', $articulo->id)->where('almacen_id', $almacen->id)->value('cantidad_disponible'));
        $this->assertSame($createsAccountingEntry, AsientoContable::query()
            ->where('documento_tipo', 'kardex')->where('documento_id', $kardex->id)->exists());
    }

    public static function movementTypes(): array
    {
        return [
            'compra' => ['compra', 'entrada', 'entrada_compra', false, true],
            'venta' => ['venta', 'salida', 'salida_venta', true, true],
            'transferencia entrada' => ['transferencia_entrada', 'entrada', 'transferencia_entrada', false, false],
            'transferencia salida' => ['transferencia_salida', 'salida', 'transferencia_salida', true, false],
            'ajuste positivo' => ['ajuste_incremento', 'entrada', 'ajuste_positivo', false, true],
            'ajuste negativo' => ['ajuste_decremento', 'salida', 'ajuste_negativo', true, true],
            'devolucion compra' => ['devolucion_compra', 'salida', 'salida_devolucion', true, true],
            'devolucion venta' => ['devolucion_venta', 'entrada', 'entrada_devolucion', false, true],
            'produccion entrada' => ['produccion_entrada', 'entrada', 'produccion_entrada', false, true],
            'produccion salida' => ['produccion_salida', 'salida', 'produccion_salida', true, true],
            'inventario inicial' => ['inventario_inicial', 'entrada', 'entrada_inventario_inicial', false, true],
            'ajuste fisico entrada' => ['ajuste_fisico', 'entrada', 'ajuste_positivo', false, true],
            'ajuste fisico salida' => ['ajuste_fisico', 'salida', 'ajuste_negativo', true, true],
            'merma' => ['merma', 'salida', 'salida_merma', true, true],
            'despacho' => ['despacho', 'salida', 'salida_despacho', true, true],
            'consignacion' => ['consignacion', 'entrada', 'entrada_consignacion', false, false],
        ];
    }
}
