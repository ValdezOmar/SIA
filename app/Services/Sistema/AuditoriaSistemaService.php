<?php

namespace App\Services\Sistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class AuditoriaSistemaService
{
    private const TABLA = 'sys_eventos_auditoria';

    private const CAMPOS_SENSIBLES = [
        'password', 'password_confirmation', 'remember_token', 'token', 'access_token',
        'refresh_token', 'client_secret', 'secret', 'authorization', 'cookie', 'google_token',
        'google_refresh_token', 'api_key', 'private_key',
    ];

    public function registrarCambioModelo(string $evento, Model $modelo): void
    {
        if ($modelo->getTable() === self::TABLA) {
            return;
        }

        $accion = $this->accionModelo($evento);
        if (! $accion) {
            return;
        }

        $datos = match ($accion) {
            'creado' => ['nuevo' => $this->sanitizar($modelo->getAttributes())],
            'actualizado' => $this->cambios($modelo),
            'eliminado' => ['anterior' => $this->sanitizar($modelo->getRawOriginal())],
        };

        $this->guardar([
            'tipo_evento' => $accion,
            'nivel' => $accion === 'eliminado' ? 'advertencia' : 'informativo',
            'modelo' => $modelo::class,
            'tabla' => $modelo->getTable(),
            'registro_id' => (string) $modelo->getKey(),
            'usuario_id' => auth()->id(),
            'empresa_id' => auth()->user()?->empresa_id,
            'ruta' => request()?->route()?->uri(),
            'metodo_http' => request()?->method(),
            'ip' => request()?->ip(),
            'resumen' => ucfirst($accion).' registro de '.$modelo->getTable(),
            'datos' => $datos,
        ]);
    }

    public function registrarRespuestaHttp(Request $request, Response $response): void
    {
        $status = $response->getStatusCode();
        if ($status < 400) {
            return;
        }

        $this->guardar([
            'tipo_evento' => 'error_http',
            'nivel' => $status >= 500 ? 'critico' : 'advertencia',
            'usuario_id' => auth()->id(),
            'empresa_id' => auth()->user()?->empresa_id,
            'ruta' => $request->route()?->uri(),
            'metodo_http' => $request->method(),
            'codigo_http' => $status,
            'ip' => $request->ip(),
            'agente_usuario' => mb_substr((string) $request->userAgent(), 0, 1000),
            'resumen' => 'Respuesta HTTP '.$status,
        ]);
    }

    public function registrarExcepcion(Throwable $exception, Request $request): void
    {
        $status = $exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500;

        $this->guardar([
            'tipo_evento' => 'error_sistema',
            'nivel' => $status >= 500 ? 'critico' : 'advertencia',
            'usuario_id' => auth()->id(),
            'empresa_id' => auth()->user()?->empresa_id,
            'ruta' => $request->route()?->uri(),
            'metodo_http' => $request->method(),
            'codigo_http' => $status,
            'clase_excepcion' => $exception::class,
            'linea_excepcion' => $exception->getLine(),
            'ip' => $request->ip(),
            'agente_usuario' => mb_substr((string) $request->userAgent(), 0, 1000),
            'resumen' => 'Excepción del sistema HTTP '.$status,
        ]);
    }

    private function accionModelo(string $evento): ?string
    {
        return match (true) {
            str_starts_with($evento, 'eloquent.created: ') => 'creado',
            str_starts_with($evento, 'eloquent.updated: ') => 'actualizado',
            str_starts_with($evento, 'eloquent.deleted: '),
            str_starts_with($evento, 'eloquent.forceDeleted: ') => 'eliminado',
            default => null,
        };
    }

    private function cambios(Model $modelo): array
    {
        $cambios = [];

        foreach ($modelo->getChanges() as $campo => $nuevo) {
            $cambios[$campo] = [
                'anterior' => $this->sanitizarValor($campo, $modelo->getRawOriginal($campo)),
                'nuevo' => $this->sanitizarValor($campo, $nuevo),
            ];
        }

        return ['cambios' => $cambios];
    }

    private function sanitizar(array $atributos): array
    {
        $limpios = [];

        foreach ($atributos as $campo => $valor) {
            $limpios[$campo] = $this->sanitizarValor((string) $campo, $valor);
        }

        return $limpios;
    }

    private function sanitizarValor(string $campo, mixed $valor): mixed
    {
        $campoNormalizado = mb_strtolower($campo);

        foreach (self::CAMPOS_SENSIBLES as $sensible) {
            if (str_contains($campoNormalizado, $sensible)) {
                return '[REDACTADO]';
            }
        }

        if (is_string($valor) && mb_strlen($valor) > 2000) {
            return mb_substr($valor, 0, 2000).' [TRUNCADO]';
        }

        if (is_array($valor)) {
            return $this->sanitizar($valor);
        }

        if (is_object($valor) || is_resource($valor)) {
            return '[NO SERIALIZABLE]';
        }

        return $valor;
    }

    private function guardar(array $evento): void
    {
        try {
            if (! Schema::hasTable(self::TABLA)) {
                return;
            }

            $evento += [
                'modelo' => null,
                'tabla' => null,
                'registro_id' => null,
                'usuario_id' => null,
                'empresa_id' => null,
                'ruta' => null,
                'metodo_http' => null,
                'codigo_http' => null,
                'clase_excepcion' => null,
                'linea_excepcion' => null,
                'ip' => null,
                'agente_usuario' => null,
                'resumen' => null,
                'datos' => null,
            ];

            $evento['datos'] = $evento['datos'] === null ? null : json_encode($evento['datos'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $evento['created_at'] = now();

            DB::table(self::TABLA)->insert($evento);
        } catch (Throwable $exception) {
            Log::error('No se pudo persistir un evento de auditoría del sistema.', [
                'tipo_evento' => $evento['tipo_evento'] ?? null,
                'mensaje' => $exception->getMessage(),
            ]);
        }
    }
}
