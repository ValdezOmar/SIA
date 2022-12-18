<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Derivacion;

use App\Models\Tramite;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DerivacionControllerTest extends TestCase
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
    public function it_displays_index_view_with_derivaciones()
    {
        $derivaciones = Derivacion::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('derivaciones.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.derivaciones.index')
            ->assertViewHas('derivaciones');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_derivacion()
    {
        $response = $this->get(route('derivaciones.create'));

        $response->assertOk()->assertViewIs('app.derivaciones.create');
    }

    /**
     * @test
     */
    public function it_stores_the_derivacion()
    {
        $data = Derivacion::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('derivaciones.store'), $data);

        $this->assertDatabaseHas('cor_derivaciones', $data);

        $derivacion = Derivacion::latest('id')->first();

        $response->assertRedirect(route('derivaciones.edit', $derivacion));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_derivacion()
    {
        $derivacion = Derivacion::factory()->create();

        $response = $this->get(route('derivaciones.show', $derivacion));

        $response
            ->assertOk()
            ->assertViewIs('app.derivaciones.show')
            ->assertViewHas('derivacion');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_derivacion()
    {
        $derivacion = Derivacion::factory()->create();

        $response = $this->get(route('derivaciones.edit', $derivacion));

        $response
            ->assertOk()
            ->assertViewIs('app.derivaciones.edit')
            ->assertViewHas('derivacion');
    }

    /**
     * @test
     */
    public function it_updates_the_derivacion()
    {
        $derivacion = Derivacion::factory()->create();

        $tramite = Tramite::factory()->create();
        $user = User::factory()->create();

        $data = [
            'fecha_derivacion' => $this->faker->dateTime,
            'fecha_recepcion' => $this->faker->dateTime,
            'fecha_rechazo' => $this->faker->dateTime,
            'fecha_anulado' => $this->faker->dateTime,
            'fecha_archivo' => $this->faker->dateTime,
            'proveido' => $this->faker->text(255),
            'destinatario_id' => $this->faker->randomNumber,
            'tramite_id' => $tramite->id,
            'remitente_id' => $user->id,
        ];

        $response = $this->put(
            route('derivaciones.update', $derivacion),
            $data
        );

        $data['id'] = $derivacion->id;

        $this->assertDatabaseHas('cor_derivaciones', $data);

        $response->assertRedirect(route('derivaciones.edit', $derivacion));
    }

    /**
     * @test
     */
    public function it_deletes_the_derivacion()
    {
        $derivacion = Derivacion::factory()->create();

        $response = $this->delete(route('derivaciones.destroy', $derivacion));

        $response->assertRedirect(route('derivaciones.index'));

        $this->assertSoftDeleted($derivacion);
    }
}
