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
        $errorMarkdown = $this->crearMarkdownExcepcion($exception, $request, $status);

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
            'datos' => ['error_markdown' => $errorMarkdown],
            'ip' => $request->ip(),
            'agente_usuario' => mb_substr((string) $request->userAgent(), 0, 1000),
            'resumen' => 'Excepción del sistema HTTP '.$status,
        ]);
    }

    private function crearMarkdownExcepcion(Throwable $exception, Request $request, int $status): string
    {
        $markdown = "# Error del sistema\n\n";
        $markdown .= "- **HTTP:** {$status}\n";
        $markdown .= '- **Método:** '.$request->method()."\n";
        $markdown .= '- **Ruta:** '.($request->route()?->uri() ?? $request->path())."\n";
        $markdown .= '- **URL:** '.$request->url()."\n";
        $markdown .= '- **Fecha:** '.now()->toDateTimeString()."\n\n";

        $nivel = 0;
        for ($actual = $exception; $actual; $actual = $actual->getPrevious()) {
            $markdown .= '## Excepción'.($nivel ? " previa {$nivel}" : '')."\n\n";
            $markdown .= '- **Clase:** '.$actual::class."\n";
            $markdown .= '- **Archivo:** '.$actual->getFile().':'.$actual->getLine()."\n\n";
            $markdown .= "### Mensaje\n\n```text\n".$actual->getMessage()."\n```\n\n";
            $markdown .= "### Traza completa\n\n```text\n".$actual->getTraceAsString()."\n```\n\n";
            $nivel++;
        }

        return $this->redactarTextoTecnico($markdown);
    }

    private function redactarTextoTecnico(string $texto): string
    {
        $patrones = [
            '/(?im)^(cookie|set-cookie|authorization|x-xsrf-token|x-csrf-token):.*$/',
            '/(?i)(laravel_session|xsrf-token|remember_web_[^=\\s]*)=[^;\\s`]+/',
            '/(?im)^.*(?:password|passwd|token|secret|api[_-]?key).*/',
            '/(?i)(password|passwd|token|secret|api[_-]?key)(\\s*[=:]\\s*)([^\\s,;`]+)/',
        ];
        $reemplazos = [
            '$1: [REDACTADO]',
            '$1=[REDACTADO]',
            '[REDACTADO: posible secreto]',
            '$1$2[REDACTADO]',
        ];

        return preg_replace($patrones, $reemplazos, $texto) ?? '[No se pudo sanitizar el detalle técnico]';
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
