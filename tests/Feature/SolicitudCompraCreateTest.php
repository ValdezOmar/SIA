<?php

namespace Tests\Feature;

use App\Filament\Resources\Compras\SolicitudCompraResource\Pages\CreateSolicitudCompra;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SolicitudCompraCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_formulario_de_creacion_se_renderiza(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateSolicitudCompra::class)->assertSuccessful();
    }
}
