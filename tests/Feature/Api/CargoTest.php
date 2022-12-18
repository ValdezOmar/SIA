<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Cargo;

use App\Models\Area;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CargoTest extends TestCase
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
    public function it_gets_cargos_list()
    {
        $cargos = Cargo::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.cargos.index'));

        $response->assertOk()->assertSee($cargos[0]->nombre_cargo);
    }

    /**
     * @test
     */
    public function it_stores_the_cargo()
    {
        $data = Cargo::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.cargos.store'), $data);

        $this->assertDatabaseHas('hr_cargos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_cargo()
    {
        $cargo = Cargo::factory()->create();

        $area = Area::factory()->create();

        $data = [
            'nombre_cargo' => $this->faker->text(255),
            'descripcion_funciones' => $this->faker->sentence(15),
            'area_id' => $area->id,
        ];

        $response = $this->putJson(route('api.cargos.update', $cargo), $data);

        $data['id'] = $cargo->id;

        $this->assertDatabaseHas('hr_cargos', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_cargo()
    {
        $cargo = Cargo::factory()->create();

        $response = $this->deleteJson(route('api.cargos.destroy', $cargo));

        $this->assertDeleted($cargo);

        $response->assertNoContent();
    }
}
