<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Area;
use App\Models\Cargo;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AreaCargosTest extends TestCase
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
    public function it_gets_area_cargos()
    {
        $area = Area::factory()->create();
        $cargos = Cargo::factory()
            ->count(2)
            ->create([
                'area_id' => $area->id,
            ]);

        $response = $this->getJson(route('api.areas.cargos.index', $area));

        $response->assertOk()->assertSee($cargos[0]->nombre_cargo);
    }

    /**
     * @test
     */
    public function it_stores_the_area_cargos()
    {
        $area = Area::factory()->create();
        $data = Cargo::factory()
            ->make([
                'area_id' => $area->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.areas.cargos.store', $area),
            $data
        );

        $this->assertDatabaseHas('hr_cargos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $cargo = Cargo::latest('id')->first();

        $this->assertEquals($area->id, $cargo->area_id);
    }
}
