<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\RemitenteExterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RemitenteExternoTest extends TestCase
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
    public function it_gets_remitente_externos_list()
    {
        $remitenteExternos = RemitenteExterno::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.remitente-externos.index'));

        $response
            ->assertOk()
            ->assertSee($remitenteExternos[0]->nombre_completo);
    }

    /**
     * @test
     */
    public function it_stores_the_remitente_externo()
    {
        $data = RemitenteExterno::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(
            route('api.remitente-externos.store'),
            $data
        );

        $this->assertDatabaseHas('cor_remitente_externos', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
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

        $response = $this->putJson(
            route('api.remitente-externos.update', $remitenteExterno),
            $data
        );

        $data['id'] = $remitenteExterno->id;

        $this->assertDatabaseHas('cor_remitente_externos', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_remitente_externo()
    {
        $remitenteExterno = RemitenteExterno::factory()->create();

        $response = $this->deleteJson(
            route('api.remitente-externos.destroy', $remitenteExterno)
        );

        $this->assertDeleted($remitenteExterno);

        $response->assertNoContent();
    }
}
