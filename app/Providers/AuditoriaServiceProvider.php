<?php

namespace App\Providers;

use App\Models\Sistema\Auditoria;
use App\Policies\Sistema\AuditoriaPolicy;
use App\Services\Sistema\AuditoriaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuditoriaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditoriaService::class);
    }

    public function boot(): void
    {
        Gate::policy(Auditoria::class, AuditoriaPolicy::class);
        foreach (['created', 'updated', 'deleted', 'restored'] as $evento) {
            Event::listen('eloquent.'.$evento.': *', function (string $nombre, array $datos) use ($evento): void {
                if (($datos[0] ?? null) instanceof Model) {
                    app(AuditoriaService::class)->modelo($evento, $datos[0]);
                }
            });
        }
        DB::listen(fn ($evento) => app(AuditoriaService::class)->consulta($evento));
        foreach ([\Illuminate\Auth\Events\Login::class => 'login', \Illuminate\Auth\Events\Logout::class => 'logout', \Illuminate\Auth\Events\Failed::class => 'fallido', \Illuminate\Auth\Events\Lockout::class => 'bloqueo'] as $clase => $nombre) {
            Event::listen($clase, fn ($evento) => app(AuditoriaService::class)->registrar([
                'categoria' => 'seguridad', 'evento' => 'auth.'.$nombre,
                'usuario_id' => isset($evento->user) ? $evento->user?->id : null,
                'nivel' => in_array($nombre, ['fallido', 'bloqueo']) ? 'warning' : 'info',
                'descripcion' => 'Autenticación: '.$nombre,
            ]));
        }
        Event::listen(\Illuminate\Log\Events\MessageLogged::class, function ($evento): void {
            app(AuditoriaService::class)->registrar([
                'evento' => 'sistema.log', 'nivel' => $evento->level,
                'descripcion' => 'Evento de registro · '.$evento->level,
                // El texto libre puede incluir SQL con contraseñas; solo huella para correlacionar con el log protegido.
                'contexto' => ['huella' => hash('sha256', (string) $evento->message), 'nivel' => $evento->level],
            ]);
        });
        Event::listen(\Illuminate\Queue\Events\JobFailed::class, fn ($evento) => app(AuditoriaService::class)->registrar([
            'evento' => 'cola.fallida', 'nivel' => 'critical', 'descripcion' => 'Falló un trabajo en cola',
            'contexto' => ['clase' => $evento->exception::class],
        ]));
        if (function_exists('Livewire\\on')) {
            \Livewire\on('exception', fn ($componente, \Throwable $exception) => app(AuditoriaService::class)->excepcion($exception));
        }
    }
}
