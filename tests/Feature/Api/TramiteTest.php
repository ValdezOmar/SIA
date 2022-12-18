<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tramite;

use App\Models\CiteInterno;
use App\Models\TipoDocumento;
use App\Models\RemitenteExterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TramiteTest extends TestCase
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
    public function it_gets_tramites_list()
    {
        $tramites = Tramite::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.tramites.index'));

        $response->assertOk()->assertSee($tramites[0]->hoja_ruta);
    }

    /**
     * @test
     */
    public function it_stores_the_tramite()
    {
        $data = Tramite::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.tramites.store'), $data);

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
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

        $response = $this->putJson(
            route('api.tramites.update', $tramite),
            $data
        );

        $data['id'] = $tramite->id;

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_tramite()
    {
        $tramite = Tramite::factory()->create();

        $response = $this->deleteJson(route('api.tramites.destroy', $tramite));

        $this->assertSoftDeleted($tramite);

        $response->assertNoContent();
    }
}
