<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\RemitenteExterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemitenteExternoControllerTest extends TestCase
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
    public function it_displays_index_view_with_remitente_externos()
    {
        $remitenteExternos = RemitenteExterno::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('remitente-externos.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.remitente_externos.index')
            ->assertViewHas('remitenteExternos');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_remitente_externo()
    {
        $response = $this->get(route('remitente-externos.create'));

        $response->assertOk()->assertViewIs('app.remitente_externos.create');
    }

    /**
     * @test
     */
    public function it_stores_the_remitente_externo()
    {
        $data = RemitenteExterno::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('remitente-externos.store'), $data);

        $this->assertDatabaseHas('cor_remitente_externos', $data);

        $remitenteExterno = RemitenteExterno::latest('id')->first();

        $response->assertRedirect(
            route('remitente-externos.edit', $remitenteExterno)
        );
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_remitente_externo()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();

        $response = $this->get(
            route('remitente-externos.show', $remitenteExterno)
        );

        $response
            ->assertOk()
            ->assertViewIs('app.remitente_externos.show')
            ->assertViewHas('remitenteExterno');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_remitente_externo()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();

        $response = $this->get(
            route('remitente-externos.edit', $remitenteExterno)
        );

        $response
            ->assertOk()
            ->assertViewIs('app.remitente_externos.edit')
            ->assertViewHas('remitenteExterno');
    }

    /**
     * @test
     */
    public function it_updates_the_remitente_externo()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();

        $data = [
            'nombre_completo' => $this->faker->name(),
            'cargo_empresa' => $this->faker->text(255),
            'telefono_1' => $this->faker->text(255),
            'telefono_2' => $this->faker->text(255),
        ];

        $response = $this->put(
            route('remitente-externos.update', $remitenteExterno),
            $data
        );

        $data['id'] = $remitenteExterno->id;

        $this->assertDatabaseHas('cor_remitente_externos', $data);

        $response->assertRedirect(
            route('remitente-externos.edit', $remitenteExterno)
        );
    }

    /**
     * @test
     */
    public function it_deletes_the_remitente_externo()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();

        $response = $this->delete(
            route('remitente-externos.destroy', $remitenteExterno)
        );

        $response->assertRedirect(route('remitente-externos.index'));

        $this->assertDeleted($remitenteExterno);
    }
}
