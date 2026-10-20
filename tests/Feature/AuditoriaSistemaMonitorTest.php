<?php

namespace Tests\Feature;

use App\Filament\Clusters\Sistema\Resources\AuditoriaResource;
use App\Filament\Clusters\Sistema\Resources\AuditoriaResource\Pages\ListAuditorias;
use App\Http\Middleware\MonitorSistemaMiddleware;
use App\Models\User;
use App\Services\Sistema\AuditoriaSistemaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditoriaSistemaMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_creacion_actualizacion_y_eliminacion_de_modelos_redactando_secretos(): void
    {
        $actor = User::factory()->create();
        $this->actingAs($actor);

        $usuario = User::factory()->create(['password' => 'clave-privada-prueba']);
        $usuario->update(['name' => 'Nombre actualizado']);
        $usuario->delete();

        $eventos = DB::table('sys_eventos_auditoria')
            ->where('modelo', User::class)
            ->where('registro_id', (string) $usuario->id)
            ->orderBy('id')
            ->get();

        $this->assertSame(['creado', 'actualizado', 'eliminado'], $eventos->pluck('tipo_evento')->all());
        $this->assertSame($actor->id, $eventos->first()->usuario_id);
        $this->assertStringContainsString('[REDACTADO]', $eventos->first()->datos);
        $this->assertStringNotContainsString('clave-privada-prueba', $eventos->first()->datos);
        $this->assertStringContainsString('Nombre actualizado', $eventos[1]->datos);
    }

    public function test_registra_respuestas_http_de_error_con_severidad_y_codigo(): void
    {
        $request = Request::create('/ruta-inexistente', 'GET');
        $middleware = app(MonitorSistemaMiddleware::class);

        $middleware->handle($request, fn () => new Response('No encontrado', 404));
        $middleware->handle($request, fn () => new Response('Error interno', 500));

        $this->assertDatabaseHas('sys_eventos_auditoria', [
            'tipo_evento' => 'error_http',
            'codigo_http' => 404,
            'nivel' => 'advertencia',
        ]);
        $this->assertDatabaseHas('sys_eventos_auditoria', [
            'tipo_evento' => 'error_http',
            'codigo_http' => 500,
            'nivel' => 'critico',
        ]);
    }

    public function test_registra_excepciones_sin_guardar_su_mensaje_potencialmente_sensible(): void
    {
        $exception = new \RuntimeException('Token privado: no guardar este texto');
        app(AuditoriaSistemaService::class)->registrarExcepcion($exception, Request::create('/fallo', 'POST'));

        $evento = DB::table('sys_eventos_auditoria')->where('tipo_evento', 'error_sistema')->first();

        $this->assertNotNull($evento);
        $this->assertSame(500, $evento->codigo_http);
        $this->assertSame('critico', $evento->nivel);
        $this->assertSame(\RuntimeException::class, $evento->clase_excepcion);
        $this->assertStringNotContainsString('Token privado', json_encode($evento));
    }

    public function test_resource_de_auditoria_es_solo_lectura_y_restringido_a_administradores(): void
    {
        Role::findOrCreate('admin', 'web');
        $administrador = User::factory()->create();
        $administrador->assignRole('admin');
        $this->actingAs($administrador);

        $evento = DB::table('sys_eventos_auditoria')->insertGetId([
            'tipo_evento' => 'actualizado',
            'nivel' => 'informativo',
            'tabla' => 'articulos',
            'registro_id' => '15',
            'usuario_id' => $administrador->id,
            'resumen' => 'Actualización registrada',
            'created_at' => now(),
        ]);

        Livewire::test(ListAuditorias::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([\App\Models\Sistema\AuditoriaSistema::findOrFail($evento)])
            ->assertTableActionExists('view', record: \App\Models\Sistema\AuditoriaSistema::findOrFail($evento));

        $this->assertTrue(AuditoriaResource::canViewAny());
        $this->assertFalse(AuditoriaResource::canCreate());
        $this->assertFalse(AuditoriaResource::canEdit(\App\Models\Sistema\AuditoriaSistema::findOrFail($evento)));
        $this->assertFalse(AuditoriaResource::canDelete(\App\Models\Sistema\AuditoriaSistema::findOrFail($evento)));

        $usuario = User::factory()->create();
        $this->actingAs($usuario);
        $this->assertFalse(AuditoriaResource::canViewAny());
    }
}
