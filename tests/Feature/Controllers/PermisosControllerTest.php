<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Permisos;

use App\Models\TipoPermiso;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermisosControllerTest extends TestCase
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
    public function it_displays_index_view_with_permisos()
    {
        $permisos = Permisos::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('permisos.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.permisos.index')
            ->assertViewHas('permisos');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_permisos()
    {
        $response = $this->get(route('permisos.create'));

        $response->assertOk()->assertViewIs('app.permisos.create');
    }

    /**
     * @test
     */
    public function it_stores_the_permisos()
    {
        $data = Permisos::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('permisos.store'), $data);

        $this->assertDatabaseHas('rh_permisos', $data);

        $permisos = Permisos::latest('id')->first();

        $response->assertRedirect(route('permisos.edit', $permisos));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_permisos()
    {
        $permisos = Permisos::factory()->create();

        $response = $this->get(route('permisos.show', $permisos));

        $response
            ->assertOk()
            ->assertViewIs('app.permisos.show')
            ->assertViewHas('permisos');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_permisos()
    {
        $permisos = Permisos::factory()->create();

        $response = $this->get(route('permisos.edit', $permisos));

        $response
            ->assertOk()
            ->assertViewIs('app.permisos.edit')
            ->assertViewHas('permisos');
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

        $response = $this->put(route('permisos.update', $permisos), $data);

        $data['id'] = $permisos->id;

        $this->assertDatabaseHas('rh_permisos', $data);

        $response->assertRedirect(route('permisos.edit', $permisos));
    }

    /**
     * @test
     */
    public function it_deletes_the_permisos()
    {
        $permisos = Permisos::factory()->create();

        $response = $this->delete(route('permisos.destroy', $permisos));

        $response->assertRedirect(route('permisos.index'));

        $this->assertSoftDeleted($permisos);
    }
}
