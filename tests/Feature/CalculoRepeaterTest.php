<?php

namespace Tests\Feature;

use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Schema;
use App\Forms\Components\CalculoRepeater;
use App\Support\CalculoDetalle;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class CalculoRepeaterTest extends TestCase
{
    public function test_escritura_no_es_reactiva_y_calcula_al_pulsar(): void
    {
        $test = Livewire::test(FormularioCalculoPrueba::class);
        $key = array_key_first($test->get('data.detalles'));
        $path = 'data.detalles.'.$key;
        $repeater = $test->instance()->form->getComponent('detalles');
        $fields = array_values($repeater->getChildComponentContainers())[0]->getFlatFields();
        $this->assertFalse($repeater->isLive());
        $this->assertFalse($fields['cantidad']->isLive());
        $this->assertFalse($fields['precio_unitario']->isLive());
        $test->set($path.'.cantidad', '123')
            ->set($path.'.precio_unitario', '12.50')
            ->set($path.'.descuento_porcentaje', '10')
            ->set($path.'._descuento_tipo', 'porcentaje')
            ->assertSet($path.'.total', 0)
            ->callAction(\Filament\Actions\Testing\TestAction::make('calcular')->schemaComponent('detalles', 'form'))
            ->assertSet($path.'.descuento', 153.75)
            ->assertSet($path.'.total', 1383.75);
    }

    public function test_guardar_recalcula_sin_exigir_click_y_sin_persistir_metadatos(): void
    {
        $test = Livewire::test(FormularioCalculoPrueba::class);
        $path = 'data.detalles.'.array_key_first($test->get('data.detalles'));
        $test->set($path.'.cantidad', 3)
            ->set($path.'.precio_unitario', 100)
            ->set($path.'.descuento', 30)
            ->set($path.'.aplicar_iva', true)
            ->call('guardar')
            ->assertHasNoFormErrors()
            ->assertSet('guardado.detalles.0.total', 305.1)
            ->assertSet('guardado.detalles.0._descuento_tipo', null);
    }

    public function test_calculos_de_compras_recepcion_y_solicitud(): void
    {
        $this->assertSame(203.4, CalculoDetalle::calcular(['cantidad' => 2, 'precio_unitario' => 100, 'descuento' => 20], 'compra')['total']);
        $this->assertSame(12.5, CalculoDetalle::calcular(['cantidad_aceptada' => 5, 'costo_unitario' => 2.5], 'recepcion')['costo_total']);
        $this->assertSame(7.5, CalculoDetalle::calcular(['cantidad' => 3, 'precio_estimado' => 2.5], 'solicitud')['subtotal']);
    }

    public function test_cambios_sucesivos_de_porcentaje_importe_y_cantidad_no_duplican_descuentos(): void
    {
        $test = Livewire::test(FormularioCalculoPrueba::class);
        $path = 'data.detalles.'.array_key_first($test->get('data.detalles'));
        $test->set($path.'.cantidad', 2)->set($path.'.precio_unitario', 100)
            ->set($path.'.descuento_porcentaje', 10)->set($path.'._descuento_tipo', 'porcentaje')
            ->callAction(\Filament\Actions\Testing\TestAction::make('calcular')->schemaComponent('detalles', 'form'))
            ->assertSet($path.'.total', 180.0)
            ->set($path.'.cantidad', 3)
            ->callAction(\Filament\Actions\Testing\TestAction::make('calcular')->schemaComponent('detalles', 'form'))
            ->assertSet($path.'.descuento', 30.0)->assertSet($path.'.total', 270.0)
            ->set($path.'.descuento', 15)->set($path.'._descuento_tipo', 'importe')
            ->callAction(\Filament\Actions\Testing\TestAction::make('calcular')->schemaComponent('detalles', 'form'))
            ->assertSet($path.'.descuento_porcentaje', 5.0)->assertSet($path.'.total', 285.0)
            ->assertSet('data.total', 285.0);
    }
}

class FormularioCalculoPrueba extends Component implements HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    public array $data = [];

    public array $guardado = [];

    public function mount(): void
    {
        $this->form->fill(['detalles' => ['linea' => ['cantidad' => 1, 'precio_unitario' => 0, 'descuento' => 0, 'descuento_porcentaje' => 0, 'aplicar_iva' => false, 'total' => 0]]]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            CalculoRepeater::make('detalles')->key('detalles')->calculo('venta')->live()->schema([
                TextInput::make('cantidad')->numeric(),
                TextInput::make('precio_unitario')->numeric(),
                TextInput::make('descuento')->numeric(),
                TextInput::make('descuento_porcentaje')->numeric(),
                Toggle::make('aplicar_iva')->live(),
                Hidden::make('subtotal'), Hidden::make('impuesto'), Hidden::make('total'),
            ]),
        ]);
    }

    public function guardar(): void
    {
        $this->guardado = $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
