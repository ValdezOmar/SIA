<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\Tramite;

use App\Models\CiteInterno;
use App\Models\TipoDocumento;
use App\Models\RemitenteExterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TramiteControllerTest extends TestCase
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
    public function it_displays_index_view_with_tramites()
    {
        $tramites = Tramite::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('tramites.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.tramites.index')
            ->assertViewHas('tramites');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_tramite()
    {
        $response = $this->get(route('tramites.create'));

        $response->assertOk()->assertViewIs('app.tramites.create');
    }

    /**
     * @test
     */
    public function it_stores_the_tramite()
    {
        $data = Tramite::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('tramites.store'), $data);

        $this->assertDatabaseHas('cor_tramites', $data);

        $tramite = Tramite::latest('id')->first();

        $response->assertRedirect(route('tramites.edit', $tramite));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_tramite()
    {
        $tramite = Tramite::factory()->create();

        $response = $this->get(route('tramites.show', $tramite));

        $response
            ->assertOk()
            ->assertViewIs('app.tramites.show')
            ->assertViewHas('tramite');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_tramite()
    {
        $tramite = Tramite::factory()->create();

        $response = $this->get(route('tramites.edit', $tramite));

        $response
            ->assertOk()
            ->assertViewIs('app.tramites.edit')
            ->assertViewHas('tramite');
    }

    /**
     * @test
     */
    public function it_updates_the_tramite()
    {
        $tramite = Tramite::factory()->create();

        $citeInterno = CiteInterno::factory()->create();
        $remitenteExterno = RemitenteExterno::factory()->create();
        $tipoDocumento = TipoDocumento::factory()->create();
        $user = User::factory()->create();
        $user = User::factory()->create();

        $data = [
            'hoja_ruta' => $this->faker->text(50),
            'fecha_ingreso' => $this->faker->dateTime,
            'hr_ext' => $this->faker->boolean,
            'asunto_ext' => $this->faker->sentence(15),
            'cite_ext' => $this->faker->text(255),
            'asunto_int' => $this->faker->randomNumber,
            'estado' => $this->faker->text(5),
            'fusionado' => $this->faker->boolean,
            'hr_fusionado' => $this->faker->text(255),
            'cite_interno_id' => $citeInterno->id,
            'remitente_externo_id' => $remitenteExterno->id,
            'tipo_documento_id' => $tipoDocumento->id,
            'recepcion_user_id' => $user->id,
            'remitente_interno_id' => $user->id,
        ];

        $response = $this->put(route('tramites.update', $tramite), $data);

        $data['id'] = $tramite->id;

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertRedirect(route('tramites.edit', $tramite));
    }

    /**
     * @test
     */
    public function it_deletes_the_tramite()
    {
        $tramite = Tramite::factory()->create();

        $response = $this->delete(route('tramites.destroy', $tramite));

        $response->assertRedirect(route('tramites.index'));

        $this->assertSoftDeleted($tramite);
    }
}
