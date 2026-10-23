<?php

namespace Tests\Feature;

use App\Exports\StockPorSucursalExport;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\ListaPrecio;
use App\Models\Inventario\PrecioArticulo;
use App\Models\Sistema\Sucursal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockPorSucursalExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_exporta_productos_con_una_unidad_o_mas_con_costo_sucursal_y_precios_activos(): void
    {
        $empresaId = $this->crearEmpresa('Empresa stock');
        $otraEmpresaId = $this->crearEmpresa('Empresa externa');
        $sucursal = Sucursal::create(['empresa_id' => $empresaId, 'nombre' => 'Sucursal Centro', 'activo' => true]);
        $almacen = Almacen::create([
            'codigo' => 'EXP-STK-1',
            'nombre' => 'Almacén Centro',
            'empresa_id' => $empresaId,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);
        $almacenExterno = Almacen::create([
            'codigo' => 'EXP-STK-2',
            'nombre' => 'Almacén externo',
            'empresa_id' => $otraEmpresaId,
            'activo' => true,
        ]);
        $almacenStockFraccionario = Almacen::create([
            'codigo' => 'EXP-STK-3',
            'nombre' => 'Almacén secundario',
            'empresa_id' => $empresaId,
            'activo' => true,
        ]);

        $producto = Articulo::create([
            'codigo' => 'PROD-EXPORT',
            'nombre_comercial' => 'Producto de análisis',
            'empresa_id' => $empresaId,
            'inventariable' => true,
        ]);
        $servicio = Articulo::create([
            'codigo' => 'SERV-EXPORT',
            'nombre_comercial' => 'Servicio no inventariable',
            'empresa_id' => $empresaId,
            'inventariable' => false,
        ]);
        $productoExterno = Articulo::create([
            'codigo' => 'PROD-EXTERNO',
            'nombre_comercial' => 'Producto de otra empresa',
            'empresa_id' => $otraEmpresaId,
            'inventariable' => true,
        ]);

        Existencia::create([
            'articulo_id' => $producto->id,
            'almacen_id' => $almacen->id,
            'cantidad_disponible' => 3,
            'cantidad_comprometida' => 1,
            'costo_promedio' => 12.5,
            'costo_acumulado' => 37.5,
            'ultimo_costo' => 13,
        ]);
        Existencia::create(['articulo_id' => $producto->id, 'almacen_id' => $almacenStockFraccionario->id, 'cantidad_disponible' => 0.75]);
        Existencia::create(['articulo_id' => $servicio->id, 'almacen_id' => $almacen->id, 'cantidad_disponible' => 4]);
        Existencia::create(['articulo_id' => $productoExterno->id, 'almacen_id' => $almacenExterno->id, 'cantidad_disponible' => 8]);

        $listaNormal = ListaPrecio::create([
            'codigo' => 'EXP-LISTA-1',
            'nombre' => 'Minorista',
            'moneda' => 'BOB',
            'empresa_id' => $empresaId,
            'activo' => true,
        ]);
        $listaMayorista = ListaPrecio::create([
            'codigo' => 'EXP-LISTA-2',
            'nombre' => 'Mayorista',
            'moneda' => 'USD',
            'empresa_id' => $empresaId,
            'activo' => true,
        ]);
        $listaInactiva = ListaPrecio::create([
            'codigo' => 'EXP-LISTA-3',
            'nombre' => 'Lista vencida',
            'moneda' => 'BOB',
            'empresa_id' => $empresaId,
            'activo' => false,
        ]);
        PrecioArticulo::create(['articulo_id' => $producto->id, 'lista_precio_id' => $listaNormal->id, 'precio' => 20]);
        PrecioArticulo::create(['articulo_id' => $producto->id, 'lista_precio_id' => $listaMayorista->id, 'precio' => 18]);
        PrecioArticulo::create(['articulo_id' => $producto->id, 'lista_precio_id' => $listaInactiva->id, 'precio' => 15]);

        $export = new StockPorSucursalExport($empresaId);
        $filas = $export->query()->get();
        $fila = $export->map($filas->first());

        $this->assertCount(1, $filas);
        $this->assertSame('Empresa stock', $fila[0]);
        $this->assertSame('Sucursal Centro', $fila[1]);
        $this->assertSame('Almacén Centro', $fila[2]);
        $this->assertSame('PROD-EXPORT', $fila[3]);
        $this->assertSame(3.0, $fila[7]);
        $this->assertSame(2.0, $fila[9]);
        $this->assertSame(12.5, $fila[10]);
        $this->assertSame(37.5, $fila[12]);
        $this->assertStringContainsString('Minorista: 20.00 BOB', $fila[13]);
        $this->assertStringContainsString('Mayorista: 18.00 USD', $fila[13]);
        $this->assertStringNotContainsString('Lista vencida', $fila[13]);
    }

    private function crearEmpresa(string $nombre): int
    {
        return DB::table('conf_empresas')->insertGetId([
            'razon_social' => $nombre,
            'nombre_comercial' => $nombre,
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);
    }
}
