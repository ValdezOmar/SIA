<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\FilePersonal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilePersonalUsersTest extends TestCase
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
    public function it_gets_file_personal_users()
    {
        $filePersonal = FilePersonal::factory()->create();
        $users = User::factory()
            ->count(2)
            ->create([
                'file_personal_id' => $filePersonal->id,
            ]);

        $response = $this->getJson(
            route('api.file-personal.users.index', $filePersonal)
        );

        $response->assertOk()->assertSee($users[0]->name);
    }

    /**
     * @test
     */
    public function it_stores_the_file_personal_users()
    {
        $filePersonal = FilePersonal::factory()->create();
        $data = User::factory()
            ->make([
                'file_personal_id' => $filePersonal->id,
            ])
            ->toArray();
        $data['password'] = \Str::random('8');

        $response = $this->postJson(
            route('api.file-personal.users.store', $filePersonal),
            $data
        );

        unset($data['password']);
        unset($data['email_verified_at']);

        $this->assertDatabaseHas('users', $data);

        $response->assertStatus(201)->assertJsonFragment($data);

        $user = User::latest('id')->first();

        $this->assertEquals($filePersonal->id, $user->file_personal_id);
    }
}
