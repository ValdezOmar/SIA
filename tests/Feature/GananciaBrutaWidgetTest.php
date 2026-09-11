<?php

namespace Tests\Feature;

use App\Exports\AnalisisComercialVentasExport;
use ReflectionMethod;
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
        $method = new ReflectionMethod($widget, 'getTableQuery');
        $method->setAccessible(true);
        $fila = $method->invoke($widget)->firstOrFail();

        $this->assertEqualsWithDelta(796, (float) $fila->ingresos_netos, 0.000001);
        $this->assertEqualsWithDelta(89.6, (float) $fila->descuentos, 0.000001);
        $this->assertEqualsWithDelta(140, (float) $fila->costo_ventas, 0.000001);
        $this->assertEqualsWithDelta(656, (float) $fila->ganancia_despues_costo, 0.000001);
        $this->assertEqualsWithDelta(82.4120603, ((float) $fila->ganancia_despues_costo / (float) $fila->ingresos_netos) * 100, 0.000001);
    }

    public function test_ganancia_del_widget_never_returns_a_negative_value(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa sin margen', 'nombre_comercial' => 'Sin margen',
            'pais' => 'Bolivia', 'empresa_activo' => true,
        ]);
        $cliente = Cliente::create(['codigo' => 'CLI-PER', 'nombre' => 'Cliente', 'empresa_id' => $empresa]);
        $almacen = DB::table('alm_almacenes')->insertGetId([
            'codigo' => 'ALM-PER', 'nombre' => 'Principal', 'empresa_id' => $empresa, 'activo' => true,
        ]);
        $articulo = DB::table('alm_articulos')->insertGetId([
            'codigo' => 'ART-PER', 'nombre_comercial' => 'Producto', 'empresa_id' => $empresa,
            'inventariable' => true, 'metodo_costo' => 'promedio', 'activo' => true,
        ]);
        $factura = $this->factura($cliente->id, $empresa, 'FAC-PER', 20, 0, 1, 'BOB');
        $this->asientoVenta($factura, $empresa, 'ASI-PER');
        $this->kardexVenta($factura->id, $articulo, $almacen, $empresa, 30);

        $widget = app(GananciaBrutaWidget::class);
        $method = new ReflectionMethod($widget, 'getTableQuery');
        $method->setAccessible(true);

        $this->assertSame(0.0, (float) $method->invoke($widget)->firstOrFail()->ganancia_despues_costo);
    }

    public function test_loss_on_one_invoice_does_not_reduce_profit_from_another_invoice(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa mensual', 'nombre_comercial' => 'Mensual',
            'pais' => 'Bolivia', 'empresa_activo' => true,
        ]);
        $cliente = Cliente::create(['codigo' => 'CLI-MES', 'nombre' => 'Cliente', 'empresa_id' => $empresa]);
        $almacen = DB::table('alm_almacenes')->insertGetId([
            'codigo' => 'ALM-MES', 'nombre' => 'Principal', 'empresa_id' => $empresa, 'activo' => true,
        ]);
        $articulo = DB::table('alm_articulos')->insertGetId([
            'codigo' => 'ART-MES', 'nombre_comercial' => 'Producto', 'empresa_id' => $empresa,
            'inventariable' => true, 'metodo_costo' => 'promedio', 'activo' => true,
        ]);
        $rentable = $this->factura($cliente->id, $empresa, 'FAC-POS', 100, 0, 1, 'BOB');
        $perdida = $this->factura($cliente->id, $empresa, 'FAC-NEG', 20, 0, 1, 'BOB');
        $this->asientoVenta($rentable, $empresa, 'ASI-POS');
        $this->asientoVenta($perdida, $empresa, 'ASI-NEG');
        $this->kardexVenta($rentable->id, $articulo, $almacen, $empresa, 20);
        $this->kardexVenta($perdida->id, $articulo, $almacen, $empresa, 30);

        $widget = app(GananciaBrutaWidget::class);
        $method = new ReflectionMethod($widget, 'getTableQuery');
        $method->setAccessible(true);

        $gananciaWidget = (float) $method->invoke($widget)->firstOrFail()->ganancia_despues_costo;
        $gananciaExcel = (float) (new AnalisisComercialVentasExport(now()->format('Y-m'), null))
            ->collection()
            ->sum(fn (array $fila): float => (float) $fila[8]);

        $this->assertSame(80.0, $gananciaWidget);
        $this->assertSame($gananciaWidget, $gananciaExcel);
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
        $venta = Factura::findOrFail($factura);
        $detalleId = DB::table('ven_facturas_detalle')->insertGetId([
            'factura_id' => $factura, 'articulo_id' => $articulo,
            'codigo_articulo' => 'ART-DET-'.$factura, 'descripcion_articulo' => 'Producto de prueba',
            'cantidad' => 1, 'precio_unitario' => $venta->subtotal, 'precio_original' => $venta->subtotal,
            'descuento' => $venta->descuento, 'subtotal' => $venta->subtotal, 'total' => $venta->total,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('alm_kardex')->insert([
            'articulo_id' => $articulo, 'almacen_id' => $almacen, 'tipo_movimiento' => 'venta',
            'direccion' => 'salida', 'cantidad' => 1, 'costo_total' => $costo,
            'documento_tipo' => 'venta', 'documento_id' => $factura, 'documento_detalle_id' => $detalleId, 'estado' => 'confirmado',
            'fecha_movimiento' => now(), 'empresa_id' => $empresa, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
