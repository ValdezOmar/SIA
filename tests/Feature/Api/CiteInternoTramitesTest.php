<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tramite;
use App\Models\CiteInterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CiteInternoTramitesTest extends TestCase
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
    public function it_gets_cite_interno_tramites()
    {
        $citeInterno = CiteInterno::factory()->create();
        $tramites = Tramite::factory()
            ->count(2)
            ->create([
                'cite_interno_id' => $citeInterno->id,
            ]);

        $response = $this->getJson(
            route('api.cite-internos.tramites.index', $citeInterno)
        );

        $response->assertOk()->assertSee($tramites[0]->hoja_ruta);
    }

    /**
     * @test
     */
    public function it_stores_the_cite_interno_tramites()
    {
        $citeInterno = CiteInterno::factory()->create();
        $data = Tramite::factory()
            ->make([
                'cite_interno_id' => $citeInterno->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.cite-internos.tramites.store', $citeInterno),
            $data
        );

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $tramite = Tramite::latest('id')->first();

        $this->assertEquals($citeInterno->id, $tramite->cite_interno_id);
    }
}
