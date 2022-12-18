<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\TipoDocumento;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TipoDocumentoTest extends TestCase
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
    public function it_gets_tipo_documentos_list()
    {
        $tipoDocumentos = TipoDocumento::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.tipo-documentos.index'));

        $response->assertOk()->assertSee($tipoDocumentos[0]->tipo_documento);
    }

    /**
     * @test
     */
    public function it_stores_the_tipo_documento()
    {
        $data = TipoDocumento::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.tipo-documentos.store'), $data);

        $this->assertDatabaseHas('cor_tipo_documentos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_tipo_documento()
    {
        $tipoDocumento = TipoDocumento::factory()->create();

        $data = [
            'tipo_documento' => $this->faker->text(255),
        ];

        $response = $this->putJson(
            route('api.tipo-documentos.update', $tipoDocumento),
            $data
        );

        $data['id'] = $tipoDocumento->id;

        $this->assertDatabaseHas('cor_tipo_documentos', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_tipo_documento()
    {
        $tipoDocumento = TipoDocumento::factory()->create();

        $response = $this->deleteJson(
            route('api.tipo-documentos.destroy', $tipoDocumento)
        );

        $this->assertDeleted($tipoDocumento);

        $response->assertNoContent();
    }
}
