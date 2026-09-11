<?php

namespace Tests\Feature;

use App\Filament\Resources\Inventario\KardexResource\Pages\CreateKardex;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Kardex;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class KardexResourceCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_de_kardex_persiste_el_movimiento_y_su_reflejo_de_inventario(): void
    {
        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa Kardex',
            'nombre_comercial' => 'Kardex Test',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $usuario = User::factory()->create();
        Auth::login($usuario);
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        $almacen = Almacen::create([
            'codigo' => 'ALM-KARDEX',
            'nombre' => 'Almacén Kardex',
            'empresa_id' => $empresaId,
            'activo' => true,
        ]);
        $articulo = Articulo::create([
            'codigo' => 'ART-KARDEX',
            'nombre_comercial' => 'Artículo Kardex',
            'empresa_id' => $empresaId,
            'inventariable' => true,
            'metodo_costo' => 'promedio',
            'activo' => true,
        ]);

        Livewire::test(CreateKardex::class)
            ->fillForm([
                'articulo_id' => $articulo->id,
                'almacen_id' => $almacen->id,
                'fecha_movimiento' => now()->format('Y-m-d H:i:s'),
                'fecha_contable' => now()->toDateString(),
                'tipo_movimiento' => 'compra',
                'direccion' => 'entrada',
                'cantidad' => 2,
                'costo_unitario' => 35.5,
                'costo_total' => 71,
                'estado' => 'confirmado',
                'motivo' => 'Compra de prueba desde el recurso',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $kardex = Kardex::query()->firstOrFail();

        $this->assertSame('compra', $kardex->tipo_movimiento);
        $this->assertSame('confirmado', $kardex->estado);
        $this->assertSame(71.0, (float) $kardex->costo_total);
        $this->assertDatabaseHas('alm_movimientos_inventario', [
            'kardex_id' => $kardex->id,
            'tipo' => 'entrada_compra',
            'estado' => 'confirmado',
        ]);
        $this->assertDatabaseHas('con_asientos_contables', [
            'documento_tipo' => 'kardex',
            'documento_id' => $kardex->id,
            'estado' => 'confirmado',
        ]);
    }
}
