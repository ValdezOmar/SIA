<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\CiteInterno;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CiteInternoControllerTest extends TestCase
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
    public function it_displays_index_view_with_cite_internos()
    {
        $citeInternos = CiteInterno::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('cite-internos.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.cite_internos.index')
            ->assertViewHas('citeInternos');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_cite_interno()
    {
        $response = $this->get(route('cite-internos.create'));

        $response->assertOk()->assertViewIs('app.cite_internos.create');
    }

    /**
     * @test
     */
    public function it_stores_the_cite_interno()
    {
        $data = CiteInterno::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('cite-internos.store'), $data);

        $this->assertDatabaseHas('cor_cite_internos', $data);

        $citeInterno = CiteInterno::latest('id')->first();

        $response->assertRedirect(route('cite-internos.edit', $citeInterno));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_cite_interno()
    {
        $citeInterno = CiteInterno::factory()->create();

        $response = $this->get(route('cite-internos.show', $citeInterno));

        $response
            ->assertOk()
            ->assertViewIs('app.cite_internos.show')
            ->assertViewHas('citeInterno');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_cite_interno()
    {
        $citeInterno = CiteInterno::factory()->create();

        $response = $this->get(route('cite-internos.edit', $citeInterno));

        $response
            ->assertOk()
            ->assertViewIs('app.cite_internos.edit')
            ->assertViewHas('citeInterno');
    }

    /**
     * @test
     */
    public function it_updates_the_cite_interno()
    {
        $citeInterno = CiteInterno::factory()->create();

        $data = [
            'cite_interno' => $this->faker->text(255),
            'asunto' => $this->faker->text(255),
        ];

        $response = $this->put(
            route('cite-internos.update', $citeInterno),
            $data
        );

        $data['id'] = $citeInterno->id;

        $this->assertDatabaseHas('cor_cite_internos', $data);

        $response->assertRedirect(route('cite-internos.edit', $citeInterno));
    }

    /**
     * @test
     */
    public function it_deletes_the_cite_interno()
    {
        $citeInterno = CiteInterno::factory()->create();

        $response = $this->delete(route('cite-internos.destroy', $citeInterno));

        $response->assertRedirect(route('cite-internos.index'));

        $this->assertSoftDeleted($citeInterno);
    }
}
