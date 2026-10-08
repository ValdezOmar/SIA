<?php

namespace App\Http\Middleware;

use App\Services\Sistema\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrarAuditoria
{
    public function handle(Request $request, Closure $next): mixed
    {
        $request->attributes->set('auditoria_id', (string) Str::uuid());
        $inicio = microtime(true);
        try {
            $respuesta = $next($request);
            $status = $respuesta->getStatusCode();

            return $respuesta;
        } finally {
            app(AuditoriaService::class)->registrar([
                'origen' => 'web', 'categoria' => 'acceso', 'evento' => 'http.peticion',
                'nivel' => ($status ?? 500) >= 500 ? 'error' : (($status ?? 500) >= 400 ? 'warning' : 'info'),
                'descripcion' => 'Petición '.$request->method().' · '.($request->route()?->getName() ?? 'sin nombre'),
                'contexto' => ['metodo' => $request->method(), 'ruta' => $request->route()?->uri(), 'http_status' => $status ?? 500, 'duracion_ms' => round((microtime(true) - $inicio) * 1000, 2)],
            ]);
        }
    }
}
