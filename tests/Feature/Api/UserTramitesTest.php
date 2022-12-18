<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Tramite;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTramitesTest extends TestCase
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
    public function it_gets_user_tramites()
    {
        $user = User::factory()->create();
        $tramites = Tramite::factory()
            ->count(2)
            ->create([
                'remitente_interno_id' => $user->id,
            ]);

        $response = $this->getJson(route('api.users.tramites.index', $user));

        $response->assertOk()->assertSee($tramites[0]->hoja_ruta);
    }

    /**
     * @test
     */
    public function it_stores_the_user_tramites()
    {
        $user = User::factory()->create();
        $data = Tramite::factory()
            ->make([
                'remitente_interno_id' => $user->id,
            ])
            ->toArray();

        $response = $this->postJson(
            route('api.users.tramites.store', $user),
            $data
        );

        $this->assertDatabaseHas('cor_tramites', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $tramite = Tramite::latest('id')->first();

        $this->assertEquals($user->id, $tramite->remitente_interno_id);
    }
}
