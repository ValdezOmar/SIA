<?php

namespace Tests\Feature;

use App\Models\Sistema\Auditoria;
use App\Models\User;
use App\Policies\Sistema\AuditoriaPolicy;
use App\Services\Sistema\AuditoriaService;
use App\Support\LegacyShieldPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    public function test_captura_alta_eloquent_y_escritura_sql_sin_guardar_sql_crudo(): void
    {
        $usuario = User::factory()->create();

        $altaModelo = Auditoria::query()
            ->where('evento', 'modelo.created')
            ->where('entidad', User::class)
            ->latest('id')
            ->firstOrFail();
        $this->assertSame('info', $altaModelo->nivel);
        $this->assertSame('[REDACTADO]', $altaModelo->despues['email']);
        $this->assertSame('[REDACTADO]', $altaModelo->despues['password']);

        DB::table('users')->where('id', $usuario->id)->update(['name' => 'Nombre de prueba actualizado']);

        $sql = Auditoria::query()->where('evento', 'sql.update')->where('tabla', 'users')->latest('id')->firstOrFail();
        $this->assertSame('Escritura update en users', $sql->descripcion);
        $this->assertStringNotContainsString('Nombre de prueba actualizado', json_encode($sql->contexto));
        $this->assertStringNotContainsString('UPDATE ', json_encode($sql->contexto));
    }

    public function test_saneamiento_conserva_montos_y_redacta_datos_personales(): void
    {
        app(AuditoriaService::class)->registrar([
            'evento' => 'prueba.saneamiento',
            'descripcion' => 'Prueba interna de saneamiento',
            'antes' => ['monto' => 125.50, 'email' => 'privado@example.com'],
            'despues' => ['monto' => 100.00, 'password' => 'no-guardar'],
        ]);

        $registro = Auditoria::query()->where('evento', 'prueba.saneamiento')->firstOrFail();
        $this->assertSame(125.5, $registro->antes['monto']);
        $this->assertSame('[REDACTADO]', $registro->antes['email']);
        $this->assertEquals(100.0, $registro->despues['monto']);
        $this->assertSame('[REDACTADO]', $registro->despues['password']);
    }

    public function test_policy_usa_la_clave_de_permiso_que_genera_shield(): void
    {
        $permisoGenerado = LegacyShieldPermissions::key(
            \App\Filament\Clusters\Sistema\Resources\AuditoriaResource::class,
            'view_any',
            'AuditoriaResource',
        );
        $this->assertSame(AuditoriaPolicy::PERMISO_CONSULTAR, $permisoGenerado);

        $usuario = \Mockery::mock(User::class)->makePartial();
        $usuario->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
        $usuario->shouldReceive('can')->with($permisoGenerado)->andReturn(true);
        $this->assertTrue((new AuditoriaPolicy)->viewAny($usuario));

        $auditorGlobal = \Mockery::mock(User::class)->makePartial();
        $auditorGlobal->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
        $auditorGlobal->shouldReceive('can')->with($permisoGenerado)->andReturn(false);
        $auditorGlobal->shouldReceive('can')->with('ver_todas_auditorias')->andReturn(true);
        $this->assertTrue((new AuditoriaPolicy)->viewAny($auditorGlobal));
    }

    public function test_middleware_registra_peticion_y_codigo_http(): void
    {
        $this->get('/')->assertRedirect('/dashboard');

        $peticion = Auditoria::query()->where('evento', 'http.peticion')->latest('id')->firstOrFail();
        $this->assertSame('GET', $peticion->contexto['metodo']);
        $this->assertSame(302, $peticion->contexto['http_status']);
    }
}
