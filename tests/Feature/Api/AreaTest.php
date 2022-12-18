<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Area;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AreaTest extends TestCase
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
    public function it_gets_areas_list()
    {
        $areas = Area::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.areas.index'));

        $response->assertOk()->assertSee($areas[0]->nombre_area);
    }

    /**
     * @test
     */
    public function it_stores_the_area()
    {
        $data = Area::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.areas.store'), $data);

        $this->assertDatabaseHas('rh_areas', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_area()
    {
        $area = Area::factory()->create();

        $data = [
            'nombre_area' => $this->faker->text(255),
            'descripcion_area' => $this->faker->text,
            'prefijo_cite' => $this->faker->text(255),
        ];

        $response = $this->putJson(route('api.areas.update', $area), $data);

        $data['id'] = $area->id;

        $this->assertDatabaseHas('rh_areas', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_area()
    {
        $area = Area::factory()->create();

        $response = $this->deleteJson(route('api.areas.destroy', $area));

        $this->assertSoftDeleted($area);

        $response->assertNoContent();
    }
}
