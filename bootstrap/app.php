<?php

use App\Http\Middleware\MonitorSistemaMiddleware;
use App\Services\Sistema\NotificacionExcepcionOperativaService;
use Filament\Notifications\Notification;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(MonitorSistemaMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (\Throwable $exception): void {
            app(\App\Services\Sistema\AuditoriaSistemaService::class)
                ->registrarExcepcion($exception, request());
        });

        $exceptions->render(function (\RuntimeException $exception, Request $request) {
            if (! app(NotificacionExcepcionOperativaService::class)->esOperativa($exception)) {
                return null;
            }

            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                return null;
            }

            $contenido = app(NotificacionExcepcionOperativaService::class)->contenido($exception);
            Notification::make()
                ->title($contenido['titulo'])
                ->body("Motivo: {$contenido['mensaje']}\n\nSolución: {$contenido['solucion']}")
                ->danger()
                ->persistent()
                ->send();

            return back();
        });
        $exceptions->render(function (\Throwable $exception, Request $request) {
            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                return null;
            }

            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $exception->getStatusCode() < 500) {
                return null;
            }

            Notification::make()
                ->title('No se pudo completar la operación')
                ->body('Ocurrió un error inesperado. El incidente fue registrado; actualice la página e inténtelo nuevamente. Si continúa, comuníquelo al administrador.')
                ->danger()
                ->persistent()
                ->send();

            return back();
        });
    })->create();
