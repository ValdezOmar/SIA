<?php

namespace Tests\Feature;

use App\Filament\Resources\Ventas\CotizacionResource;
use App\Filament\Resources\Ventas\FacturaResource;
use App\Filament\Resources\Ventas\PedidoResource;
use App\Models\Inventario\Articulo;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Cotizacion;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pedido;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class VentasFormularioRealTest extends TestCase
{
    use RefreshDatabase;

    public function test_formularios_reales_calculan_y_muestran_subtotal_y_total(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Prueba', 'nombre_comercial' => 'Prueba', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $cliente = Cliente::create(['codigo' => 'CLI-REAL', 'nombre' => 'Cliente', 'empresa_id' => $empresa]);
        $articulo = Articulo::create(['codigo' => 'SRV-REAL', 'nombre_comercial' => 'Servicio', 'inventariable' => false, 'empresa_id' => $empresa]);
        foreach ([FacturaResource::class => Factura::class, PedidoResource::class => Pedido::class, CotizacionResource::class => Cotizacion::class] as $recurso => $modelo) {
            $fecha = $modelo === Pedido::class ? 'fecha_pedido' : 'fecha_emision';
            $record = $modelo::create(['cliente_id' => $cliente->id, 'empresa_id' => $empresa, $fecha => today(), 'moneda' => 'BOB', 'condicion_pago' => 'parcial']);
            $record->detalles()->create(['articulo_id' => $articulo->id, 'codigo_articulo' => $articulo->codigo, 'descripcion_articulo' => 'Servicio', 'cantidad' => 1, 'precio_unitario' => 100, 'subtotal' => 100, 'total' => 100]);
            $test = Livewire::test(FormularioVentasReal::class, ['record' => $record, 'recurso' => $recurso]);
            $key = array_key_first($test->get('data.detalles'));
            $path = 'data.detalles.'.$key;
            $test->set($path.'.cantidad', 3)->set($path.'.precio_unitario', 100)
                ->set($path.'.descuento', 30)->set($path.'._descuento_tipo', 'importe')
                ->set($path.'.aplicar_iva', true)
                ->call('mountFormComponentAction', 'data.detalles', 'calcular')
                ->assertSet($path.'.subtotal', 270.0)
                ->assertSet($path.'.total', 305.1);
            $components = $test->instance()->form->getFlatComponents(withHidden: true);
            $subtotal = collect($components)->first(fn ($c) => $c->getStatePath() === $path.'.subtotal_linea');
            $total = collect($components)->first(fn ($c) => $c->getStatePath() === $path.'.total_con_iva');
            $this->assertStringContainsString('270.00', (string) $subtotal->getContent(), $recurso);
            $this->assertStringContainsString('305.10', (string) $total->getContent(), $recurso);
            $this->assertStringContainsString($path.'.subtotal', $subtotal->getExtraAttributes()['x-text']);
            $this->assertStringContainsString($path.'.total', $total->getExtraAttributes()['x-text']);
            $repeater = $test->instance()->form->getComponent('data.detalles');
            $fields = $repeater->getChildComponentContainers()[$key]->getFlatFields();
            $this->assertStringContainsString('siaVentasImportes.actualizar', $fields['cantidad']->getExtraInputAttributes()['x-on:change']);
            $this->assertFalse($fields['cantidad']->isLive());
            $this->assertArrayNotHasKey('x-on:click', $fields['aplicar_iva']->getExtraAttributes());
        }
    }

    public function test_factura_nueva_calcula_fila_sin_totales_precargados(): void
    {
        $this->actingAs(User::factory()->create());
        $test = Livewire::test(FormularioVentasReal::class, ['record' => new Factura, 'recurso' => FacturaResource::class])
            ->set('data.detalles', ['nueva' => ['cantidad' => 2, 'precio_unitario' => 125, 'descuento' => 25, 'aplicar_iva' => false]])
            ->call('mountFormComponentAction', 'data.detalles', 'calcular')
            ->assertSet('data.detalles.nueva.subtotal', 225.0)
            ->assertSet('data.detalles.nueva.total', 225.0);
        $total = collect($test->instance()->form->getFlatComponents(withHidden: true))
            ->first(fn ($c) => $c->getStatePath() === 'data.detalles.nueva.total_con_iva');
        $this->assertStringContainsString('225.00', (string) $total->getContent());
    }
}

class FormularioVentasReal extends Component implements HasForms
{
    use InteractsWithForms;

    public Model $record;

    public string $recurso;

    public array $data = [];

    public function mount(Model $record, string $recurso): void
    {
        $this->record = $record;
        $this->recurso = $recurso;
        $this->form->fill($record->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $this->recurso::form($form->model($this->record)->operation($this->record->exists ? 'edit' : 'create'))->statePath('data');
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
