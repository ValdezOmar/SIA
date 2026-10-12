<?php

namespace Tests\Feature;

use App\Models\Ventas\Cliente;
use App\Models\Ventas\Cotizacion;
use App\Models\Ventas\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProspectoClienteTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(): int
    {
        return DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba',
            'nombre_comercial' => 'Empresa de prueba',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);
    }

    public function test_prospecto_de_cotizacion_conserva_registro_y_se_convierte_al_crear_factura(): void
    {
        $empresaId = $this->empresa();
        $resultado = Cliente::crearOReutilizarPorCelular([
            'nombre' => 'Ana Prospecto',
            'celular' => '71234567',
            'empresa_id' => $empresaId,
            'tipo_registro' => 'prospecto',
        ]);
        $prospecto = $resultado['cliente'];

        $this->assertSame('PRO-'.date('y').'001', $prospecto->codigo);
        $this->assertTrue($prospecto->esProspecto());

        $cotizacion = Cotizacion::create([
            'cliente_id' => $prospecto->id,
            'empresa_id' => $empresaId,
            'fecha_emision' => today(),
            'moneda' => 'BOB',
        ]);

        $this->assertTrue($prospecto->fresh()->esProspecto());

        Factura::create([
            'numero' => 'FAC-PRO-001',
            'cliente_id' => $prospecto->id,
            'empresa_id' => $empresaId,
            'fecha_emision' => now(),
            'condicion_pago' => 'contado',
            'moneda' => 'BOB',
            'total' => 0,
        ]);

        $clienteConvertido = $prospecto->fresh();
        $this->assertSame('CLI-'.date('y').'001', $clienteConvertido->codigo);
        $this->assertFalse($clienteConvertido->esProspecto());
        $this->assertSame($prospecto->id, $clienteConvertido->id);
        $this->assertSame($clienteConvertido->id, $cotizacion->fresh()->cliente_id);
    }

    public function test_reutilizar_celular_de_cliente_no_lo_convierte_en_prospecto(): void
    {
        $empresaId = $this->empresa();
        $cliente = Cliente::create([
            'codigo' => Cliente::generarCodigo(),
            'nombre' => 'Cliente existente',
            'celular' => '79998888',
            'empresa_id' => $empresaId,
        ]);

        $resultado = Cliente::crearOReutilizarPorCelular([
            'nombre' => 'Cliente existente',
            'celular' => '7999 8888',
            'empresa_id' => $empresaId,
            'tipo_registro' => 'prospecto',
        ]);

        $this->assertTrue($resultado['reutilizado']);
        $this->assertSame($cliente->id, $resultado['cliente']->id);
        $this->assertFalse($resultado['cliente']->esProspecto());
    }
}
