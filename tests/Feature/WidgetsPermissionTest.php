<?php

namespace Tests\Feature;

use App\Filament\Widgets\AnalisisComercialWidget;
use App\Filament\Widgets\ContabilidadResumenWidget;
use App\Filament\Widgets\ContabilidadTendenciaWidget;
use App\Filament\Widgets\GananciaBrutaWidget;
use App\Filament\Widgets\InventarioResumenWidget;
use App\Filament\Widgets\InventarioTendenciaWidget;
use App\Filament\Widgets\VentasResumenWidget;
use App\Filament\Widgets\VentasTendenciaWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\TableWidget;
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
            AnalisisComercialWidget::class,
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

    public function test_ganancia_bruta_conserva_el_formato_de_tabla(): void
    {
        $widget = app(GananciaBrutaWidget::class);
        $this->assertInstanceOf(TableWidget::class, $widget);
    }

    public function test_analisis_comercial_usa_un_solo_widget(): void
    {
        $widget = app(AnalisisComercialWidget::class);

        $this->assertInstanceOf(AnalisisComercialWidget::class, $widget);
        $this->assertCount(4, $widget->pestanas());
    }
}
