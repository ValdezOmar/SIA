<?php

namespace Tests\Feature;

use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialLaboral;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Filament\Resources\Almacen\InventarioHistoricoResource\Pages\ListInventariosHistoricos;
use App\Filament\Resources\Almacen\InventarioResource\RelationManagers\EventosRelationManager;
use LogicException;
use Illuminate\Support\Facades\Storage;
use App\Services\Inventario\InventarioPdfService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Filament\Resources\Almacen\InventarioResource;
use App\Filament\Resources\Almacen\InventarioResource\Pages\CreateInventario;
use App\Filament\Resources\Almacen\InventarioResource\Pages\ListInventarios;
use App\Filament\Resources\Almacen\InventarioResource\Pages\ViewInventario;
use App\Filament\Resources\Almacen\InventarioResource\RelationManagers\ConteosRelationManager;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use App\Models\Inventario\InventarioFisico;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use App\Services\Inventario\InventarioFisicoService as Servicio;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventarioFisicoTest extends TestCase
{
    use RefreshDatabase;

    private Servicio $servicio;

    private Almacen $almacen;

    private Articulo $articulo;

    private User $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = User::factory()->create();
        foreach ([Servicio::VER, Servicio::PROGRAMAR, Servicio::CONTAR] as $permiso) {
            Permission::findOrCreate($permiso, 'web');
            $this->usuario->givePermissionTo($permiso);
        }
        Auth::login($this->usuario);
        Filament::setCurrentPanel(Filament::getPanel('dashboard'));
        $empresa = DB::table('conf_empresas')->insertGetId(['razon_social' => 'Empresa inventario', 'nombre_comercial' => 'Empresa inventario', 'pais' => 'Bolivia', 'empresa_activo' => true]);
        $sucursal = Sucursal::create(['empresa_id' => $empresa, 'nombre' => 'Central', 'activo' => true]);
        $this->almacen = Almacen::create(['codigo' => 'ALM-TEST', 'nombre' => 'Principal', 'empresa_id' => $empresa, 'sucursal_id' => $sucursal->id, 'activo' => true]);
        $this->articulo = Articulo::create(['codigo' => 'ART-01', 'nombre_comercial' => 'Equipo prueba', 'empresa_id' => $empresa]);
        Existencia::create(['articulo_id' => $this->articulo->id, 'almacen_id' => $this->almacen->id, 'cantidad_disponible' => 5.5, 'cantidad_comprometida' => 2]);
        $this->servicio = app(Servicio::class);
    }

    private function programar(): InventarioFisico
    {
        return $this->servicio->programar([
            'empresa_id' => $this->almacen->empresa_id, 'sucursal_id' => $this->almacen->sucursal_id,
            'almacen_id' => $this->almacen->id, 'responsable_id' => $this->usuario->id, 'fecha_programada' => today()->toDateString(),
        ]);
    }

    public function test_ciclo_completo_con_cero_diferencias_auditoria_e_historial(): void
    {
        $sinStock = Articulo::create(['codigo' => 'ART-02', 'nombre_comercial' => 'Sin stock', 'empresa_id' => $this->almacen->empresa_id]);
        $inv = $this->programar();
        $this->assertSame(0, $inv->conteos()->count());
        $this->servicio->iniciar($inv);
        $linea = $inv->conteos()->where('articulo_id', $this->articulo->id)->firstOrFail();
        $this->assertSame('5.500000', $linea->stock_sistema);
        $this->assertSame('2.000000', $linea->stock_reservado);
        $this->servicio->contar($linea, ['cantidad_contada' => 4.5, 'version' => 0, 'observaciones' => 'Falta una unidad']);
        $this->assertSame(50.0, InventarioFisico::conProgreso()->find($inv->id)->progreso);
        $this->servicio->contar($inv->conteos()->where('articulo_id', $sinStock->id)->first(), ['cantidad_contada' => 0, 'version' => 0]);
        $this->assertSame(100.0, InventarioFisico::conProgreso()->find($inv->id)->progreso);
        $this->servicio->enviarRevision($inv);
        foreach ($inv->conteos as $conteo) {
            $this->servicio->revisar($conteo, 'Verificado físicamente. Diferencias pendientes de ajuste por Kardex.');
        }
        $this->servicio->finalizar($inv, 'cerrar', 'Auditoría concluida');
        $this->assertSame('cerrado', $inv->fresh()->estado);
        $this->assertSame('5.500000', Existencia::first()->cantidad_disponible);
        $this->assertSame(8, $inv->eventos()->count());
        $nuevo = $this->programar();
        $this->assertNotSame($inv->id, $nuevo->id);
        $this->assertSame(2, $inv->conteos()->count());
    }

    public function test_rechaza_programacion_duplicada_del_mismo_almacen(): void
    {
        $this->programar();
        $this->expectException(ValidationException::class);
        $this->programar();
    }

    public function test_no_envia_conteos_incompletos_a_revision(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $this->expectException(ValidationException::class);
        $this->servicio->enviarRevision($inv);
    }

    public function test_no_cierra_sin_revision(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $this->servicio->contar($inv->conteos()->first(), ['cantidad_contada' => 5.5, 'version' => 0]);
        $this->servicio->enviarRevision($inv);
        $this->expectException(ValidationException::class);
        $this->servicio->finalizar($inv, 'cerrar', 'Cierre');
    }

    public function test_reconteo_con_motivo_conserva_antes_y_despues_y_rechaza_version_antigua(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $linea = $inv->conteos()->first();
        $this->servicio->contar($linea, ['cantidad_contada' => 5.5, 'version' => 0]);
        $this->servicio->contar($linea, ['cantidad_contada' => 6, 'version' => 1, 'motivo' => 'Segundo conteo', 'observaciones' => 'Sobrante']);
        $evento = $inv->eventos()->where('accion', 'reconteo')->firstOrFail();
        $this->assertSame('5.500000', $evento->antes['cantidad_contada']);
        $this->assertSame('6.000000', $evento->despues['cantidad_contada']);
        $this->expectException(ValidationException::class);
        $this->servicio->contar($linea, ['cantidad_contada' => 5.5, 'version' => 1, 'motivo' => 'Formulario antiguo']);
    }

    public function test_escaner_resuelve_codigo_barras_y_serie_del_almacen(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $this->articulo->codigosBarras()->create(['codigo_barras' => 'QR-PRUEBA', 'tipo' => 'QR']);
        $this->assertSame($this->articulo->id, $this->servicio->buscarCodigo($inv, 'QR-PRUEBA')->articulo_id);
        $this->assertSame($this->articulo->id, $this->servicio->buscarCodigo($inv, 'ART-01')->articulo_id);
        $this->expectException(ValidationException::class);
        $this->servicio->buscarCodigo($inv, 'NO-EXISTE');
    }

    public function test_pantallas_y_escaner_abren_formulario_de_conteo(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        Livewire::test(ListInventarios::class)->assertSuccessful()->assertCanSeeTableRecords([$inv]);
        Livewire::test(CreateInventario::class)->assertSuccessful();
        Livewire::test(ViewInventario::class, ['record' => $inv->id])->assertSuccessful()->assertSee($inv->codigo);
        Livewire::test(ConteosRelationManager::class, ['ownerRecord' => $inv->fresh(), 'pageClass' => ViewInventario::class])
            ->callTableAction('escanear', data: ['codigo' => 'ART-01'])
            ->assertHasNoTableActionErrors()
            ->assertSet('mountedTableActions', ['contar'])
            ->setTableActionData(['cantidad_contada' => 5.5])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();
        $this->assertSame('5.500000', $inv->conteos()->first()->cantidad_contada);
        $this->assertSame('ART-01', $inv->conteos()->first()->codigo_leido);
    }

    public function test_cada_almacen_tiene_su_propia_referencia_y_no_se_alteran_los_saldos(): void
    {
        $otro = Almacen::create(['codigo' => 'ALM-OTRO', 'nombre' => 'Otro', 'empresa_id' => $this->almacen->empresa_id, 'sucursal_id' => $this->almacen->sucursal_id, 'activo' => true]);
        Existencia::create(['articulo_id' => $this->articulo->id, 'almacen_id' => $otro->id, 'cantidad_disponible' => 99]);
        $primero = $this->programar();
        $this->almacen = $otro;
        $segundo = $this->programar();
        $this->servicio->iniciar($segundo);
        $this->assertSame('99.000000', $segundo->conteos()->first()->stock_sistema);
        $this->assertSame('programado', $primero->fresh()->estado);
    }

    public function test_usuario_de_otra_sucursal_no_puede_contar_ni_ver_la_sesion(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $this->usuario->setRelation('empleado', (new Empleado)->setRelation('historialActivo',
            (new HistorialLaboral)->forceFill(['empresa_id' => $this->almacen->empresa_id, 'sucursal_id' => 999999])));
        $this->assertFalse(InventarioResource::getEloquentQuery()->whereKey($inv->id)->exists());
        $this->expectException(HttpException::class);
        $this->servicio->contar($inv->conteos()->first(), ['cantidad_contada' => 5.5, 'version' => 0]);
    }

    public function test_usuario_sin_permiso_no_puede_programar(): void
    {
        $this->usuario->revokePermissionTo(Servicio::PROGRAMAR);
        $this->expectException(HttpException::class);
        $this->programar();
    }

    public function test_no_se_modifica_un_inventario_cancelado(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $this->servicio->finalizar($inv, 'cancelar', 'Cambio de programación');
        $this->expectException(ValidationException::class);
        $this->servicio->contar($inv->conteos()->first(), ['cantidad_contada' => 5.5, 'version' => 0]);
    }

    public function test_devolver_a_conteo_invalida_revision_y_conserva_la_bitacora(): void
    {
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $linea = $inv->conteos()->first();
        $this->servicio->contar($linea, ['cantidad_contada' => 5.5, 'version' => 0]);
        $this->servicio->enviarRevision($inv);
        $this->servicio->revisar($linea, 'Revisado');
        $this->servicio->finalizar($inv, 'devolver', 'Verificar de nuevo');
        $this->assertNull($linea->fresh()->revisado_at);
        $this->assertSame(1, $inv->eventos()->where('accion', 'revisado')->count());
        $this->assertSame('en_conteo', $inv->fresh()->estado);
    }

    public function test_programacion_desde_formulario_y_exportacion(): void
    {
        Livewire::test(CreateInventario::class)->fillForm([
            'empresa_id' => $this->almacen->empresa_id, 'sucursal_id' => $this->almacen->sucursal_id,
            'almacen_id' => $this->almacen->id, 'responsable_id' => $this->usuario->id, 'fecha_programada' => today()->toDateString(),
        ])->call('create')->assertHasNoFormErrors();
        $inv = InventarioFisico::firstOrFail();
        Livewire::test(ViewInventario::class, ['record' => $inv->id])->callAction('iniciar')->assertHasNoActionErrors();
        $this->assertSame('en_conteo', $inv->fresh()->estado);
        Livewire::test(ViewInventario::class, ['record' => $inv->id])->callAction('exportar')->assertFileDownloaded($inv->codigo.'.csv');
        Livewire::test(ListInventariosHistoricos::class)->assertSuccessful();
        Livewire::test(EventosRelationManager::class,
            ['ownerRecord' => $inv->fresh(), 'pageClass' => ViewInventario::class])->assertSuccessful();
    }

    public function test_bitacora_no_se_puede_editar(): void
    {
        $inv = $this->programar();
        $this->expectException(LogicException::class);
        $inv->eventos()->first()->update(['motivo' => 'Modificado']);
    }

    public function test_pdf_con_logo_conteos_y_bitacora_se_descarga_desde_la_ficha_y_el_listado(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('empresas/logos/prueba.png', file_get_contents(public_path('images/logo.png')));
        $this->almacen->empresa->update(['logo_path' => 'empresas/logos/prueba.png', 'nit' => '123456789', 'direccion' => 'Av. de prueba 123', 'ciudad' => 'La Paz']);
        for ($i = 2; $i <= 30; $i++) {
            Articulo::create(['codigo' => sprintf('ART-%02d', $i), 'nombre_comercial' => 'Equipo de laboratorio con descripción de prueba '.$i, 'empresa_id' => $this->almacen->empresa_id]);
        }
        $inv = $this->programar();
        $this->servicio->iniciar($inv);
        $this->servicio->contar($inv->conteos()->first(), ['cantidad_contada' => 4.123456, 'version' => 0, 'observaciones' => 'Diferencia pendiente de revisión. Texto con tildes y símbolos: á é í ó ú ñ.']);
        $servicio = app(InventarioPdfService::class);
        $this->assertStringStartsWith('data:image/png;base64,', $servicio->logoDataUri($this->almacen->empresa->fresh()));
        $pdf = $servicio->generar($inv);
        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringContainsString('/Subtype /Image', $pdf);
        Livewire::test(ViewInventario::class, ['record' => $inv->id])->callAction('exportarPdf')->assertFileDownloaded($inv->codigo.'.pdf');
        Livewire::test(ListInventarios::class)->callTableAction('exportarPdf', $inv)->assertFileDownloaded($inv->codigo.'.pdf');
        if ($preview = getenv('SIA_PDF_PREVIEW')) {
            file_put_contents($preview, $pdf);
        }
    }

    public function test_pdf_no_exporta_inventario_de_otra_sucursal(): void
    {
        $inv = $this->programar();
        $this->usuario->setRelation('empleado', (new Empleado)->setRelation('historialActivo',
            (new HistorialLaboral)->forceFill(['empresa_id' => $this->almacen->empresa_id, 'sucursal_id' => 999999])));
        $this->expectException(ModelNotFoundException::class);
        app(InventarioPdfService::class)->generar($inv);
    }
}
