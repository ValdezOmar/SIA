<?php

namespace Tests\Feature;

use App\Models\Compras\FacturaCompra;
use App\Models\Compras\Proveedor;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Kardex;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FacturaCompraFlujoTest extends TestCase
{
    use RefreshDatabase;

    private int $empresaId;
    private Articulo $articulo;
    private Proveedor $proveedor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresaId = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Empresa', 'nombre_comercial' => 'Empresa', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $this->actingAs(User::factory()->create());
        $this->proveedor = Proveedor::create(['codigo' => 'PRV-TEST', 'nombre' => 'Proveedor', 'activo' => true, 'empresa_id' => $this->empresaId]);
        $this->articulo = Articulo::create(['codigo' => 'ART-CMP', 'nombre_comercial' => 'ArtÃ­culo compra', 'descripcion' => 'ArtÃ­culo de prueba', 'inventariable' => true, 'comprable' => true, 'vendible' => true, 'activo' => true, 'metodo_costo' => 'promedio', 'empresa_id' => $this->empresaId]);
    }

    public function test_contado_registra_pago_y_prepara_recepcion_sin_ingresar_stock(): void
    {
        $factura = $this->factura('contado');
        $factura->registrarPago(['monto' => 113, 'tipo_pago' => 'efectivo', 'fecha_pago' => today(), 'automatico' => true]);
        $recepcion = $factura->fresh()->prepararRecepcionPendiente();

        $this->assertSame('pagada', $factura->fresh()->estado);
        $this->assertSame(1, $factura->pagos()->where('estado', 'confirmado')->count());
        $this->assertSame(DB::connection()->getDriverName() === 'sqlite' ? 'pendiente' : 'listo', $recepcion->estado);
        $this->assertSame(0.0, (float) $recepcion->detalles()->value('cantidad_aceptada'));
        $this->assertSame(0, Kardex::query()->where('documento_tipo', 'recepcion')->count());
    }

    public function test_pago_parcial_mantiene_recepcion_parcial_sin_kardex(): void
    {
        $factura = $this->factura('parcial');
        $factura->registrarPago(['monto' => 50, 'tipo_pago' => 'transferencia', 'fecha_pago' => today(), 'respaldos' => ['pagos/prueba.pdf']]);
        $recepcion = $factura->fresh()->prepararRecepcionPendiente();

        $this->assertSame('parcial', $factura->fresh()->estado);
        $this->assertSame('parcial', $recepcion->estado);
        $this->assertSame(0, Kardex::query()->where('documento_tipo', 'recepcion')->count());
    }

    public function test_moneda_extranjera_guarda_tipo_de_cambio_y_bob_usa_uno(): void
    {
        $usd = FacturaCompra::create([
            'codigo' => FacturaCompra::generarCodigo(),
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => today(),
            'condicion_pago' => 'contado',
            'moneda' => 'USD',
            'tasa_cambio' => 6.96,
            'empresa_id' => $this->empresaId,
        ]);
        $bob = FacturaCompra::create([
            'codigo' => FacturaCompra::generarCodigo(),
            'proveedor_id' => $this->proveedor->id,
            'fecha_emision' => today(),
            'condicion_pago' => 'contado',
            'moneda' => 'BOB',
            'tasa_cambio' => 99,
            'empresa_id' => $this->empresaId,
        ]);

        $this->assertSame(6.96, (float) $usd->fresh()->tasa_cambio);
        $this->assertSame(1.0, (float) $bob->fresh()->tasa_cambio);
    }

    private function factura(string $condicion): FacturaCompra
    {
        $factura = FacturaCompra::create(['codigo' => FacturaCompra::generarCodigo(), 'proveedor_id' => $this->proveedor->id, 'fecha_emision' => today(), 'condicion_pago' => $condicion, 'moneda' => 'BOB', 'tasa_cambio' => 1, 'empresa_id' => $this->empresaId, 'estado' => 'registrada']);
        $factura->detalles()->create(['articulo_id' => $this->articulo->id, 'codigo_articulo' => $this->articulo->codigo, 'descripcion_articulo' => $this->articulo->descripcion, 'cantidad' => 1, 'precio_unitario' => 100, 'subtotal' => 100, 'impuesto' => 13, 'total' => 113]);
        $factura->recalcularTotales();
        $factura->updateQuietly(['estado' => 'registrada', 'saldo' => 113, 'monto_pagado' => 0]);

        return $factura->fresh();
    }
}
