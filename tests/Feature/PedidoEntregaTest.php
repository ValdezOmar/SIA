<?php

namespace Tests\Feature;

use App\Models\Inventario\Articulo;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pedido;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class PedidoEntregaTest extends TestCase
{
    use RefreshDatabase;

    private Pedido $pedido;

    private Factura $factura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Entrega', 'nombre_comercial' => 'Entrega', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $cliente = Cliente::create(['codigo' => 'CLI-ENT', 'nombre' => 'Cliente entrega', 'empresa_id' => $empresa]);
        $articulo = Articulo::create(['codigo' => 'SRV-ENT', 'nombre_comercial' => 'Servicio', 'empresa_id' => $empresa, 'inventariable' => false]);
        $this->pedido = Pedido::create(['codigo' => 'PED-ENT', 'cliente_id' => $cliente->id, 'empresa_id' => $empresa, 'fecha_pedido' => '2026-10-05', 'estado' => 'pendiente', 'total' => 100]);
        $this->factura = Factura::create(['numero' => 'FAC-ENT', 'pedido_id' => $this->pedido->id, 'cliente_id' => $cliente->id, 'empresa_id' => $empresa, 'fecha_emision' => '2026-10-05', 'fecha_vencimiento' => '2026-10-05', 'condicion_pago' => 'parcial', 'moneda' => 'BOB', 'total' => 100, 'subtotal' => 100, 'saldo' => 100]);
        $this->factura->detalles()->create(['articulo_id' => $articulo->id, 'codigo_articulo' => 'SRV-ENT', 'descripcion_articulo' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 100]);
    }

    public function test_saldo_guardado_en_cero_no_permite_entregar_sin_pagos(): void
    {
        DB::table('ven_facturas')->where('id', $this->factura->id)->update(['saldo' => 0]);
        try {
            $this->pedido->confirmarEntrega();
            $this->fail('Debe bloquear la deuda aunque el saldo almacenado sea cero.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('registrar_pago', $exception->errors());
        }
        $this->assertSame('pendiente', $this->pedido->fresh()->estado);
        $this->assertSame(0, $this->factura->pagos()->count());
    }

    public function test_pago_completo_se_asocia_y_entrega_sin_duplicar_cobro(): void
    {
        $resultado = $this->pedido->confirmarEntrega(['monto' => 100, 'tipo_pago' => 'qr', 'referencia' => 'QR-ENT', 'fecha_pago' => '2026-10-05']);
        $this->assertSame($this->factura->id, $resultado['pago']->factura_id);
        $this->assertSame('QR-ENT', $resultado['pago']->referencia);
        $this->assertSame('entregado', $this->pedido->fresh()->estado);
        $this->assertSame(0.0, (float) $this->factura->fresh()->saldo);
        try {
            $this->pedido->confirmarEntrega(['monto' => 100, 'tipo_pago' => 'efectivo']);
            $this->fail('No se debe repetir una entrega.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('ya fue entregado', $exception->getMessage());
        }
        $this->assertSame(1, $this->factura->pagos()->count());
    }

    public function test_abono_parcial_no_se_cobra_desde_la_accion_de_entrega(): void
    {
        try {
            $this->pedido->confirmarEntrega(['monto' => 40, 'tipo_pago' => 'efectivo']);
            $this->fail('Un abono no debe habilitar entrega.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('monto', $exception->errors());
        }
        $this->assertSame(0, $this->factura->pagos()->count());
        $this->assertSame('pendiente', $this->pedido->fresh()->estado);
    }

    public function test_fallo_de_inventario_revierte_pago_y_contabilidad(): void
    {
        $this->factura->detalles()->first()->articulo->update(['inventariable' => true]);
        try {
            $this->pedido->confirmarEntrega(['monto' => 100, 'tipo_pago' => 'efectivo']);
            $this->fail('No existe almacén para entregar.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('almacén', $exception->getMessage());
        }
        $this->assertSame(0, $this->factura->pagos()->count());
        $this->assertSame(0, DB::table('con_asientos_contables')->count());
        $this->assertSame('pendiente', $this->pedido->fresh()->estado);
    }

    public function test_modal_registra_pago_y_notifica_la_entrega(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('dashboard'));
        \Livewire\Livewire::test(\App\Filament\Resources\Ventas\PedidoResource\Pages\ListPedidos::class)
            ->callTableAction('confirmar_entrega', $this->pedido, data: [
                'registrar_pago' => true, 'monto' => 100, 'tipo_pago' => 'efectivo', 'fecha_pago' => '2026-10-05',
            ])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Pedido PED-ENT: entrega confirmada');
        $this->assertSame('entregado', $this->pedido->fresh()->estado);
        $this->assertSame(1, $this->factura->pagos()->count());
    }

    public function test_factura_cubierta_no_genera_otro_pago(): void
    {
        $this->factura->pagos()->create(['cliente_id' => $this->factura->cliente_id, 'fecha_pago' => '2026-10-05', 'tipo_pago' => 'efectivo', 'monto' => 100, 'moneda' => 'BOB', 'estado' => 'confirmado']);
        $resultado = $this->pedido->confirmarEntrega();
        $this->assertNull($resultado['pago']);
        $this->assertSame(1, $this->factura->pagos()->count());
        $this->assertSame('entregado', $this->pedido->fresh()->estado);
    }

    public function test_cancelar_pedido_anula_factura_pago_y_asiento_del_cobro(): void
    {
        $pago = $this->factura->registrarPago(['monto' => 40, 'tipo_pago' => 'efectivo', 'fecha_pago' => '2026-10-05']);
        $resultado = $this->pedido->cancelar('Cliente desistió de la compra');
        $this->assertSame('cancelado', $this->pedido->fresh()->estado);
        $this->assertSame('anulada', $this->factura->fresh()->estado);
        $this->assertSame('anulado', $pago->fresh()->estado);
        $this->assertSame(0.0, (float) $this->factura->fresh()->monto_pagado);
        $this->assertSame(40.0, $resultado['importes_revertidos']['BOB']);
        $this->assertSame(1, $resultado['pagos_anulados']);
        $this->assertSame(['FAC-ENT'], $resultado['facturas']);
        $this->assertSame('anulado', $pago->asientoContable()->first()->estado);
        $this->assertStringContainsString('Cliente desistió', $this->pedido->fresh()->observaciones);
    }

    public function test_cancelar_sin_factura_y_rechazar_segunda_cancelacion(): void
    {
        $this->factura->update(['pedido_id' => null]);
        $resultado = $this->pedido->cancelar('Sin compra');
        $this->assertSame([], $resultado['facturas']);
        $this->assertSame('cancelado', $this->pedido->fresh()->estado);
        $this->assertNotSame('anulada', $this->factura->fresh()->estado);
        $this->expectException(RuntimeException::class);
        $this->pedido->cancelar('Reintento');
    }

    public function test_modal_exige_aceptar_consecuencias_y_notifica_cancelacion(): void
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('dashboard'));
        $pago = $this->factura->registrarPago(['monto' => 40, 'tipo_pago' => 'qr', 'fecha_pago' => '2026-10-05']);
        \Livewire\Livewire::test(\App\Filament\Resources\Ventas\PedidoResource\Pages\ListPedidos::class)
            ->callTableAction('cancelar', $this->pedido, data: ['motivo' => 'Cancelación solicitada', 'acepto_consecuencias' => false])
            ->assertHasTableActionErrors(['acepto_consecuencias']);
        $this->assertNotSame('cancelado', $this->pedido->fresh()->estado);
        $this->assertSame('confirmado', $pago->fresh()->estado);
        \Livewire\Livewire::test(\App\Filament\Resources\Ventas\PedidoResource\Pages\ListPedidos::class)
            ->callTableAction('cancelar', $this->pedido, data: ['motivo' => 'Cancelación solicitada', 'acepto_consecuencias' => true])
            ->assertHasNoTableActionErrors()
            ->assertNotified('Pedido PED-ENT cancelado');
        $this->assertSame('anulada', $this->factura->fresh()->estado);
        $this->assertSame('anulado', $pago->fresh()->estado);
    }

    public function test_fallo_al_anular_otra_factura_revierte_toda_la_cancelacion(): void
    {
        $pago = $this->factura->registrarPago(['monto' => 40, 'tipo_pago' => 'efectivo', 'fecha_pago' => '2026-10-05']);
        $otra = $this->factura->replicate();
        $otra->numero = 'FAC-ENT-2';
        $otra->save();
        $dispatcher = Factura::getEventDispatcher();
        Factura::setEventDispatcher(clone $dispatcher);
        Factura::updating(function (Factura $factura) use ($otra): void {
            if ($factura->id === $otra->id && $factura->estado === 'anulada') {
                throw new RuntimeException('No se pudo anular la segunda factura.');
            }
        });
        try {
            $this->pedido->cancelar('Prueba de reversión');
            $this->fail('La cancelación debe revertirse por completo.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('segunda factura', $exception->getMessage());
        } finally {
            Factura::setEventDispatcher($dispatcher);
        }
        $this->assertNotSame('cancelado', $this->pedido->fresh()->estado);
        $this->assertNotSame('anulada', $this->factura->fresh()->estado);
        $this->assertNotSame('anulada', $otra->fresh()->estado);
        $this->assertSame('confirmado', $pago->fresh()->estado);
        $this->assertSame('confirmado', $pago->asientoContable()->first()->estado);
    }
}
