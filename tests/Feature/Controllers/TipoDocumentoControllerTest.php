<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use App\Models\TipoDocumento;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TipoDocumentoControllerTest extends TestCase
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
    public function it_displays_index_view_with_tipo_documentos()
    {
        $tipoDocumentos = TipoDocumento::factory()
            ->count(5)
            ->create();

        $response = $this->get(route('tipo-documentos.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.tipo_documentos.index')
            ->assertViewHas('tipoDocumentos');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_tipo_documento()
    {
        $response = $this->get(route('tipo-documentos.create'));

        $response->assertOk()->assertViewIs('app.tipo_documentos.create');
    }

    /**
     * @test
     */
    public function it_stores_the_tipo_documento()
    {
        $data = TipoDocumento::factory()
            ->make()
            ->toArray();

        $response = $this->post(route('tipo-documentos.store'), $data);

        $this->assertDatabaseHas('cor_tipo_documentos', $data);

        $tipoDocumento = TipoDocumento::latest('id')->first();

        $response->assertRedirect(
            route('tipo-documentos.edit', $tipoDocumento)
        );
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_tipo_documento()
    {
        $tipoDocumento = TipoDocumento::factory()->create();

        $response = $this->get(route('tipo-documentos.show', $tipoDocumento));

        $response
            ->assertOk()
            ->assertViewIs('app.tipo_documentos.show')
            ->assertViewHas('tipoDocumento');
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_tipo_documento()
    {
        $tipoDocumento = TipoDocumento::factory()->create();

        $response = $this->get(route('tipo-documentos.edit', $tipoDocumento));

        $response
            ->assertOk()
            ->assertViewIs('app.tipo_documentos.edit')
            ->assertViewHas('tipoDocumento');
    }

    /**
     * @test
     */
    public function it_updates_the_tipo_documento()
    {
        $tipoDocumento = TipoDocumento::factory()->create();

        $data = [
            'tipo_documento' => $this->faker->text(255),
        ];

        $response = $this->put(
            route('tipo-documentos.update', $tipoDocumento),
            $data
        );

        $data['id'] = $tipoDocumento->id;

        $this->assertDatabaseHas('cor_tipo_documentos', $data);

        $response->assertRedirect(
            route('tipo-documentos.edit', $tipoDocumento)
        );
    }

    /**
     * @test
     */
    public function it_deletes_the_tipo_documento()
    {
        $tipoDocumento = TipoDocumento::factory()->create();

        $response = $this->delete(
            route('tipo-documentos.destroy', $tipoDocumento)
        );

        $response->assertRedirect(route('tipo-documentos.index'));

        $this->assertDeleted($tipoDocumento);
    }
}
