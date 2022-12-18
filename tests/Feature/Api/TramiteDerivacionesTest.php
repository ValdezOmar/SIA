<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tramite;
use App\Models\Derivacion;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TramiteDerivacionesTest extends TestCase
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
    public function it_gets_tramite_derivaciones()
    {
        $tramite = Tramite::factory()->create();
        $derivaciones = Derivacion::factory()
            ->count(2)
            ->create([
                'tramite_id' => $tramite->id,
            ]);

        $response = $this->getJson(
            route('api.tramites.derivaciones.index', $tramite)
        );

        $response->assertOk()->assertSee($derivaciones[0]->proveido);
    }

    /**
     * @test
     */
    public function it_stores_the_tramite_derivaciones()
    {
        $tramite = Tramite::factory()->create();
        $data = Derivacion::factory()
            ->make([
                'tramite_id' => $tramite->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.tramites.derivaciones.store', $tramite),
            $data
        );

        $this->assertDatabaseHas('cor_derivaciones', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $derivacion = Derivacion::latest('id')->first();

        $this->assertEquals($tramite->id, $derivacion->tramite_id);
    }
}
