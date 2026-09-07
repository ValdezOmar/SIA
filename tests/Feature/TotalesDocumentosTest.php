<?php

namespace Tests\Feature;

use App\Forms\Components\CalculoRepeater;
use App\Models\Inventario\Articulo;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Cotizacion;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class TotalesDocumentosTest extends TestCase
{
    use RefreshDatabase;

    public function test_resumen_usa_formulario_incluso_al_borrar_todas_las_filas(): void
    {
        $clases = [
            \App\Filament\Resources\Ventas\FacturaResource::class,
            \App\Filament\Resources\Ventas\PedidoResource::class,
            \App\Filament\Resources\Ventas\CotizacionResource::class,
            \App\Filament\Resources\Ventas\ClienteResource\RelationManagers\PedidosRelationManager::class,
            \App\Filament\Resources\Ventas\ClienteResource\RelationManagers\CotizacionesRelationManager::class,
            \App\Filament\Resources\Compras\FacturaCompraResource::class,
            \App\Filament\Resources\Compras\OrdenCompraResource::class,
        ];
        $anterior = new Cotizacion(['subtotal' => 999, 'total' => 999]);
        $anterior->exists = true;
        foreach ($clases as $clase) {
            $metodo = new \ReflectionMethod($clase, 'calcularTotales');
            $filas = [['subtotal' => 180, 'descuento' => 20, 'impuesto' => 23.4, 'total' => 203.4]];
            $actual = $metodo->invoke(null, fn ($campo) => $filas, $anterior);
            $this->assertSame(203.4, $actual['total'], $clase);
            $this->assertSame(180.0, $actual['subtotal'], $clase);
            $vacio = $metodo->invoke(null, fn ($campo) => [], $anterior);
            $this->assertSame(0.0, $vacio['total'], $clase);
        }
    }

    public function test_guardar_editar_y_eliminar_filas_actualiza_cabecera_despues_de_las_relaciones(): void
    {
        $cliente = Cliente::create(['codigo' => 'CLI-TOT', 'nombre' => 'Cliente']);
        $articulo = Articulo::create(['codigo' => 'SRV-TOT', 'nombre_comercial' => 'Servicio', 'inventariable' => false]);
        $cotizacion = Cotizacion::create(['cliente_id' => $cliente->id, 'fecha_emision' => today(), 'moneda' => 'BOB']);
        $fila = ['articulo_id' => $articulo->id, 'codigo_articulo' => 'SRV-TOT', 'descripcion_articulo' => 'Servicio', 'cantidad' => 2, 'precio_unitario' => 100, 'descuento' => 0, 'descuento_porcentaje' => 10, '_descuento_tipo' => 'porcentaje', 'aplicar_iva' => true];
        $test = Livewire::test(FormularioDocumentoCalculoPrueba::class, ['record' => $cotizacion])
            ->set('data.detalles', ['nueva' => $fila])
            ->call('guardar')
            ->assertHasNoFormErrors();
        $this->assertSame(203.4, (float) $cotizacion->fresh()->total);
        $detalle = $cotizacion->detalles()->firstOrFail();
        $this->assertSame(180.0, (float) $detalle->subtotal);

        $key = array_key_first($test->get('data.detalles'));
        $test->set('data.detalles.'.$key.'.cantidad', 3)
            ->set('data.detalles.'.$key.'.descuento', 30)
            ->set('data.detalles.'.$key.'._descuento_tipo', 'importe')
            ->call('guardar')->assertHasNoFormErrors();
        $this->assertSame(305.1, (float) $cotizacion->fresh()->total);
        $this->assertSame(270.0, (float) $detalle->fresh()->subtotal);
        $test->set('data.detalles', [])->call('guardar')->assertHasNoFormErrors();
        $this->assertSame(0.0, (float) $cotizacion->fresh()->total);
        $this->assertSame(0, $cotizacion->detalles()->count());
    }
}

class FormularioDocumentoCalculoPrueba extends Component implements HasForms
{
    use InteractsWithForms;

    public Cotizacion $record;

    public array $data = [];

    public function mount(Cotizacion $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form->statePath('data')->model($this->record)->schema([
            CalculoRepeater::make('detalles')->calculo('venta')->relationship('detalles')->defaultItems(0)->schema([
                Hidden::make('articulo_id'), Hidden::make('codigo_articulo'), Hidden::make('descripcion_articulo'),
                TextInput::make('cantidad')->numeric(), TextInput::make('precio_unitario')->numeric(),
                TextInput::make('descuento')->numeric(), TextInput::make('descuento_porcentaje')->numeric(),
                Toggle::make('aplicar_iva'),
                Hidden::make('subtotal'), Hidden::make('impuesto'), Hidden::make('total'),
            ]),
        ]);
    }

    public function guardar(): void
    {
        $this->form->getState();
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
