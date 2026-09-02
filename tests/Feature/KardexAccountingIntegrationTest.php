<?php

namespace Tests\Feature;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Kardex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KardexAccountingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $empresaId;

    private int $almacenId;

    private int $articuloId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba', 'nombre_comercial' => 'Empresa Test',
            'pais' => 'Bolivia', 'empresa_activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->user = User::factory()->create(['empresa_id' => $this->empresaId]);
        $this->actingAs($this->user);
        $this->almacenId = DB::table('alm_almacenes')->insertGetId([
            'codigo' => 'ALM-001', 'nombre' => 'Almacén principal', 'direccion' => 'Calle 1',
            'empresa_id' => $this->empresaId, 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->articuloId = DB::table('alm_articulos')->insertGetId([
            'codigo' => 'ART-001', 'nombre_comercial' => 'Artículo de prueba', 'descripcion' => 'Prueba',
            'inventariable' => true, 'comprable' => true, 'vendible' => true, 'maneja_lotes' => false,
            'maneja_series' => false, 'requiere_serie_en_salida' => false, 'metodo_costo' => 'promedio',
            'comision' => 0, 'activo' => true, 'empresa_id' => $this->empresaId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_manual_inventory_adjustment_creates_balanced_confirmed_entry(): void
    {
        $kardex = $this->movimiento('ajuste_incremento', 4, 25);
        $asiento = $kardex->asientoContable()->with('detalles.cuenta')->firstOrFail();

        $this->assertSame('confirmado', $asiento->estado);
        $this->assertEquals(100, (float) $asiento->total_debe);
        $this->assertEquals(100, (float) $asiento->total_haber);
        $this->assertSame(['1.1.5', '4.9.1'], $asiento->detalles->pluck('cuenta.codigo')->all());
    }

    public function test_reversion_creates_exact_inverse_accounting_entry(): void
    {
        $original = $this->movimiento('merma', 2, 10, true);
        $asientoOriginal = $original->asientoContable()->with('detalles')->firstOrFail();

        $reversion = $original->revertirMovimiento('Corrección de prueba');
        $asientoReversion = $reversion->asientoContable()->with('detalles')->firstOrFail();

        foreach ($asientoOriginal->detalles as $linea) {
            $inversa = $asientoReversion->detalles->firstWhere('linea', $linea->linea);
            $this->assertEquals((float) $linea->debe, (float) $inversa->haber);
            $this->assertEquals((float) $linea->haber, (float) $inversa->debe);
        }
    }

    public function test_internal_transfer_does_not_create_financial_entry(): void
    {
        $kardex = $this->movimiento('transferencia_entrada', 1, 15);

        $this->assertFalse(AsientoContable::query()->where('documento_tipo', 'kardex')->where('documento_id', $kardex->id)->exists());
    }

    private function movimiento(string $tipo, float $cantidad, float $costo, bool $prepararStock = false): Kardex
    {
        if ($prepararStock) {
            $this->movimiento('inventario_inicial', 10, $costo);
        }

        return Kardex::registrarMovimiento([
            'articulo_id' => $this->articuloId,
            'almacen_id' => $this->almacenId,
            'tipo_movimiento' => $tipo,
            'cantidad' => $cantidad,
            'costo_unitario' => $costo,
            'documento_tipo' => 'manual',
            'documento_id' => 0,
            'fecha_movimiento' => now(),
            'fecha_contable' => now(),
            'empresa_id' => $this->empresaId,
            'motivo' => 'Prueba automática',
        ]);
    }
}
