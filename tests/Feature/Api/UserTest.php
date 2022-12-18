<?php

namespace Tests\Feature\Api;

use App\Models\User;

use App\Models\Cargo;
use App\Models\FilePersonal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserTest extends TestCase
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
    public function it_gets_users_list()
    {
        $users = User::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.users.index'));

        $response->assertOk()->assertSee($users[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_user()
    {
        $data = User::factory()
            ->make()
            ->toArray();
        $data['password'] = \Str::random('8');

        $response = $this->postJson(route('api.users.store'), $data);

        unset($data['password']);
        unset($data['email_verified_at']);

        $this->assertDatabaseHas('users', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_user()
    {
        $user = User::factory()->create();

        $cargo = Cargo::factory()->create();
        $filePersonal = FilePersonal::factory()->create();

        $data = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique->email,
            'fecha_alta' => $this->faker->date,
            'fecha_baja' => $this->faker->date,
            'fecha_cambio' => $this->faker->date,
            'telefono_int' => $this->faker->text(255),
            'activo' => $this->faker->boolean,
            'cargo_id' => $cargo->id,
            'file_personal_id' => $filePersonal->id,
        ];

        $data['password'] = \Str::random('8');

        $response = $this->putJson(route('api.users.update', $user), $data);

        unset($data['password']);
        unset($data['email_verified_at']);

        $data['id'] = $user->id;

        $this->assertDatabaseHas('users', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_user()
    {
        $user = User::factory()->create();

        $response = $this->deleteJson(route('api.users.destroy', $user));

        $this->assertSoftDeleted($user);

        $response->assertNoContent();
    }
}
