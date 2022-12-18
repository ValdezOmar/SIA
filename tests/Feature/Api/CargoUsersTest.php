<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Cargo;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CargoUsersTest extends TestCase
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
    public function it_gets_cargo_users()
    {
        $cargo = Cargo::factory()->create();
        $users = User::factory()
            ->count(2)
            ->create([
                'cargo_id' => $cargo->id,
            ]);

        $response = $this->getJson(route('api.cargos.users.index', $cargo));

        $response->assertOk()->assertSee($users[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_cargo_users()
    {
        $cargo = Cargo::factory()->create();
        $data = User::factory()
            ->make([
                'cargo_id' => $cargo->id,
            ])
            ->toArray();
        $data['password'] = \Str::random('8');

        $response = $this->postJson(
            route('api.cargos.users.store', $cargo),
            $data
        );

        unset($data['password']);
        unset($data['email_verified_at']);

        $this->assertDatabaseHas('users', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $user = User::latest('id')->first();

        $this->assertEquals($cargo->id, $user->cargo_id);
    }
}
