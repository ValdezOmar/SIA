<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\FilePersonal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilePersonalTest extends TestCase
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
    public function it_gets_file_personal_list()
    {
        $filePersonal = FilePersonal::factory()
            ->count(5)
            ->create();

        $response = $this->getJson(route('api.file-personal.index'));

        $response->assertOk()->assertSee($filePersonal[0]->nombres);
    }

    /**
     * @test
     */
    public function it_stores_the_file_personal()
    {
        $data = FilePersonal::factory()
            ->make()
            ->toArray();

        $response = $this->postJson(route('api.file-personal.store'), $data);

        $this->assertDatabaseHas('hr_file_personal', $data);

        $response->assertStatus(201)->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_updates_the_file_personal()
    {
        $filePersonal = FilePersonal::factory()->create();

        $data = [
            'nombres' => $this->faker->name(),
            'apellidos' => $this->faker->lastName,
            'fecha_nacimiento' => $this->faker->date,
            'CI' => $this->faker->text(255),
            'direccion' => $this->faker->address,
            'telefono_1' => $this->faker->text(255),
            'telefono_2' => $this->faker->text(255),
            'email_personal' => $this->faker->text(255),
            'presentacion' => $this->faker->sentence(15),
        ];

        $response = $this->putJson(
            route('api.file-personal.update', $filePersonal),
            $data
        );

        $data['id'] = $filePersonal->id;

        $this->assertDatabaseHas('hr_file_personal', $data);

        $response->assertOk()->assertJsonFragment($data);
    }

    /**
     * @test
     */
    public function it_deletes_the_file_personal()
    {
        $filePersonal = FilePersonal::factory()->create();

        $response = $this->deleteJson(
            route('api.file-personal.destroy', $filePersonal)
        );

        $this->assertSoftDeleted($filePersonal);

        $response->assertNoContent();
    }
}
