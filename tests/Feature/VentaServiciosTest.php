<?php

namespace Tests\Feature;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VentaServiciosTest extends TestCase
{
    use RefreshDatabase;

    private Factura $factura;

    private Articulo $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Servicios', 'nombre_comercial' => 'Servicios', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $cliente = Cliente::create(['codigo' => 'CLI-SRV', 'nombre' => 'Cliente', 'empresa_id' => $empresa]);
        $this->servicio = Articulo::create(['codigo' => 'SRV', 'nombre_comercial' => 'Instalación', 'empresa_id' => $empresa, 'inventariable' => false, 'maneja_series' => true, 'requiere_serie_en_salida' => true]);
        $this->factura = Factura::create(['numero' => 'FAC-SRV', 'cliente_id' => $cliente->id, 'empresa_id' => $empresa, 'fecha_emision' => today(), 'condicion_pago' => 'contado', 'moneda' => 'BOB', 'total' => 100, 'subtotal' => 100]);
        $this->factura->detalles()->create(['articulo_id' => $this->servicio->id, 'codigo_articulo' => 'SRV', 'descripcion_articulo' => 'Instalación', 'cantidad' => 1, 'precio_unitario' => 100]);
    }

    public function test_servicio_se_vende_sin_almacen_y_conserva_contabilidad(): void
    {
        $this->factura->crearPagoAutomaticoSiEsContado();
        $this->assertSame(0.0, (float) $this->factura->fresh()->saldo);
        $this->assertSame(0, Kardex::count());
        $this->assertSame(0, MovimientoInventario::count());
        $this->assertSame(0, Existencia::count());
        $asiento = AsientoContable::where('documento_tipo', 'venta')->firstOrFail();
        $this->assertSame(100.0, (float) $asiento->detalles()->sum('debe'));
        $this->assertSame(100.0, (float) $asiento->detalles()->sum('haber'));
        $this->assertFalse($this->servicio->fresh()->maneja_series);
        $this->assertFalse($this->servicio->fresh()->requiere_serie_en_salida);
    }

    public function test_servicio_admite_pagos_parciales_sin_reservar_stock(): void
    {
        $this->factura->update(['condicion_pago' => 'parcial']);
        $this->factura->reservarInventario();
        $this->factura->registrarPago(['monto' => 40, 'fecha_pago' => today(), 'tipo_pago' => 'efectivo']);
        $this->assertSame(60.0, (float) $this->factura->fresh()->saldo);
        $this->assertSame(0, MovimientoInventario::count());
        $this->factura->registrarPago(['monto' => 60, 'fecha_pago' => today(), 'tipo_pago' => 'efectivo']);
        $this->assertSame(0.0, (float) $this->factura->fresh()->saldo);
        $this->assertSame('entregado', $this->factura->fresh()->pedido->estado);
        $this->assertSame(0, Kardex::count());
    }

    public function test_venta_mixta_reserva_y_descuenta_solo_producto(): void
    {
        $almacen = Almacen::create(['codigo' => 'ALM', 'nombre' => 'Principal', 'empresa_id' => $this->factura->empresa_id, 'activo' => true]);
        $producto = Articulo::create(['codigo' => 'PRD', 'nombre_comercial' => 'Equipo', 'empresa_id' => $this->factura->empresa_id, 'metodo_costo' => 'promedio']);
        $stock = Existencia::create(['articulo_id' => $producto->id, 'almacen_id' => $almacen->id, 'cantidad_disponible' => 10, 'cantidad_comprometida' => 0, 'costo_promedio' => 10, 'costo_acumulado' => 100]);
        $stockServicio = Existencia::create(['articulo_id' => $this->servicio->id, 'almacen_id' => $almacen->id, 'cantidad_disponible' => 5, 'cantidad_comprometida' => 0]);
        $this->factura->detalles()->create(['articulo_id' => $producto->id, 'codigo_articulo' => 'PRD', 'descripcion_articulo' => 'Equipo', 'cantidad' => 1, 'precio_unitario' => 100]);
        $this->factura->update(['condicion_pago' => 'parcial', 'total' => 200, 'subtotal' => 200]);
        $this->factura->registrarPago(['monto' => 40, 'fecha_pago' => today(), 'tipo_pago' => 'efectivo']);
        $this->assertSame(1.0, (float) $stock->fresh()->cantidad_comprometida);
        $this->assertSame(0.0, (float) $stockServicio->fresh()->cantidad_comprometida);
        $this->factura->registrarPago(['monto' => 160, 'fecha_pago' => today(), 'tipo_pago' => 'efectivo']);
        $this->assertSame(9.0, (float) $stock->fresh()->cantidad_disponible);
        $this->assertSame(0.0, (float) $stock->fresh()->cantidad_comprometida);
        $this->assertSame(5.0, (float) $stockServicio->fresh()->cantidad_disponible);
        $this->assertSame(0, Kardex::where('articulo_id', $this->servicio->id)->count());
        $this->assertSame(10.0, (float) Kardex::where('documento_tipo', 'venta')->sum('costo_total'));
    }

    public function test_kardex_rechaza_salida_manual_de_un_servicio(): void
    {
        $this->expectException(ValidationException::class);
        Kardex::registrarSalida(['articulo_id' => $this->servicio->id, 'almacen_id' => 1, 'cantidad' => 1]);
    }

    public function test_cambiar_a_servicio_libera_reserva_anterior_al_completar_venta(): void
    {
        $almacen = Almacen::create(['codigo' => 'ALM', 'nombre' => 'Principal', 'empresa_id' => $this->factura->empresa_id, 'activo' => true]);
        $this->servicio->update(['inventariable' => true]);
        $stock = Existencia::create(['articulo_id' => $this->servicio->id, 'almacen_id' => $almacen->id, 'cantidad_disponible' => 5, 'cantidad_comprometida' => 0]);
        $this->factura->update(['condicion_pago' => 'parcial']);
        $this->factura->registrarPago(['monto' => 40, 'fecha_pago' => today(), 'tipo_pago' => 'efectivo']);
        $this->assertSame(1.0, (float) $stock->fresh()->cantidad_comprometida);
        $this->servicio->update(['inventariable' => false]);
        $this->factura->registrarPago(['monto' => 60, 'fecha_pago' => today(), 'tipo_pago' => 'efectivo']);
        $this->assertSame(0.0, (float) $stock->fresh()->cantidad_comprometida);
        $this->assertSame(5.0, (float) $stock->fresh()->cantidad_disponible);
        $this->assertSame(0, Kardex::count());
    }

    public function test_recepcion_de_servicio_actualiza_compra_sin_ingresar_stock(): void
    {
        $proveedor = DB::table('cmp_proveedores')->insertGetId(['codigo' => 'PROV', 'nombre' => 'Proveedor', 'empresa_id' => $this->factura->empresa_id]);
        $orden = DB::table('cmp_ordenes_compra')->insertGetId(['codigo' => 'OC-SRV', 'proveedor_id' => $proveedor, 'fecha_orden' => today(), 'empresa_id' => $this->factura->empresa_id]);
        $detalle = DB::table('cmp_ordenes_compra_detalle')->insertGetId(['orden_id' => $orden, 'articulo_id' => $this->servicio->id, 'linea' => 1, 'codigo_articulo' => 'SRV', 'descripcion_articulo' => 'Instalación', 'cantidad' => 1, 'precio_unitario' => 100, 'subtotal' => 100, 'total' => 100]);
        $recepcion = \App\Models\Compras\Recepcion::create(['codigo' => 'REC-SRV', 'orden_compra_id' => $orden, 'proveedor_id' => $proveedor, 'fecha_recepcion' => today(), 'empresa_id' => $this->factura->empresa_id]);
        $recepcion->detalles()->create(['orden_detalle_id' => $detalle, 'articulo_id' => $this->servicio->id, 'codigo_articulo' => 'SRV', 'descripcion_articulo' => 'Instalación', 'cantidad' => 1, 'cantidad_aceptada' => 1, 'costo_unitario' => 100]);
        $recepcion->procesarEntradaInventario();
        $this->assertNotNull($recepcion->fresh()->inventario_procesado_at);
        $this->assertSame(1.0, (float) DB::table('cmp_ordenes_compra_detalle')->where('id', $detalle)->value('cantidad_recibida'));
        $this->assertSame(0, Existencia::count());
        $this->assertSame(0, Kardex::count());
    }

    public function test_kardex_rechaza_entrada_manual_de_un_servicio(): void
    {
        $this->expectException(ValidationException::class);
        Kardex::registrarEntrada(['articulo_id' => $this->servicio->id, 'almacen_id' => 1, 'cantidad' => 1]);
    }
}
