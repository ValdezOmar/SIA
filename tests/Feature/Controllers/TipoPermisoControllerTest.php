<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\TipoPermiso;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TipoPermisoControllerTest extends TestCase
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
    public function it_displays_index_view_with_tipo_permisos()
    {
        $tipoPermisos = TipoPermiso::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('tipo-permisos.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.tipo_permisos.index')
            ->assertViewHas('tipoPermisos');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_tipo_permiso()
    {
        $response = $this->get(route('tipo-permisos.create'));

        $response->assertOk()->assertViewIs('app.tipo_permisos.create');
    }

    /**
     * @test
     */
    public function it_stores_the_tipo_permiso()
    {
        $data = TipoPermiso::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('tipo-permisos.store'), $data);

        $this->assertDatabaseHas('h_tipo_permisos', $data);

        $tipoPermiso = TipoPermiso::latest('id')->first();

        $response->assertRedirect(route('tipo-permisos.edit', $tipoPermiso));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_tipo_permiso()
    {
        $tipoPermiso = TipoPermiso::factory()->create();

        $response = $this->get(route('tipo-permisos.show', $tipoPermiso));

        $response
            ->assertOk()
            ->assertViewIs('app.tipo_permisos.show')
            ->assertViewHas('tipoPermiso');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_tipo_permiso()
    {
        $tipoPermiso = TipoPermiso::factory()->create();

        $response = $this->get(route('tipo-permisos.edit', $tipoPermiso));

        $response
            ->assertOk()
            ->assertViewIs('app.tipo_permisos.edit')
            ->assertViewHas('tipoPermiso');
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

        $response = $this->put(
            route('tipo-permisos.update', $tipoPermiso),
            $data
        );

        $data['id'] = $tipoPermiso->id;

        $this->assertDatabaseHas('h_tipo_permisos', $data);

        $response->assertRedirect(route('tipo-permisos.edit', $tipoPermiso));
    }

    /**
     * @test
     */
    public function it_deletes_the_tipo_permiso()
    {
        $tipoPermiso = TipoPermiso::factory()->create();

        $response = $this->delete(route('tipo-permisos.destroy', $tipoPermiso));

        $response->assertRedirect(route('tipo-permisos.index'));

        $this->assertDeleted($tipoPermiso);
    }
}
