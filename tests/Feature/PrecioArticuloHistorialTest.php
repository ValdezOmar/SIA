<?php

namespace Tests\Feature;

use App\Filament\Resources\Inventario\ArticuloResource\Pages\EditArticulo;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\PreciosRelationManager;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\ListaPrecio;
use App\Models\Inventario\PrecioArticulo;
use App\Models\Inventario\PrecioArticuloHistorial;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PrecioArticuloHistorialTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_altas_cambios_y_borrados_con_usuario_y_conserva_la_bitacora(): void
    {
        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa auditoría precios',
            'nombre_comercial' => 'Auditoría Test',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $usuario = User::factory()->create(['name' => 'Operador de precios']);
        Auth::login($usuario);
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        $articulo = Articulo::create([
            'codigo' => 'ART-PRECIO-AUDIT',
            'nombre_comercial' => 'Artículo con auditoría',
            'empresa_id' => $empresaId,
        ]);
        $lista = ListaPrecio::create([
            'codigo' => 'LPR-AUDIT',
            'nombre' => 'Lista auditoría',
            'moneda' => 'BOB',
            'empresa_id' => $empresaId,
        ]);

        $precio = PrecioArticulo::create([
            'articulo_id' => $articulo->id,
            'lista_precio_id' => $lista->id,
            'precio' => 125.50,
        ]);
        $precio->update(['precio' => 150.75]);
        $this->assertCount(2, $precio->historial()->get());

        Livewire::test(PreciosRelationManager::class, [
            'ownerRecord' => $articulo,
            'pageClass' => EditArticulo::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$precio->fresh()])
            ->assertTableActionExists('view', record: $precio->fresh())
            ->assertTableActionExists('edit', record: $precio->fresh());

        $precio->delete();

        $historial = PrecioArticuloHistorial::query()->orderBy('id')->get();

        $this->assertCount(3, $historial);
        $this->assertSame(['creado', 'actualizado', 'eliminado'], $historial->pluck('accion')->all());
        $this->assertNull($historial[0]->precio_anterior);
        $this->assertSame(125.5, (float) $historial[0]->precio_nuevo);
        $this->assertSame(125.5, (float) $historial[1]->precio_anterior);
        $this->assertSame(150.75, (float) $historial[1]->precio_nuevo);
        $this->assertSame(150.75, (float) $historial[2]->precio_anterior);
        $this->assertNull($historial[2]->precio_nuevo);
        $this->assertSame($usuario->id, $historial[1]->usuario_id);
        $this->assertSame('Operador de precios', $historial[1]->usuario_nombre);
        $this->assertSame('LPR-AUDIT', $historial[1]->lista_precio_nuevo_codigo);
        $this->assertDatabaseMissing('alm_precios_articulos', ['id' => $precio->id]);

    }

    public function test_al_cambiar_de_lista_audita_lista_y_precio_de_ambos_lados(): void
    {
        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa cambio lista',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $articulo = Articulo::create(['codigo' => 'ART-LISTA-AUDIT', 'empresa_id' => $empresaId]);
        $listaAnterior = ListaPrecio::create(['codigo' => 'LPR-ANT', 'nombre' => 'Lista anterior', 'empresa_id' => $empresaId]);
        $listaNueva = ListaPrecio::create(['codigo' => 'LPR-NVA', 'nombre' => 'Lista nueva', 'moneda' => 'USD', 'empresa_id' => $empresaId]);
        $precio = PrecioArticulo::create([
            'articulo_id' => $articulo->id,
            'lista_precio_id' => $listaAnterior->id,
            'precio' => 10,
        ]);

        $precio->update(['lista_precio_id' => $listaNueva->id, 'precio' => 12]);

        $cambio = PrecioArticuloHistorial::query()->where('accion', 'actualizado')->firstOrFail();
        $this->assertSame('LPR-ANT', $cambio->lista_precio_anterior_codigo);
        $this->assertSame('LPR-NVA', $cambio->lista_precio_nuevo_codigo);
        $this->assertSame(10.0, (float) $cambio->precio_anterior);
        $this->assertSame(12.0, (float) $cambio->precio_nuevo);

        DB::table('alm_precios_articulos')->where('id', $precio->id)->update(['precio' => 14]);

        $cambioMasivo = PrecioArticuloHistorial::query()
            ->where('accion', 'actualizado')
            ->orderByDesc('id')
            ->firstOrFail();
        $this->assertSame(12.0, (float) $cambioMasivo->precio_anterior);
        $this->assertSame(14.0, (float) $cambioMasivo->precio_nuevo);
        $this->assertSame('Sistema (BD)', $cambioMasivo->usuario_nombre);

        $articulo->delete();
        $this->assertSame(1, PrecioArticuloHistorial::query()
            ->where('articulo_id', $articulo->id)
            ->where('accion', 'eliminado')
            ->count());

        $articuloDeOtraLista = Articulo::create(['codigo' => 'ART-LISTA-BORRADA', 'empresa_id' => $empresaId]);
        PrecioArticulo::create([
            'articulo_id' => $articuloDeOtraLista->id,
            'lista_precio_id' => $listaNueva->id,
            'precio' => 22,
        ]);
        $listaNueva->delete();
        $this->assertDatabaseHas('alm_precios_articulos_historial', [
            'articulo_id' => $articuloDeOtraLista->id,
            'accion' => 'eliminado',
            'lista_precio_anterior_codigo' => 'LPR-NVA',
            'precio_anterior' => 22,
        ]);
    }
}
