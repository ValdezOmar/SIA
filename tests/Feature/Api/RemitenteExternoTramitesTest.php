<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tramite;
use App\Models\RemitenteExterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemitenteExternoTramitesTest extends TestCase
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
    public function it_gets_remitente_externo_tramites()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();
        $tramites = Tramite::factory()
            ->count(2)
            ->create([
                'remitente_externo_id' => $remitenteExterno->id,
            ]);

        $response = $this->getJson(
            route('api.remitente-externos.tramites.index', $remitenteExterno)
        );

        $response->assertOk()->assertSee($tramites[0]->hoja_ruta);
    }

    /**
     * @test
     */
    public function it_stores_the_remitente_externo_tramites()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();
        $data = Tramite::factory()
            ->make([
                'remitente_externo_id' => $remitenteExterno->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.remitente-externos.tramites.store', $remitenteExterno),
            $data
        );

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $tramite = Tramite::latest('id')->first();

        $this->assertEquals(
            $remitenteExterno->id,
            $tramite->remitente_externo_id
        );
    }
}
