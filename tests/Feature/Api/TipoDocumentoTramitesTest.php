<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tramite;
use App\Models\TipoDocumento;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TipoDocumentoTramitesTest extends TestCase
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
    public function it_gets_tipo_documento_tramites()
    {
        $tipoDocumento = TipoDocumento::factory()->create();
        $tramites = Tramite::factory()
            ->count(2)
            ->create([
                'tipo_documento_id' => $tipoDocumento->id,
            ]);

        $response = $this->getJson(
            route('api.tipo-documentos.tramites.index', $tipoDocumento)
        );

        $response->assertOk()->assertSee($tramites[0]->hoja_ruta);
    }

    /**
     * @test
     */
    public function it_stores_the_tipo_documento_tramites()
    {
        $tipoDocumento = TipoDocumento::factory()->create();
        $data = Tramite::factory()
            ->make([
                'tipo_documento_id' => $tipoDocumento->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.tipo-documentos.tramites.store', $tipoDocumento),
            $data
        );

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $tramite = Tramite::latest('id')->first();

        $this->assertEquals($tipoDocumento->id, $tramite->tipo_documento_id);
    }
}
