<?php

namespace Tests\Feature;

use App\Filament\Resources\Inventario\TransferenciaAlmacenResource;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages\ListTransferenciaAlmacens;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\TransferenciaAlmacen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TransferenciaAlmacenSolicitudTest extends TestCase
{
    use RefreshDatabase;

    private Almacen $origen;

    private Almacen $destino;

    private Articulo $articulo;

    protected function setUp(): void
    {
        parent::setUp();
        Auth::logout();
        Role::findOrCreate('admin', 'web');

        $empresaId = DB::table('conf_empresas')->insertGetId([
            'razon_social' => 'Empresa traspasos',
            'nombre_comercial' => 'Empresa traspasos',
            'pais' => 'Bolivia',
            'empresa_activo' => true,
        ]);

        $this->origen = Almacen::create(['codigo' => 'TR-ORI', 'nombre' => 'Origen', 'empresa_id' => $empresaId, 'activo' => true]);
        $this->destino = Almacen::create(['codigo' => 'TR-DES', 'nombre' => 'Destino', 'empresa_id' => $empresaId, 'activo' => true]);
        $this->articulo = Articulo::create([
            'codigo' => 'TR-ART',
            'nombre_comercial' => 'Artículo para traspaso',
            'empresa_id' => $empresaId,
            'inventariable' => true,
            'metodo_costo' => 'promedio',
        ]);

        Existencia::create([
            'articulo_id' => $this->articulo->id,
            'almacen_id' => $this->origen->id,
            'cantidad_disponible' => 5,
            'costo_promedio' => 10,
            'costo_acumulado' => 50,
            'ultimo_costo' => 10,
        ]);
    }

    public function test_codigo_usa_prefijo_anual_y_siguiente_correlativo_de_cuatro_digitos(): void
    {
        $prefijo = 'TRA-'.now()->format('y');
        $this->assertSame($prefijo.'0001', TransferenciaAlmacen::generarCodigo());

        $responsable = $this->crearAdministrador();
        TransferenciaAlmacen::create([
            'codigo' => $prefijo.'0009',
            'almacen_origen_id' => $this->origen->id,
            'almacen_destino_id' => $this->destino->id,
            'receptor_id' => $responsable->id,
            'creado_por' => $responsable->id,
            'estado' => 'en_transito',
        ]);

        TransferenciaAlmacen::create([
            'codigo' => 'TRA-'.now()->subYear()->format('y').'9999',
            'almacen_origen_id' => $this->origen->id,
            'almacen_destino_id' => $this->destino->id,
            'receptor_id' => $responsable->id,
            'creado_por' => $responsable->id,
            'estado' => 'en_transito',
        ]);

        $this->assertSame($prefijo.'0010', TransferenciaAlmacen::generarCodigo());
    }

    public function test_aceptar_mueve_stock_y_registra_ambos_movimientos_en_kardex(): void
    {
        $receptor = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor);

        $this->assertSame('en_transito', $traspaso->estado);
        $this->assertSame(5.0, (float) Existencia::where('almacen_id', $this->origen->id)->value('cantidad_disponible'));
        $this->assertSame(0, Kardex::where('documento_tipo', 'transferencia_almacen')->count());

        $this->actingAs($receptor);
        $traspaso->recibir('Revisado físicamente al recibir.');

        $this->assertSame('recibida', $traspaso->fresh()->estado);
        $this->assertSame('Revisado físicamente al recibir.', $traspaso->fresh()->observaciones_resolucion);
        $this->assertSame(3.0, (float) Existencia::where('almacen_id', $this->origen->id)->value('cantidad_disponible'));
        $this->assertSame(2.0, (float) Existencia::where('almacen_id', $this->destino->id)->value('cantidad_disponible'));
        $this->assertSame(2, Kardex::where('documento_tipo', 'transferencia_almacen')->count());
    }

    public function test_rechazar_exige_motivo_y_no_mueve_stock(): void
    {
        $receptor = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor);
        $this->actingAs($receptor);

        $traspaso->rechazar('Cantidad solicitada no corresponde', 'El origen debe corregir la cantidad antes de reenviar.');

        $this->assertSame('rechazada', $traspaso->fresh()->estado);
        $this->assertSame('Cantidad solicitada no corresponde', $traspaso->fresh()->motivo_rechazo);
        $this->assertSame('El origen debe corregir la cantidad antes de reenviar.', $traspaso->fresh()->observaciones_resolucion);
        $this->assertSame(5.0, (float) Existencia::where('almacen_id', $this->origen->id)->value('cantidad_disponible'));
        $this->assertSame(0, Kardex::where('documento_tipo', 'transferencia_almacen')->count());
    }

    public function test_stock_insuficiente_impide_aceptar_sin_dejar_movimientos_parciales(): void
    {
        $receptor = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor);
        $traspaso->detalles()->update(['cantidad' => 8]);
        $this->actingAs($receptor);

        try {
            $traspaso->recibir();
            $this->fail('No debe aceptar un traspaso con más unidades que el stock disponible.');
        } catch (\Exception $exception) {
            $this->assertSame('Stock insuficiente para la salida', $exception->getMessage());
        }

        $this->assertSame('en_transito', $traspaso->fresh()->estado);
        $this->assertSame(5.0, (float) Existencia::where('almacen_id', $this->origen->id)->value('cantidad_disponible'));
        $this->assertSame(0.0, (float) (Existencia::where('almacen_id', $this->destino->id)->value('cantidad_disponible') ?? 0));
        $this->assertSame(0, Kardex::where('documento_tipo', 'transferencia_almacen')->count());
    }

    public function test_anular_solicitud_pendiente_conserva_existencias_y_no_elimina_el_registro(): void
    {
        $creador = $this->crearAdministrador();
        $receptor = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor, $creador);
        $this->actingAs($creador);

        $traspaso->anular();

        $this->assertSame('borrador', $traspaso->fresh()->estado);
        $this->assertDatabaseHas('alm_transferencias_almacenes', ['id' => $traspaso->id, 'estado' => 'borrador']);
        $this->assertSame(5.0, (float) Existencia::where('almacen_id', $this->origen->id)->value('cantidad_disponible'));
        $this->assertSame(0, Kardex::where('documento_tipo', 'transferencia_almacen')->count());
    }

    public function test_solo_el_receptor_asignado_puede_aceptar_o_rechazar(): void
    {
        $receptor = $this->crearAdministrador();
        $otroUsuario = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor);
        $this->actingAs($otroUsuario);

        try {
            $traspaso->recibir();
            $this->fail('Un usuario que no es receptor no debe aceptar el traspaso.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('persona responsable del almacén de destino', $exception->getMessage());
        }

        try {
            $traspaso->rechazar('Motivo de prueba');
            $this->fail('Un usuario que no es receptor no debe rechazar el traspaso.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('persona responsable del almacén de destino', $exception->getMessage());
        }

        $this->assertSame('en_transito', $traspaso->fresh()->estado);
        $this->assertSame(5.0, (float) Existencia::where('almacen_id', $this->origen->id)->value('cantidad_disponible'));
    }

    public function test_solo_quien_creo_la_solicitud_puede_anularla(): void
    {
        $creador = $this->crearAdministrador();
        $receptor = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor, $creador);

        $this->actingAs($receptor);
        $this->assertFalse(TransferenciaAlmacenResource::canDelete($traspaso));

        try {
            $traspaso->anular();
            $this->fail('El receptor no debe anular una solicitud creada por otra persona.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('usuario que creó', $exception->getMessage());
        }

        $this->actingAs($creador);
        $this->assertTrue(TransferenciaAlmacenResource::canDelete($traspaso));
        $traspaso->anular();

        $this->assertSame('borrador', $traspaso->fresh()->estado);
    }

    public function test_la_vista_muestra_detalle_y_botones_de_aceptar_y_rechazar(): void
    {
        $receptor = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor);
        $this->actingAs($receptor);

        Livewire::test(ListTransferenciaAlmacens::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$traspaso])
            ->assertTableActionExists('view', record: $traspaso)
            ->mountTableAction('view', $traspaso)
            ->assertSet('mountedActions.0.name', 'view')
            ->assertMountedActionModalSee([
                'Artículos solicitados',
                'TR-ART',
                'Aceptar traspaso',
                'Rechazar traspaso',
            ]);

        Livewire::test(ListTransferenciaAlmacens::class)
            ->mountTableAction('view', $traspaso)
            ->callAction('aceptar_desde_vista', data: ['observaciones' => 'Revisión completada.'])
            ->assertHasNoActionErrors();
    }

    public function test_otros_usuarios_pueden_ver_documento_sin_poder_aceptar_o_rechazar(): void
    {
        $receptor = $this->crearAdministrador();
        $creador = $this->crearAdministrador();
        $traspaso = $this->crearSolicitud($receptor, $creador);
        $otroUsuario = $this->crearAdministrador();
        $this->actingAs($otroUsuario);

        Livewire::test(ListTransferenciaAlmacens::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$traspaso])
            ->assertTableActionExists('view', record: $traspaso)
            ->mountTableAction('view', $traspaso)
            ->assertMountedActionModalSee([
                'TR-ART',
                'Artículos solicitados',
            ])
            ->assertMountedActionModalDontSee([
                'Aceptar traspaso',
                'Rechazar traspaso',
            ]);

        $this->assertFalse(TransferenciaAlmacenResource::canDelete($traspaso));
        $this->assertSame('en_transito', $traspaso->fresh()->estado);
    }

    public function test_filtro_estado_inicia_en_pendientes_y_permite_consultar_traspasos_recibidos(): void
    {
        $receptor = $this->crearAdministrador();
        $pendiente = $this->crearSolicitud($receptor);
        $recibido = $this->crearSolicitud($receptor);
        $recibido->update([
            'estado' => 'recibida',
            'recibido_por' => $receptor->id,
            'fecha_recepcion' => now(),
        ]);
        $this->actingAs($receptor);

        Livewire::test(ListTransferenciaAlmacens::class)
            ->assertTableFilterExists('estado')
            ->assertCanSeeTableRecords([$pendiente])
            ->assertCanNotSeeTableRecords([$recibido])
            ->filterTable('estado', 'recibida')
            ->assertCanSeeTableRecords([$recibido])
            ->assertCanNotSeeTableRecords([$pendiente]);
    }

    private function crearSolicitud(User $receptor, ?User $creador = null): TransferenciaAlmacen
    {
        $traspaso = TransferenciaAlmacen::create([
            'almacen_origen_id' => $this->origen->id,
            'almacen_destino_id' => $this->destino->id,
            'receptor_id' => $receptor->id,
            'creado_por' => ($creador ?? $receptor)->id,
            'estado' => 'en_transito',
        ]);

        $traspaso->detalles()->create([
            'articulo_id' => $this->articulo->id,
            'cantidad' => 2,
        ]);

        return $traspaso;
    }

    private function crearAdministrador(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
