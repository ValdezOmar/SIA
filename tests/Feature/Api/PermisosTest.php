<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Permisos;

use App\Models\TipoPermiso;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermisosTest extends TestCase
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
    public function it_gets_permisos_list()
    {
        $permisos = Permisos::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.permisos.index'));

        $response->assertOk()->assertSee($permisos[0]->descripcion);
    }

    /**
     * @test
     */
    public function it_stores_the_permisos()
    {
        $data = Permisos::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.permisos.store'), $data);

        $this->assertDatabaseHas('rh_permisos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_permisos()
    {
        $permisos = Permisos::factory()->create();

        $tipoPermiso = TipoPermiso::factory()->create();

        $data = [
            'fecha_inicio' => $this->faker->dateTime,
            'fecha_fin' => $this->faker->dateTime,
            'descripcion' => $this->faker->text(255),
            'descripcion_rechazo' => $this->faker->text(255),
            'tipo_permiso_id' => $tipoPermiso->id,
        ];

        $response = $this->putJson(
            route('api.permisos.update', $permisos),
            $data
        );

        $data['id'] = $permisos->id;

        $this->assertDatabaseHas('rh_permisos', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_permisos()
    {
        $permisos = Permisos::factory()->create();

        $response = $this->deleteJson(route('api.permisos.destroy', $permisos));

        $this->assertSoftDeleted($permisos);

        $response->assertNoContent();
    }
}
