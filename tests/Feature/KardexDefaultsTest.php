<?php

namespace Tests\Feature;

use App\Models\Inventario\Kardex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class KardexDefaultsTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_sets_missing_document_and_audit_defaults_before_save(): void
    {
        $user = \App\Models\User::factory()->create();
        Auth::login($user);

        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa de prueba',
            'nombre_comercial' => 'Empresa Test',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $almacenId = DB::table('alm_almacenes')->insertGetId([
            'codigo' => 'ALM-001',
            'nombre' => 'Almacén principal',
            'direccion' => 'Calle 1',
            'empresa_id' => $empresaId,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $articuloId = DB::table('alm_articulos')->insertGetId([
            'codigo' => 'ART-001',
            'nombre_comercial' => 'Artículo de prueba',
            'descripcion' => 'Artículo para prueba de kardex',
            'inventariable' => true,
            'comprable' => true,
            'vendible' => true,
            'maneja_lotes' => false,
            'maneja_series' => false,
            'requiere_serie_en_salida' => false,
            'metodo_costo' => 'promedio',
            'comision' => 0,
            'activo' => true,
            'empresa_id' => $empresaId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $kardex = Kardex::create([
            'articulo_id' => $articuloId,
            'almacen_id' => $almacenId,
            'tipo_movimiento' => 'compra',
            'direccion' => 'entrada',
            'cantidad' => 1,
            'costo_unitario' => 111,
            'fecha_movimiento' => now(),
            'estado' => 'confirmado',
        ]);

        $this->assertSame('manual', $kardex->documento_tipo);
        $this->assertSame(0, $kardex->documento_id);
        $this->assertSame($user->id, $kardex->usuario_id);
        $this->assertSame($user->id, $kardex->creado_por);
    }
}
