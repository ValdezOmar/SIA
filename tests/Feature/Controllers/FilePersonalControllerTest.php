<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\FilePersonal;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FilePersonalControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(
            User::factory()->create(['email' => 'admin@admin.com'])
        );

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_displays_index_view_with_file_personal()
    {
        $filePersonal = FilePersonal::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('file-personal.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.file_personal.index')
            ->assertViewHas('filePersonal');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_file_personal()
    {
        $response = $this->get(route('file-personal.create'));

        $response->assertOk()->assertViewIs('app.file_personal.create');
    }

    /**
     * @test
     */
    public function it_stores_the_file_personal()
    {
        $data = FilePersonal::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('file-personal.store'), $data);

        $this->assertDatabaseHas('hr_file_personal', $data);

        $filePersonal = FilePersonal::latest('id')->first();

        $response->assertRedirect(route('file-personal.edit', $filePersonal));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_file_personal()
    {
        $filePersonal = FilePersonal::factory()->create();

        $response = $this->get(route('file-personal.show', $filePersonal));

        $response
            ->assertOk()
            ->assertViewIs('app.file_personal.show')
            ->assertViewHas('filePersonal');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_file_personal()
    {
        $filePersonal = FilePersonal::factory()->create();

        $response = $this->get(route('file-personal.edit', $filePersonal));

        $response
            ->assertOk()
            ->assertViewIs('app.file_personal.edit')
            ->assertViewHas('filePersonal');
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

        $response = $this->put(
            route('file-personal.update', $filePersonal),
            $data
        );

        $data['id'] = $filePersonal->id;

        $this->assertDatabaseHas('hr_file_personal', $data);

        $response->assertRedirect(route('file-personal.edit', $filePersonal));
    }

    /**
     * @test
     */
    public function it_deletes_the_file_personal()
    {
        $filePersonal = FilePersonal::factory()->create();

        $response = $this->delete(
            route('file-personal.destroy', $filePersonal)
        );

        $response->assertRedirect(route('file-personal.index'));

        $this->assertSoftDeleted($filePersonal);
    }
}
