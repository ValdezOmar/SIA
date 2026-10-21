<?php

namespace Tests\Feature;

use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
use App\Filament\Resources\Inventario\StockAlmacenResource;
use App\Models\Inventario\Almacen;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialLaboral;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockAlmacenVisibilidadTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_ve_todos_los_almacenes_de_su_empresa_sin_ampliar_a_otras_empresas(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));

        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa visible',
            'nombre_comercial' => 'Empresa visible',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);
        $otraEmpresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Otra empresa',
            'nombre_comercial' => 'Otra empresa',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);
        $sucursalUsuario = Sucursal::create(['empresa_id' => $empresaId, 'nombre' => 'Sucursal del usuario', 'activo' => true]);
        $sucursalEmpresa = Sucursal::create(['empresa_id' => $empresaId, 'nombre' => 'Otra sucursal', 'activo' => true]);
        $sucursalOtraEmpresa = Sucursal::create(['empresa_id' => $otraEmpresaId, 'nombre' => 'Sucursal externa', 'activo' => true]);

        $email = 'stock.usuario@example.test';
        $usuario = User::factory()->create(['email' => $email]);
        $empleado = Empleado::create([
            'nombres' => 'Usuario',
            'apellidos' => 'Stock',
            'ci' => 'STOCK-001',
            'activo' => true,
        ]);
        HistorialLaboral::create([
            'empleado_id' => $empleado->id,
            'empresa_id' => $empresaId,
            'sucursal_id' => $sucursalUsuario->id,
            'correo_corporativo' => $email,
            'activo' => true,
        ]);

        $this->assertSame($empresaId, $usuario->fresh()->empresa_id);

        $local = Almacen::create(['codigo' => 'STK-LOCAL', 'nombre' => 'Almacén local', 'empresa_id' => $empresaId, 'sucursal_id' => $sucursalUsuario->id, 'activo' => true]);
        $otraSucursal = Almacen::create(['codigo' => 'STK-OTRA', 'nombre' => 'Almacén otra sucursal', 'empresa_id' => $empresaId, 'sucursal_id' => $sucursalEmpresa->id, 'activo' => true]);
        $sinSucursal = Almacen::create(['codigo' => 'STK-CENTRAL', 'nombre' => 'Almacén central', 'empresa_id' => $empresaId, 'activo' => true]);
        $externo = Almacen::create(['codigo' => 'STK-EXTERNO', 'nombre' => 'Almacén externo', 'empresa_id' => $otraEmpresaId, 'sucursal_id' => $sucursalOtraEmpresa->id, 'activo' => true]);

        Auth::login($usuario);

        $visiblesEnStock = StockAlmacenResource::getEloquentQuery()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$local->id, $otraSucursal->id, $sinSucursal->id], $visiblesEnStock);
        $this->assertNotContains($externo->id, $visiblesEnStock);
        $this->assertEqualsCanonicalizing([$local->id, $sinSucursal->id], AlmacenResource::getEloquentQuery()->pluck('id')->all());
    }
}
