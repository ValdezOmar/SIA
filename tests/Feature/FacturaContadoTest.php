<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacturaContadoTest extends TestCase
{
    use RefreshDatabase;

    public function test_factura_contado_sets_dates_and_creates_automatic_payment(): void
    {
        $user = User::factory()->create();
        Auth::login($user);

        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba',
            'nombre_comercial' => 'Empresa Test',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $clienteId = DB::table('ven_clientes')->insertGetId([
            'codigo' => 'CLI-001',
            'nombre' => 'Cliente de prueba',
            'razon_social' => 'Cliente de prueba SRL',
            'ci/nit' => '1234567',
            'tipo_cliente' => 'empresa',
            'categoria' => 'regular',
            'condicion_pago' => 'contado',
            'descuento_general' => 0,
            'descuento_especial' => 0,
            'activo' => true,
            'bloqueado' => false,
            'empresa_id' => $empresaId,
            'creado_por' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $factura = Factura::create([
            'numero' => 'FAC-2026-0001',
            'serie' => 'F001',
            'fecha_emision' => now(),
            'fecha_vencimiento' => null,
            'fecha_pago' => null,
            'cliente_id' => $clienteId,
            'estado' => 'emitida',
            'condicion_pago' => 'contado',
            'moneda' => 'BOB',
            'tasa_cambio' => 1,
            'subtotal' => 100,
            'descuento' => 0,
            'impuesto' => 13,
            'total' => 113,
            'saldo' => 113,
            'monto_pagado' => 0,
            'monto_restante' => 113,
            'tipo_impuesto' => 'IVA',
            'tasa_impuesto' => 13,
            'empresa_id' => $empresaId,
            'vendedor_id' => $user->id,
            'creado_por' => $user->id,
        ]);

        $factura->update([
            'fecha_vencimiento' => '2026-08-20',
            'fecha_pago' => '2026-08-15',
        ]);

        $pago = $factura->crearPagoAutomaticoSiEsContado(['tipo_pago' => 'qr', 'referencia' => 'QR-TEST-001']);

        $this->assertNotNull($pago);
        $this->assertSame('pagada', $factura->fresh()->estado);
        $this->assertSame('qr', $pago->tipo_pago);
        $this->assertSame('2026-08-15', $factura->fresh()->fecha_pago->toDateString());
        $this->assertSame('2026-08-20', $factura->fresh()->fecha_vencimiento->toDateString());
        $this->assertSame('2026-08-15', $pago->fresh()->fecha_pago->toDateString());
        $this->assertSame('2026-08-20', $factura->fresh()->pedido->fecha_pedido->toDateString());
        $this->assertDatabaseHas('ven_pagos', [
            'factura_id' => $factura->id,
            'cliente_id' => $clienteId,
            'monto' => '113.000000',
            'estado' => 'confirmado',
        ]);
        $this->assertDatabaseHas('con_asientos_contables', [
            'documento_tipo' => 'pago_cliente',
            'documento_id' => $pago->id,
            'estado' => 'confirmado',
        ]);
        $this->assertDatabaseHas('con_asientos_contables', [
            'documento_tipo' => 'venta',
            'documento_id' => $factura->id,
            'estado' => 'confirmado',
        ]);
        $this->assertDatabaseHas('con_asientos_contables', [
            'documento_tipo' => 'aplicacion_anticipos_cliente',
            'documento_id' => $factura->id,
            'estado' => 'confirmado',
        ]);
    }
}
