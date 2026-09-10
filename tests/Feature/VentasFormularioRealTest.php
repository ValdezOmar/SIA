<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Schemas\Schema;
use App\Filament\Resources\Ventas\CotizacionResource;
use App\Filament\Resources\Ventas\FacturaResource;
use App\Filament\Resources\Ventas\PedidoResource;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\ListaPrecio;
use App\Models\User;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Cotizacion;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Pedido;
use App\Support\ArticuloSelectOptions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Livewire;
use Tests\TestCase;

class VentasFormularioRealTest extends TestCase
{
    use RefreshDatabase;

    public function test_seleccionar_articulo_y_lista_actualiza_precio_e_importes(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Prueba', 'nombre_comercial' => 'Prueba', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $listas = collect(['Normal', 'Mayorista'])->map(fn ($nombre) => ListaPrecio::create(['nombre' => $nombre, 'moneda' => 'BOB', 'empresa_id' => $empresa]));
        $articulos = collect([100, 200, 0])->map(function ($precio) use ($empresa, $listas) {
            $articulo = Articulo::create(['codigo' => 'SRV-'.$precio, 'nombre_comercial' => 'Servicio '.$precio, 'inventariable' => false, 'empresa_id' => $empresa]);
            if ($precio) {
                foreach ($listas as $i => $lista) {
                    $articulo->precios()->create(['lista_precio_id' => $lista->id, 'precio' => $precio - $i * 20]);
                }
            }

            return $articulo;
        });
        foreach ([FacturaResource::class => Factura::class, PedidoResource::class => Pedido::class, CotizacionResource::class => Cotizacion::class] as $recurso => $modelo) {
            $test = Livewire::test(FormularioVentasReal::class, ['record' => new $modelo, 'recurso' => $recurso])
                ->set('data.detalles', ['nueva' => ['cantidad' => 2, 'descuento' => 0, 'aplicar_iva' => false]])
                ->set('data.detalles.nueva.articulo_id', $articulos[0]->id)
                ->assertSet('data.detalles.nueva.lista_precio', $listas[0]->id)
                ->assertSet('data.detalles.nueva.precio_unitario', 100.0)
                ->assertSet('data.detalles.nueva.total', 200.0)
                ->set('data.detalles.nueva.lista_precio', $listas[1]->id)
                ->assertSet('data.detalles.nueva.precio_unitario', 80.0)
                ->assertSet('data.detalles.nueva.total', 160.0)
                ->set('data.detalles.nueva.articulo_id', $articulos[1]->id)
                ->assertSet('data.detalles.nueva.precio_unitario', 200.0)
                ->assertSet('data.detalles.nueva.total', 400.0);
            $test->set('data.detalles.nueva.articulo_id', $articulos[2]->id)
                ->assertSet('data.detalles.nueva.precio_unitario', 0.0)
                ->assertSet('data.detalles.nueva.total', 0.0);
        }
    }

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
        }
    }

    public function test_factura_nueva_calcula_fila_sin_totales_precargados(): void
    {
        $this->actingAs(User::factory()->create());
        $test = Livewire::test(FormularioVentasReal::class, ['record' => new Factura, 'recurso' => FacturaResource::class])
            ->set('data.detalles', ['nueva' => ['cantidad' => 2, 'precio_unitario' => 125, 'descuento' => 25, 'aplicar_iva' => false]])
            ->set('data.detalles.nueva.cantidad', 2)
            ->set('data.detalles.nueva.precio_unitario', 125)
            ->set('data.detalles.nueva.descuento', 25)
            ->assertSet('data.detalles.nueva.subtotal', 225.0)
            ->assertSet('data.detalles.nueva.total', 225.0);
    }

    public function test_factura_conserva_contado_al_elegir_cliente_y_respeta_un_cambio_manual(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Prueba', 'nombre_comercial' => 'Prueba', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $clientes = collect(['CLI-UNO', 'CLI-DOS'])->map(fn ($codigo) => Cliente::create([
            'codigo' => $codigo, 'nombre' => $codigo, 'empresa_id' => $empresa, 'condicion_pago' => 'parcial',
        ]));
        $test = Livewire::test(FormularioVentasReal::class, ['record' => new Factura, 'recurso' => FacturaResource::class])
            ->set('data.condicion_pago', 'contado')
            ->set('data.cliente_id', $clientes[0]->id)
            ->assertSet('data.condicion_pago', 'contado')
            ->set('data.condicion_pago', 'parcial')
            ->set('data.cliente_id', $clientes[1]->id)
            ->assertSet('data.condicion_pago', 'parcial');
    }

    public function test_selector_de_articulos_muestra_stock_y_su_estado_visual(): void
    {
        $this->actingAs(User::factory()->create());
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Prueba', 'nombre_comercial' => 'Prueba', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $almacen = Almacen::create(['codigo' => 'ALM-STOCK', 'nombre' => 'Principal', 'empresa_id' => $empresa, 'activo' => true]);
        $articulos = collect([['VERDE', 6], ['NARANJA', 5], ['ROJO', 1], ['SIN', 0]])->map(function (array $dato) use ($empresa, $almacen) {
            $articulo = Articulo::create(['codigo' => $dato[0], 'nombre_comercial' => $dato[0], 'empresa_id' => $empresa, 'inventariable' => true]);
            if ($dato[1] > 0) {
                Existencia::create(['articulo_id' => $articulo->id, 'almacen_id' => $almacen->id, 'cantidad_disponible' => $dato[1]]);
            }

            return $articulo;
        });
        $opciones = ArticuloSelectOptions::ventas();
        $etiquetaSeleccionada = ArticuloSelectOptions::label($articulos[0]->id);

        $this->assertStringContainsString('Stock: 6,00', $opciones[$articulos[0]->id]);
        $this->assertStringContainsString('text-success-600', $opciones[$articulos[0]->id]);
        $this->assertStringContainsString('Stock: 6,00', $etiquetaSeleccionada);
        $this->assertStringContainsString('Stock: 5,00', $opciones[$articulos[1]->id]);
        $this->assertStringContainsString('text-warning-600', $opciones[$articulos[1]->id]);
        $this->assertStringContainsString('Stock: 1 unidad', $opciones[$articulos[2]->id]);
        $this->assertStringContainsString('text-danger-600', $opciones[$articulos[2]->id]);
        $this->assertStringContainsString('Stock: Sin stock', $opciones[$articulos[3]->id]);
    }

}

class FormularioVentasReal extends Component implements HasForms
{
    use InteractsWithActions;
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

    public function form(Schema $schema): Schema
    {
        return $this->recurso::form($schema->model($this->record)->operation($this->record->exists ? 'edit' : 'create'))->statePath('data');
    }

    public function render(): string
    {
        return '<div>{{ $this->form }}</div>';
    }
}
