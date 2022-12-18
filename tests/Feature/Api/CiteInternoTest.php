<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\CiteInterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CiteInternoTest extends TestCase
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
    public function it_gets_cite_internos_list()
    {
        $citeInternos = CiteInterno::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.cite-internos.index'));

        $response->assertOk()->assertSee($citeInternos[0]->cite_interno);
    }

    /**
     * @test
     */
    public function it_stores_the_cite_interno()
    {
        $data = CiteInterno::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.cite-internos.store'), $data);

        $this->assertDatabaseHas('cor_cite_internos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_cite_interno()
    {
        $citeInterno = CiteInterno::factory()->create();

        $data = [
            'cite_interno' => $this->faker->text(255),
            'asunto' => $this->faker->text(255),
        ];

        $response = $this->putJson(
            route('api.cite-internos.update', $citeInterno),
            $data
        );

        $data['id'] = $citeInterno->id;

        $this->assertDatabaseHas('cor_cite_internos', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_cite_interno()
    {
        $citeInterno = CiteInterno::factory()->create();

        $response = $this->deleteJson(
            route('api.cite-internos.destroy', $citeInterno)
        );

        $this->assertSoftDeleted($citeInterno);

        $response->assertNoContent();
    }
}
