<?php

namespace Tests\Feature;

use App\Filament\Widgets\ContabilidadResumenWidget;
use App\Filament\Widgets\ContabilidadTendenciaWidget;
use App\Filament\Widgets\GananciaBrutaWidget;
use App\Filament\Widgets\InventarioResumenWidget;
use App\Filament\Widgets\InventarioTendenciaWidget;
use App\Filament\Widgets\VentasResumenWidget;
use App\Filament\Widgets\VentasTendenciaWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class WidgetsPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_widget_exige_su_permiso_de_shield(): void
    {
        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        foreach ([
            ContabilidadResumenWidget::class,
            ContabilidadTendenciaWidget::class,
            GananciaBrutaWidget::class,
            InventarioResumenWidget::class,
            InventarioTendenciaWidget::class,
            VentasResumenWidget::class,
            VentasTendenciaWidget::class,
        ] as $widget) {
            $method = new \ReflectionMethod($widget, 'canViewWithShieldPermission');
            $method->setAccessible(true);
            $this->assertFalse($method->invoke(null), $widget);

            $permiso = Permission::findOrCreate('widget_'.class_basename($widget), 'web');
            $usuario->givePermissionTo($permiso);
            $this->assertTrue($method->invoke(null), $widget);
            $usuario->revokePermissionTo($permiso);
        }
    }
}
