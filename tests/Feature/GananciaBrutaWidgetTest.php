<?php

namespace Tests\Feature;

use App\Filament\Widgets\GananciaBrutaWidget;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GananciaBrutaWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_suma_solo_ventas_contabilizadas_y_muestra_importes_en_bolivianos(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba', 'nombre_comercial' => 'Empresa de prueba',
            'pais' => 'Bolivia', 'empresa_activo' => true,
        ]);
        $cliente = Cliente::create(['codigo' => 'CLI-GAN', 'nombre' => 'Cliente', 'empresa_id' => $empresa]);
        $almacen = DB::table('alm_almacenes')->insertGetId([
            'codigo' => 'ALM-GAN', 'nombre' => 'Principal', 'empresa_id' => $empresa, 'activo' => true,
        ]);
        $articulo = DB::table('alm_articulos')->insertGetId([
            'codigo' => 'ART-GAN', 'nombre_comercial' => 'Producto', 'empresa_id' => $empresa,
            'inventariable' => true, 'metodo_costo' => 'promedio', 'activo' => true,
        ]);

        $bolivianos = $this->factura($cliente->id, $empresa, 'FAC-BOB', 100, 20, 1, 'BOB');
        $dolares = $this->factura($cliente->id, $empresa, 'FAC-USD', 100, 10, 6.96, 'USD');
        $borrador = $this->factura($cliente->id, $empresa, 'FAC-BOR', 999, 99, 1, 'BOB');
        $anulada = $this->factura($cliente->id, $empresa, 'FAC-ANU', 999, 99, 1, 'BOB', 'anulada');

        $this->asientoVenta($bolivianos, $empresa, 'ASI-BOB');
        $this->asientoVenta($dolares, $empresa, 'ASI-USD');
        $this->asientoVenta($anulada, $empresa, 'ASI-ANU');
        $this->kardexVenta($bolivianos->id, $articulo, $almacen, $empresa, 40);
        $this->kardexVenta($dolares->id, $articulo, $almacen, $empresa, 100);
        $this->kardexVenta($borrador->id, $articulo, $almacen, $empresa, 999);

        $widget = app(GananciaBrutaWidget::class);
        $method = new \ReflectionMethod($widget, 'getTableQuery');
        $method->setAccessible(true);
        $fila = $method->invoke($widget)->firstOrFail();

        $this->assertEqualsWithDelta(796, (float) $fila->ingresos_netos, 0.000001);
        $this->assertEqualsWithDelta(89.6, (float) $fila->descuentos, 0.000001);
        $this->assertEqualsWithDelta(140, (float) $fila->costo_ventas, 0.000001);
        $this->assertEqualsWithDelta(656, (float) $fila->ganancia_despues_costo, 0.000001);
        $this->assertEqualsWithDelta(82.4120603, ((float) $fila->ganancia_despues_costo / (float) $fila->ingresos_netos) * 100, 0.000001);
    }

    private function factura(int $cliente, int $empresa, string $numero, float $subtotal, float $descuento, float $tasa, string $moneda, string $estado = 'pagada'): Factura
    {
        return Factura::create([
            'numero' => $numero, 'cliente_id' => $cliente, 'empresa_id' => $empresa,
            'fecha_emision' => now(), 'condicion_pago' => 'contado', 'moneda' => $moneda,
            'tasa_cambio' => $tasa, 'subtotal' => $subtotal, 'descuento' => $descuento,
            'total' => $subtotal, 'estado' => $estado,
        ]);
    }

    private function asientoVenta(Factura $factura, int $empresa, string $codigo): void
    {
        DB::table('con_asientos_contables')->insert([
            'codigo' => $codigo, 'fecha_asiento' => now(), 'fecha_contable' => now(),
            'documento_tipo' => 'venta', 'documento_id' => $factura->id, 'tipo' => 'venta',
            'estado' => 'confirmado', 'empresa_id' => $empresa, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function kardexVenta(int $factura, int $articulo, int $almacen, int $empresa, float $costo): void
    {
        DB::table('alm_kardex')->insert([
            'articulo_id' => $articulo, 'almacen_id' => $almacen, 'tipo_movimiento' => 'venta',
            'direccion' => 'salida', 'cantidad' => 1, 'costo_total' => $costo,
            'documento_tipo' => 'venta', 'documento_id' => $factura, 'estado' => 'confirmado',
            'fecha_movimiento' => now(), 'empresa_id' => $empresa, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
