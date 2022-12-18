<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Derivacion;

use App\Models\Tramite;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DerivacionTest extends TestCase
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
    public function it_gets_derivaciones_list()
    {
        $derivaciones = Derivacion::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.derivaciones.index'));

        $response->assertOk()->assertSee($derivaciones[0]->proveido);
    }

    /**
     * @test
     */
    public function it_stores_the_derivacion()
    {
        $data = Derivacion::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.derivaciones.store'), $data);

        $this->assertDatabaseHas('cor_derivaciones', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_derivacion()
    {
        $derivacion = Derivacion::factory()->create();

        $tramite = Tramite::factory()->create();
        $user = User::factory()->create();

        $data = [
            'fecha_derivacion' => $this->faker->dateTime,
            'fecha_recepcion' => $this->faker->dateTime,
            'fecha_rechazo' => $this->faker->dateTime,
            'fecha_anulado' => $this->faker->dateTime,
            'fecha_archivo' => $this->faker->dateTime,
            'proveido' => $this->faker->text(255),
            'destinatario_id' => $this->faker->randomNumber,
            'tramite_id' => $tramite->id,
            'remitente_id' => $user->id,
        ];

        $response = $this->putJson(
            route('api.derivaciones.update', $derivacion),
            $data
        );

        $data['id'] = $derivacion->id;

        $this->assertDatabaseHas('cor_derivaciones', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_derivacion()
    {
        $derivacion = Derivacion::factory()->create();

        $response = $this->deleteJson(
            route('api.derivaciones.destroy', $derivacion)
        );

        $this->assertSoftDeleted($derivacion);

        $response->assertNoContent();
    }
}
