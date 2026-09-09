<?php

namespace Tests\Feature;

use App\Filament\Widgets\AnalisisComercialWidget;
use App\Models\Inventario\Articulo;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use App\Models\Ventas\FacturaDetalle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AnalisisComercialWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_los_ranking_mensuales_solo_para_ventas_contabilizadas(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba', 'nombre_comercial' => 'Empresa de prueba',
            'pais' => 'Bolivia', 'empresa_activo' => true,
        ]);
        $clienteUno = Cliente::create(['codigo' => 'CLI-1', 'nombre' => 'Cliente Uno', 'empresa_id' => $empresa]);
        $clienteDos = Cliente::create(['codigo' => 'CLI-2', 'nombre' => 'Cliente Dos', 'empresa_id' => $empresa]);
        $almacen = DB::table('alm_almacenes')->insertGetId(['codigo' => 'ALM-1', 'nombre' => 'Principal', 'empresa_id' => $empresa, 'activo' => true]);
        $articuloA = Articulo::create(['codigo' => 'ART-A', 'nombre_comercial' => 'Producto A', 'empresa_id' => $empresa, 'inventariable' => true]);
        $articuloB = Articulo::create(['codigo' => 'ART-B', 'nombre_comercial' => 'Producto B', 'empresa_id' => $empresa, 'inventariable' => true]);

        $facturaA = $this->factura($empresa, $clienteUno->id, 'FAC-A', 80);
        $facturaB = $this->factura($empresa, $clienteDos->id, 'FAC-B', 50);
        $excluida = $this->factura($empresa, $clienteUno->id, 'FAC-X', 999, 'anulada');
        $detalleA = $this->detalle($facturaA, $articuloA, 10, 80);
        $detalleB = $this->detalle($facturaB, $articuloB, 1, 50);
        $this->detalle($excluida, $articuloA, 100, 999);
        $this->asiento($facturaA, $empresa, 'ASI-A');
        $this->asiento($facturaB, $empresa, 'ASI-B');
        $this->asiento($excluida, $empresa, 'ASI-X');
        $this->kardex($facturaA, $detalleA, $articuloA, $almacen, $empresa, 50);
        $this->kardex($facturaB, $detalleB, $articuloB, $almacen, $empresa, 5);

        $widget = app(AnalisisComercialWidget::class);
        $widget->periodo = now()->format('Y-m');

        $widget->pestana = 'productos_vendidos';
        $vendidos = $widget->filas();
        $this->assertSame('ART-A · PRODUCTO A', $vendidos[0]['producto']);
        $this->assertEqualsWithDelta(10, $vendidos[0]['unidades'], 0.000001);

        $widget->pestana = 'productos_rentables';
        $rentables = $widget->filas();
        $this->assertSame('ART-B · PRODUCTO B', $rentables[0]['producto']);
        $this->assertEqualsWithDelta(45, $rentables[0]['ganancia'], 0.000001);

        $widget->pestana = 'clientes';
        $clientes = $widget->filas();
        $this->assertSame('CLIENTE UNO', $clientes[0]['cliente']);
        $this->assertEqualsWithDelta(80, $clientes[0]['compra_neta'], 0.000001);

        $widget->pestana = 'resumen';
        $resumen = $widget->filas();
        $this->assertEqualsWithDelta(130, $resumen[0]['valor'], 0.000001);
        $this->assertEqualsWithDelta(11, $resumen[4]['valor'], 0.000001);
    }

    public function test_se_renderiza_sin_carga_diferida_para_inicializar_el_periodo(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(AnalisisComercialWidget::class)
            ->assertSee('Análisis comercial mensual')
            ->assertSet('periodo', now()->format('Y-m'));
    }

    private function factura(int $empresa, int $cliente, string $numero, float $subtotal, string $estado = 'pagada'): Factura
    {
        return Factura::create([
            'numero' => $numero, 'cliente_id' => $cliente, 'empresa_id' => $empresa,
            'fecha_emision' => now(), 'condicion_pago' => 'contado', 'moneda' => 'BOB',
            'tasa_cambio' => 1, 'subtotal' => $subtotal, 'descuento' => 0,
            'total' => $subtotal, 'estado' => $estado,
        ]);
    }

    private function detalle(Factura $factura, Articulo $articulo, float $cantidad, float $subtotal): FacturaDetalle
    {
        return FacturaDetalle::create([
            'factura_id' => $factura->id, 'articulo_id' => $articulo->id,
            'codigo_articulo' => $articulo->codigo, 'descripcion_articulo' => $articulo->nombre_comercial,
            'unidad_medida' => 'UND', 'cantidad' => $cantidad, 'precio_unitario' => $subtotal / $cantidad,
            'subtotal' => $subtotal, 'total' => $subtotal,
        ]);
    }

    private function asiento(Factura $factura, int $empresa, string $codigo): void
    {
        DB::table('con_asientos_contables')->insert([
            'codigo' => $codigo, 'fecha_asiento' => now(), 'fecha_contable' => now(),
            'documento_tipo' => 'venta', 'documento_id' => $factura->id, 'tipo' => 'venta',
            'estado' => 'confirmado', 'empresa_id' => $empresa, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function kardex(Factura $factura, FacturaDetalle $detalle, Articulo $articulo, int $almacen, int $empresa, float $costo): void
    {
        DB::table('alm_kardex')->insert([
            'articulo_id' => $articulo->id, 'almacen_id' => $almacen, 'tipo_movimiento' => 'venta',
            'direccion' => 'salida', 'cantidad' => $detalle->cantidad, 'costo_total' => $costo,
            'documento_tipo' => 'venta', 'documento_id' => $factura->id, 'documento_detalle_id' => $detalle->id,
            'estado' => 'confirmado', 'fecha_movimiento' => now(), 'empresa_id' => $empresa,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
