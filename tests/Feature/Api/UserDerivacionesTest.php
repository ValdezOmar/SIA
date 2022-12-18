<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Derivacion;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserDerivacionesTest extends TestCase
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
    public function it_gets_user_derivaciones()
    {
        $user = User::factory()->create();
        $derivaciones = Derivacion::factory()
            ->count(2)
            ->create([
                'remitente_id' => $user->id,
            ]);

        $response = $this->getJson(
            route('api.users.derivaciones.index', $user)
        );

        $response->assertOk()->assertSee($derivaciones[0]->proveido);
    }

    /**
     * @test
     */
    public function it_stores_the_user_derivaciones()
    {
        $user = User::factory()->create();
        $data = Derivacion::factory()
            ->make([
                'remitente_id' => $user->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.users.derivaciones.store', $user),
            $data
        );

        $this->assertDatabaseHas('cor_derivaciones', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $derivacion = Derivacion::latest('id')->first();

        $this->assertEquals($user->id, $derivacion->remitente_id);
    }
}
