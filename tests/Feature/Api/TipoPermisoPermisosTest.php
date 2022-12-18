<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Permisos;
use App\Models\TipoPermiso;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TipoPermisoPermisosTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_gets_tipo_permiso_permisos()
    {
        $tipoPermiso = TipoPermiso::factory()->create();
        $permisos = Permisos::factory()
            ->count(2)
            ->create([
                'tipo_permiso_id' => $tipoPermiso->id,
            ]);

        $response = $this->getJson(
            route('api.tipo-permisos.permisos.index', $tipoPermiso)
        );

        $response->assertOk()->assertSee($permisos[0]->descripcion);
    }

    /**
     * @test
     */
    public function it_stores_the_tipo_permiso_permisos()
    {
        $tipoPermiso = TipoPermiso::factory()->create();
        $data = Permisos::factory()
            ->make([
                'tipo_permiso_id' => $tipoPermiso->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.tipo-permisos.permisos.store', $tipoPermiso),
            $data
        );

        $this->assertDatabaseHas('rh_permisos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $permisos = Permisos::latest('id')->first();

        $this->assertEquals($tipoPermiso->id, $permisos->tipo_permiso_id);
    }
}
