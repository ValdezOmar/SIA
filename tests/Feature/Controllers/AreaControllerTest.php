<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Area;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AreaControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_displays_index_view_with_areas()
    {
        $areas = Area::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('areas.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.areas.index')
            ->assertViewHas('areas');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_area()
    {
        $response = $this->get(route('areas.create'));

        $response->assertOk()->assertViewIs('app.areas.create');
    }

    /**
     * @test
     */
    public function it_stores_the_area()
    {
        $data = Area::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('areas.store'), $data);

        $this->assertDatabaseHas('rh_areas', $data);

        $area = Area::latest('id')->first();

        $response->assertRedirect(route('areas.edit', $area));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_area()
    {
        $area = Area::factory()->create();

        $response = $this->get(route('areas.show', $area));

        $response
            ->assertOk()
            ->assertViewIs('app.areas.show')
            ->assertViewHas('area');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_area()
    {
        $area = Area::factory()->create();

        $response = $this->get(route('areas.edit', $area));

        $response
            ->assertOk()
            ->assertViewIs('app.areas.edit')
            ->assertViewHas('area');
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

        $response = $this->put(route('areas.update', $area), $data);

        $data['id'] = $area->id;

        $this->assertDatabaseHas('rh_areas', $data);

        $response->assertRedirect(route('areas.edit', $area));
    }

    /**
     * @test
     */
    public function it_deletes_the_area()
    {
        $area = Area::factory()->create();

        $response = $this->delete(route('areas.destroy', $area));

        $response->assertRedirect(route('areas.index'));

        $this->assertSoftDeleted($area);
    }
}
