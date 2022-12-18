<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\TipoPermiso;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TipoPermisoTest extends TestCase
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
    public function it_gets_tipo_permisos_list()
    {
        $tipoPermisos = TipoPermiso::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.tipo-permisos.index'));

        $response->assertOk()->assertSee($tipoPermisos[0]->nombre_permiso);
    }

    /**
     * @test
     */
    public function it_stores_the_tipo_permiso()
    {
        $data = TipoPermiso::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.tipo-permisos.store'), $data);

        $this->assertDatabaseHas('h_tipo_permisos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_tipo_permiso()
    {
        $tipoPermiso = TipoPermiso::factory()->create();

        $data = [
            'nombre_permiso' => $this->faker->text(255),
            'tipo_permiso' => $this->faker->text(255),
            'tiempo_permitido' => $this->faker->time,
            'descripcion_permiso' => $this->faker->sentence(15),
        ];

        $response = $this->putJson(
            route('api.tipo-permisos.update', $tipoPermiso),
            $data
        );

        $data['id'] = $tipoPermiso->id;

        $this->assertDatabaseHas('h_tipo_permisos', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_tipo_permiso()
    {
        $tipoPermiso = TipoPermiso::factory()->create();

        $response = $this->deleteJson(
            route('api.tipo-permisos.destroy', $tipoPermiso)
        );

        $this->assertDeleted($tipoPermiso);

        $response->assertNoContent();
    }
}
