<?php

namespace Tests\Feature;

use RuntimeException;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Kardex;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PagoMixtoTest extends TestCase
{
    use RefreshDatabase;

    private Factura $factura;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Mixto', 'nombre_comercial' => 'Mixto', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $cliente = Cliente::create(['codigo' => 'CLI-MIX', 'nombre' => 'Cliente mixto', 'empresa_id' => $empresa]);
        $articulo = Articulo::create(['codigo' => 'SRV-MIX', 'nombre_comercial' => 'Servicio', 'empresa_id' => $empresa, 'inventariable' => false]);
        $this->factura = Factura::create(['numero' => 'FAC-MIX', 'cliente_id' => $cliente->id, 'empresa_id' => $empresa, 'fecha_emision' => '2025-12-20', 'fecha_pago' => '2025-12-18', 'condicion_pago' => 'contado', 'moneda' => 'BOB', 'total' => 100, 'subtotal' => 100]);
        $this->factura->detalles()->create(['articulo_id' => $articulo->id, 'codigo_articulo' => 'SRV-MIX', 'descripcion_articulo' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 100]);
    }

    public function test_contado_mixto_contabiliza_ambos_medios_sin_duplicar_al_reintentar(): void
    {
        $datos = ['tipo_pago' => 'mixto', 'monto_efectivo' => 30, 'monto_qr' => 70, 'referencia' => 'QR-123', 'banco' => 'Banco'];
        $this->factura->crearPagoAutomaticoSiEsContado($datos);
        $this->factura->crearPagoAutomaticoSiEsContado($datos);
        $pagos = $this->factura->pagos()->orderBy('id')->get();
        $this->assertCount(2, $pagos);
        $this->assertSame(['efectivo', 'qr'], $pagos->pluck('tipo_pago')->all());
        $this->assertSame([30.0, 70.0], $pagos->map(fn ($pago) => (float) $pago->monto)->all());
        $this->assertSame('2025-12-18', $pagos[1]->fecha_pago->toDateString());
        $this->assertNull($pagos[0]->banco);
        $this->assertSame('QR-123', $pagos[1]->referencia);
        $asientos = AsientoContable::where('documento_tipo', 'pago_cliente')->get();
        $this->assertCount(2, $asientos);
        $cuentas = $asientos->map(fn ($asiento) => $asiento->detalles()->where('debe', '>', 0)->value('cuenta_id'));
        $this->assertCount(2, $cuentas->unique());
        $this->assertSame(0.0, (float) $this->factura->fresh()->saldo);
    }

    public function test_suma_incorrecta_en_contado_no_registra_ninguna_parte(): void
    {
        try {
            $this->factura->crearPagoAutomaticoSiEsContado(['tipo_pago' => 'mixto', 'monto_efectivo' => 30, 'monto_qr' => 60]);
            $this->fail('Debe rechazar un pago incompleto al contado.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('monto_efectivo', $exception->errors());
        }
        $this->assertSame(0, $this->factura->pagos()->count());
        $this->assertSame(0, AsientoContable::count());
    }

    public function test_abono_mixto_suma_las_partes_y_valida_el_saldo(): void
    {
        $this->factura->update(['condicion_pago' => 'parcial']);
        $this->factura->registrarPago(['tipo_pago' => 'mixto', 'monto_efectivo' => 10, 'monto_qr' => 30, 'fecha_pago' => '2025-12-18']);
        $this->assertSame(60.0, (float) $this->factura->fresh()->saldo);
        try {
            $this->factura->registrarPago(['tipo_pago' => 'mixto', 'monto_efectivo' => 40, 'monto_qr' => 30]);
            $this->fail('Debe rechazar el exceso de pago.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('monto', $exception->errors());
        }
        $this->assertSame(2, $this->factura->pagos()->count());
    }

    public function test_fallo_de_entrega_revierte_las_dos_partes_y_sus_asientos(): void
    {
        $this->factura->detalles()->first()->articulo->update(['inventariable' => true]);
        try {
            $this->factura->crearPagoAutomaticoSiEsContado(['tipo_pago' => 'mixto', 'monto_efectivo' => 30, 'monto_qr' => 70]);
            $this->fail('No hay almacén para entregar el producto.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('almacén', $exception->getMessage());
        }
        $this->assertSame(0, $this->factura->pagos()->count());
        $this->assertSame(0, AsientoContable::count());
        $this->assertSame(0, Kardex::count());
    }
}
