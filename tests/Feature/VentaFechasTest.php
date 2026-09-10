<?php

namespace Tests\Feature;

use RuntimeException;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Inventario\Serie;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class VentaFechasTest extends TestCase
{
    use RefreshDatabase;

    private Factura $factura;

    private Existencia $stock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 9, 7)->setTime(12, 0));
        $this->actingAs(User::factory()->create());
        $empresaId = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Empresa fechas', 'nombre_comercial' => 'Empresa fechas', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $cliente = Cliente::create(['codigo' => 'CLI-FECHAS', 'nombre' => 'Cliente fechas', 'empresa_id' => $empresaId]);
        $almacen = Almacen::create(['codigo' => 'ALM-FECHAS', 'nombre' => 'Principal', 'empresa_id' => $empresaId, 'activo' => true]);
        $articulo = Articulo::create(['codigo' => 'ART-FECHAS', 'nombre_comercial' => 'Equipo', 'empresa_id' => $empresaId, 'metodo_costo' => 'promedio', 'maneja_series' => true, 'requiere_serie_en_salida' => true]);
        Serie::create(['articulo_id' => $articulo->id, 'almacen_id' => $almacen->id, 'numero_serie' => 'SER-FECHAS', 'estado' => 'disponible']);
        $this->stock = Existencia::create(['articulo_id' => $articulo->id, 'almacen_id' => $almacen->id, 'cantidad_disponible' => 10, 'cantidad_comprometida' => 0, 'costo_promedio' => 10, 'costo_acumulado' => 100]);
        $this->factura = Factura::create([
            'numero' => 'FAC-FECHAS', 'fecha_emision' => '2025-12-20', 'fecha_pago' => '2025-12-18', 'fecha_vencimiento' => '2025-12-23',
            'cliente_id' => $cliente->id, 'empresa_id' => $empresaId, 'condicion_pago' => 'contado', 'moneda' => 'BOB', 'total' => 100, 'subtotal' => 100,
        ]);
        $this->factura->detalles()->create(['articulo_id' => $articulo->id, 'codigo_articulo' => $articulo->codigo, 'descripcion_articulo' => 'Equipo', 'cantidad' => 1, 'precio_unitario' => 100, 'series' => ['SER-FECHAS']]);
    }

    public function test_venta_pasada_propaga_fechas_a_contabilidad_inventario_pedido_y_serie(): void
    {
        $pago = $this->factura->crearPagoAutomaticoSiEsContado();
        $venta = AsientoContable::where('documento_tipo', 'venta')->firstOrFail();
        $this->assertSame('2025-12-20', $venta->fecha_asiento->toDateString());
        $this->assertSame('2025-12-20', $venta->fecha_contable->toDateString());
        $cobro = AsientoContable::where('documento_tipo', 'pago_cliente')->firstOrFail();
        $this->assertSame('2025-12-18', $pago->fecha_pago->toDateString());
        $this->assertSame('2025-12-18', $cobro->fecha_asiento->toDateString());
        $this->assertSame('2025-12-18', $cobro->fecha_contable->toDateString());
        $this->assertSame('2025-12-20', AsientoContable::where('documento_tipo', 'aplicacion_anticipos_cliente')->first()->fecha_asiento->toDateString());
        $kardex = Kardex::where('documento_tipo', 'venta')->firstOrFail();
        $this->assertSame('2025-12-23', $kardex->fecha_movimiento->toDateString());
        $this->assertSame('2025-12-20', $kardex->fecha_contable->toDateString());
        $this->assertSame('2025-12-23', MovimientoInventario::where('documento_tipo', 'venta')->first()->fecha->toDateString());
        $this->assertSame('2025-12-23', $this->stock->fresh()->ultima_salida->toDateString());
        $pedido = $this->factura->fresh()->pedido;
        $this->assertSame('2025-12-23', $pedido->fecha_pedido->toDateString());
        $this->assertSame('2025-12-23', $pedido->fecha_entrega_real->toDateString());
        $this->assertSame('2025-12-20', Serie::first()->fecha_venta);
        $this->assertSame('2026-09-07', $venta->created_at->toDateString());
        $this->assertDatabaseHas('con_saldos_cuentas', ['anio' => 2025, 'mes' => 12]);
        $this->assertDatabaseMissing('con_saldos_cuentas', ['anio' => 2026, 'mes' => 9]);
        $this->factura->procesarVentaAutomatica();
        $this->assertSame(1, Kardex::where('documento_tipo', 'venta')->count());
        $this->assertSame('2025-12-23', $pedido->fresh()->fecha_entrega_real->toDateString());
    }

    public function test_pagos_parciales_y_reservas_conservan_sus_fechas(): void
    {
        $this->factura->update(['condicion_pago' => 'parcial']);
        $this->factura->registrarPago(['monto' => 40, 'fecha_pago' => '2025-12-15', 'tipo_pago' => 'efectivo']);
        $this->assertSame('2025-12-15', MovimientoInventario::where('documento_tipo', 'pedido_reserva')->firstOrFail()->fecha->toDateString());
        $this->factura->registrarPago(['monto' => 60, 'fecha_pago' => '2025-12-22', 'tipo_pago' => 'efectivo']);
        $this->assertSame('2025-12-22', AsientoContable::where('documento_tipo', 'aplicacion_anticipos_cliente')->first()->fecha_asiento->toDateString());
        $this->assertSame(['2025-12-15', '2025-12-22'], AsientoContable::where('documento_tipo', 'pago_cliente')->orderBy('id')->get()->map(fn ($a) => $a->fecha_asiento->toDateString())->all());
    }

    public function test_periodo_pasado_cerrado_impide_venta_y_revierte_operacion(): void
    {
        $this->factura->update(['fecha_pago' => '2026-01-05', 'fecha_vencimiento' => '2026-01-05']);
        DB::table('con_periodos_contables')->insert(['empresa_id' => $this->factura->empresa_id, 'anio' => 2025, 'mes' => 12, 'fecha_inicio' => '2025-12-01', 'fecha_fin' => '2025-12-31', 'estado' => 'cerrado']);
        try {
            $this->factura->crearPagoAutomaticoSiEsContado();
            $this->fail('Debió rechazar el período cerrado.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('cerrado o bloqueado', $e->getMessage());
        }
        $this->assertSame(0, $this->factura->pagos()->count());
        $this->assertSame(0, AsientoContable::count());
        $this->assertSame(0, Kardex::count());
        $this->assertSame(10.0, (float) $this->stock->fresh()->cantidad_disponible);
    }

    public function test_fecha_contable_explicita_tiene_prioridad_en_asiento_de_venta(): void
    {
        $asiento = AsientoContable::crearDesdeVenta($this->factura, '2025-12-24');
        $this->assertSame('2025-12-24', $asiento->fecha_asiento->toDateString());
        $this->assertSame('2025-12-24', $asiento->fecha_contable->toDateString());
    }

    public function test_salida_retroactiva_no_reemplaza_la_fecha_de_una_salida_mas_reciente(): void
    {
        $this->factura->crearPagoAutomaticoSiEsContado();
        Kardex::registrarSalida([
            'articulo_id' => $this->stock->articulo_id, 'almacen_id' => $this->stock->almacen_id,
            'tipo_movimiento' => 'venta', 'cantidad' => 1, 'documento_tipo' => 'venta',
            'documento_id' => $this->factura->id, 'fecha_movimiento' => '2025-12-01',
        ]);
        $this->assertSame('2025-12-23', $this->stock->fresh()->ultima_salida->toDateString());
    }
}
