<?php

namespace Tests\Feature;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialLaboral;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockMinimoNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_avisa_a_los_usuarios_activos_de_la_sucursal_al_cruzar_el_minimo_sin_repetir_mientras_sigue_bajo(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        [$empresaId, $sucursalId, $otraSucursalId] = $this->crearEmpresaYSucursales();
        $miembroUno = $this->crearUsuarioDeSucursal($sucursalId, 'uno');
        $miembroDos = $this->crearUsuarioDeSucursal($sucursalId, 'dos');
        $miembroOtraSucursal = $this->crearUsuarioDeSucursal($otraSucursalId, 'externo');
        $existencia = $this->crearExistencia($empresaId, $sucursalId, 6, 0, 5);

        $this->assertDatabaseCount('notifications', 0);

        $existencia->cantidad_disponible = 5;
        $existencia->save();

        $this->assertDatabaseCount('notifications', 2);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $miembroUno->getMorphClass(),
            'notifiable_id' => $miembroUno->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $miembroDos->getMorphClass(),
            'notifiable_id' => $miembroDos->id,
        ]);
        $notificacion = DB::table('notifications')
            ->where('notifiable_id', $miembroUno->id)
            ->first();
        $datosNotificacion = json_decode($notificacion->data, true);
        $this->assertStringContainsString('Poco stock', $datosNotificacion['title']);
        $this->assertStringContainsString('Disponible: 5 unidades; mínimo configurado: 5', $datosNotificacion['body']);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => $miembroOtraSucursal->getMorphClass(),
            'notifiable_id' => $miembroOtraSucursal->id,
        ]);

        $existencia->cantidad_disponible = 3;
        $existencia->save();
        $this->assertDatabaseCount('notifications', 2);

        $existencia->cantidad_disponible = 8;
        $existencia->save();
        $existencia->cantidad_disponible = 5;
        $existencia->save();

        $this->assertDatabaseCount('notifications', 4);
    }

    public function test_avisa_si_se_configura_un_minimo_que_el_stock_ya_alcanzo(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        [$empresaId, $sucursalId] = $this->crearEmpresaYSucursales();
        $miembro = $this->crearUsuarioDeSucursal($sucursalId, 'minimo');
        $existencia = $this->crearExistencia($empresaId, $sucursalId, 4, 0, 0);

        $existencia->cantidad_minima = 5;
        $existencia->save();

        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => $miembro->getMorphClass(),
            'notifiable_id' => $miembro->id,
        ]);
    }

    private function crearEmpresaYSucursales(): array
    {
        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa alertas stock',
            'nombre_comercial' => 'Empresa alertas stock',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);
        $sucursal = Sucursal::create(['empresa_id' => $empresaId, 'nombre' => 'Sucursal principal', 'activo' => true]);
        $otraSucursal = Sucursal::create(['empresa_id' => $empresaId, 'nombre' => 'Sucursal externa', 'activo' => true]);

        return [$empresaId, $sucursal->id, $otraSucursal->id];
    }

    private function crearUsuarioDeSucursal(int $sucursalId, string $identificador): User
    {
        $email = "stock.{$identificador}@example.test";
        $usuario = User::factory()->create(['email' => $email]);
        $empleado = Empleado::create([
            'nombres' => "Miembro {$identificador}",
            'apellidos' => 'Sucursal',
            'ci' => "STK-{$identificador}",
            'activo' => true,
        ]);

        HistorialLaboral::create([
            'empleado_id' => $empleado->id,
            'empresa_id' => Sucursal::query()->findOrFail($sucursalId)->empresa_id,
            'sucursal_id' => $sucursalId,
            'correo_corporativo' => $email,
            'activo' => true,
        ]);

        return $usuario;
    }

    private function crearExistencia(int $empresaId, int $sucursalId, float $disponible, float $comprometido, float $minimo): Existencia
    {
        $almacen = Almacen::create([
            'codigo' => 'STK-ALM-'.uniqid(),
            'nombre' => 'Almacén sucursal',
            'empresa_id' => $empresaId,
            'sucursal_id' => $sucursalId,
            'activo' => true,
        ]);
        $articulo = Articulo::create([
            'codigo' => 'STK-ART-'.uniqid(),
            'nombre_comercial' => 'Artículo de prueba',
            'empresa_id' => $empresaId,
            'inventariable' => true,
        ]);

        return Existencia::create([
            'articulo_id' => $articulo->id,
            'almacen_id' => $almacen->id,
            'cantidad_disponible' => $disponible,
            'cantidad_comprometida' => $comprometido,
            'cantidad_minima' => $minimo,
        ]);
    }
}
