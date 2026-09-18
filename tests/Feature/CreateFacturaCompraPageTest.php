<?php

namespace Tests\Feature;

use App\Filament\Resources\Compras\FacturaCompraResource\Pages\CreateFacturaCompra;
use App\Models\Compras\FacturaCompra;
use App\Models\Compras\Proveedor;
use App\Models\Inventario\Articulo;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CreateFacturaCompraPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_crear_factura_contado_guarda_detalle_pago_y_recepcion(): void
    {
        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba',
            'nombre_comercial' => 'Empresa',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);
        $usuario = User::factory()->create();
        $proveedor = Proveedor::create(['codigo' => 'PRV-PAGE', 'nombre' => 'Proveedor', 'activo' => true, 'empresa_id' => $empresaId]);
        $articulo = Articulo::create(['codigo' => 'ART-PAGE', 'nombre_comercial' => 'Artículo', 'inventariable' => true, 'comprable' => true, 'activo' => true, 'empresa_id' => $empresaId]);

        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        $this->actingAs($usuario);

        Livewire::test(CreateFacturaCompra::class)
            ->set('data.proveedor_id', $proveedor->id)
            ->set('data.fecha_emision', today()->toDateString())
            ->set('data.moneda', 'BOB')
            ->set('data.condicion_pago', 'contado')
            ->set('data.detalles', [[
                'articulo_id' => $articulo->id,
                'cantidad' => 1,
                'precio_unitario' => 100,
                'descuento' => 0,
                'descuento_porcentaje' => 0,
                'aplicar_iva' => true,
            ]])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Factura de compra registrada');

        $factura = FacturaCompra::query()->firstOrFail();
        $this->assertSame('pagada', $factura->estado);
        $this->assertSame(1, $factura->detalles()->count());
        $this->assertSame(1, $factura->pagos()->where('estado', 'confirmado')->count());
        $this->assertNotNull($factura->recepcion_id);
    }
}
