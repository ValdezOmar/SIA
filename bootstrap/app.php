<?php

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
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (\RuntimeException $exception, Request $request) {
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
    })->create();
