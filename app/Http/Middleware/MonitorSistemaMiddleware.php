<?php

namespace App\Http\Middleware;

use App\Services\Sistema\AuditoriaSistemaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MonitorSistemaMiddleware
{
    public function __construct(private readonly AuditoriaSistemaService $auditoria) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $this->auditoria->registrarRespuestaHttp($request, $response);

        return $response;
    }
}
