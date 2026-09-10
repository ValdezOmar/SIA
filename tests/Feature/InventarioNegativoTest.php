<?php

namespace Tests\Feature;

use Exception;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventarioNegativoTest extends TestCase
{
    use RefreshDatabase;

    private Almacen $almacen;

    private Articulo $articulo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa inventario', 'nombre_comercial' => 'Empresa inventario',
            'pais' => 'Bolivia', 'empresa_activo' => true,
        ]);
        $this->almacen = Almacen::create([
            'codigo' => 'ALM-NEG', 'nombre' => 'Almacén de prueba', 'empresa_id' => $empresa, 'activo' => true,
        ]);
        $this->articulo = Articulo::create([
            'codigo' => 'ART-NEG', 'nombre_comercial' => 'Artículo de prueba', 'empresa_id' => $empresa,
            'inventariable' => true, 'metodo_costo' => 'promedio', 'costo_estandar' => 12,
        ]);
    }

    public function test_almacen_sin_permiso_rechaza_la_salida_que_deja_stock_negativo(): void
    {
        $this->expectException(Exception::class);
        $this->salida(1);
    }

    public function test_salida_negativa_registra_kardex_y_asiento_con_costo_provisional(): void
    {
        $this->almacen->update(['permite_inventario_negativo' => true]);
        $kardex = $this->salida(3);

        $this->assertSame(-3.0, (float) Existencia::firstOrFail()->cantidad_disponible);
        $this->assertSame(36.0, (float) $kardex->costo_total);
        $this->assertTrue($kardex->datos_adicionales['inventario_negativo']);
        $this->assertSame(3.0, (float) $kardex->datos_adicionales['cantidad_negativa']);

        $asiento = $kardex->asientoContable()->with('detalles.cuenta')->firstOrFail();
        $this->assertSame('confirmado', $asiento->estado);
        $this->assertSame(36.0, (float) $asiento->total_debe);
        $this->assertSame(36.0, (float) $asiento->total_haber);
        $this->assertSame(['6.1', '1.1.5'], $asiento->detalles->pluck('cuenta.codigo')->all());
    }

    public function test_entrada_posterior_conserva_el_valor_contable_del_stock_negativo(): void
    {
        $this->almacen->update(['permite_inventario_negativo' => true]);
        $this->salida(3);
        Kardex::registrarMovimiento([
            'articulo_id' => $this->articulo->id, 'almacen_id' => $this->almacen->id,
            'tipo_movimiento' => 'compra', 'cantidad' => 5, 'costo_unitario' => 20,
            'documento_tipo' => 'manual', 'documento_id' => 0, 'empresa_id' => $this->almacen->empresa_id,
        ]);

        $existencia = Existencia::firstOrFail();
        $this->assertSame(2.0, (float) $existencia->cantidad_disponible);
        $this->assertSame(64.0, (float) $existencia->costo_acumulado);
        $this->assertSame(32.0, (float) $existencia->costo_promedio);
        $this->assertSame(2, AsientoContable::where('documento_tipo', 'kardex')->where('estado', 'confirmado')->count());
    }

    public function test_venta_con_stock_negativo_registra_el_mismo_costo_en_su_asiento(): void
    {
        $this->almacen->update(['permite_inventario_negativo' => true]);
        $cliente = Cliente::create([
            'codigo' => 'CLI-NEG', 'nombre' => 'Cliente de prueba', 'empresa_id' => $this->almacen->empresa_id,
        ]);
        $factura = Factura::create([
            'numero' => 'FAC-NEG', 'cliente_id' => $cliente->id, 'empresa_id' => $this->almacen->empresa_id,
            'fecha_emision' => today(), 'fecha_vencimiento' => today(), 'condicion_pago' => 'contado',
            'moneda' => 'BOB', 'subtotal' => 200, 'total' => 200,
        ]);
        $factura->detalles()->create([
            'articulo_id' => $this->articulo->id, 'codigo_articulo' => $this->articulo->codigo,
            'descripcion_articulo' => $this->articulo->nombre_comercial, 'cantidad' => 2, 'precio_unitario' => 100,
        ]);

        $factura->crearPagoAutomaticoSiEsContado();

        $kardex = Kardex::where('documento_tipo', 'venta')->firstOrFail();
        $asiento = AsientoContable::where('documento_tipo', 'venta')->where('documento_id', $factura->id)->firstOrFail();
        $this->assertSame(-2.0, (float) Existencia::firstOrFail()->cantidad_disponible);
        $this->assertSame(24.0, (float) $kardex->costo_total);
        $this->assertSame(224.0, (float) $asiento->total_debe);
        $this->assertSame(224.0, (float) $asiento->total_haber);
        $this->assertSame(24.0, (float) $asiento->detalles()->where('debe', '>', 0)->whereHas('cuenta', fn ($query) => $query->where('codigo', '6.1'))->value('debe'));
    }

    private function salida(float $cantidad): Kardex
    {
        return Kardex::registrarMovimiento([
            'articulo_id' => $this->articulo->id, 'almacen_id' => $this->almacen->id,
            'tipo_movimiento' => 'venta', 'cantidad' => $cantidad,
            'documento_tipo' => 'manual', 'documento_id' => 0, 'empresa_id' => $this->almacen->empresa_id,
        ]);
    }
}
