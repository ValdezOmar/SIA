<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Cargo;

use App\Models\Area;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CargoControllerTest extends TestCase
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
    public function it_displays_index_view_with_cargos()
    {
        $cargos = Cargo::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('cargos.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.cargos.index')
            ->assertViewHas('cargos');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_cargo()
    {
        $response = $this->get(route('cargos.create'));

        $response->assertOk()->assertViewIs('app.cargos.create');
    }

    /**
     * @test
     */
    public function it_stores_the_cargo()
    {
        $data = Cargo::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('cargos.store'), $data);

        $this->assertDatabaseHas('hr_cargos', $data);

        $cargo = Cargo::latest('id')->first();

        $response->assertRedirect(route('cargos.edit', $cargo));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_cargo()
    {
        $cargo = Cargo::factory()->create();

        $response = $this->get(route('cargos.show', $cargo));

        $response
            ->assertOk()
            ->assertViewIs('app.cargos.show')
            ->assertViewHas('cargo');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_cargo()
    {
        $cargo = Cargo::factory()->create();

        $response = $this->get(route('cargos.edit', $cargo));

        $response
            ->assertOk()
            ->assertViewIs('app.cargos.edit')
            ->assertViewHas('cargo');
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

        $response = $this->put(route('cargos.update', $cargo), $data);

        $data['id'] = $cargo->id;

        $this->assertDatabaseHas('hr_cargos', $data);

        $response->assertRedirect(route('cargos.edit', $cargo));
    }

    /**
     * @test
     */
    public function it_deletes_the_cargo()
    {
        $cargo = Cargo::factory()->create();

        $response = $this->delete(route('cargos.destroy', $cargo));

        $response->assertRedirect(route('cargos.index'));

        $this->assertDeleted($cargo);
    }
}
