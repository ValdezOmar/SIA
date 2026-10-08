<?php

namespace App\Services\Sistema;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class AuditoriaService
{
    private bool $escribiendo = false;

    private bool $falloAvisado = false;

    /** @var array<string, bool> */
    private array $tablasDisponibles = [];

    public function registrar(array $datos, ?Connection $conexion = null): void
    {
        if ($this->escribiendo || ! config('auditoria.enabled')) {
            return;
        }

        $this->escribiendo = true;
        try {
            $conexion ??= DB::connection();
            $conexionNombre = $conexion->getName();
            if (($this->tablasDisponibles[$conexionNombre] ?? false) !== true) {
                $this->tablasDisponibles[$conexionNombre] = $conexion->getSchemaBuilder()->hasTable('sis_auditorias');
            }
            if (! $this->tablasDisponibles[$conexionNombre]) {
                return;
            }
            $usuario = auth()->user();
            $request = app()->bound('request') ? request() : null;
            $registro = array_merge([
                'registrado_at' => now(),
                'correlacion_id' => $request?->attributes->get('auditoria_id'),
                'usuario_id' => $usuario?->id,
                'empresa_id' => $usuario?->empresa_id,
                'sucursal_id' => $usuario?->sucursal_id,
                'origen' => app()->runningInConsole() ? 'consola' : 'web',
                'categoria' => 'sistema',
                'nivel' => 'info',
            ], $datos);
            foreach (['antes', 'despues', 'contexto'] as $campo) {
                $registro[$campo] = isset($registro[$campo]) ? json_encode($this->sanear($registro[$campo]), JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE) : null;
            }
            $conexion->table('sis_auditorias')->insert($registro);
        } catch (Throwable) {
            // No interrumpir una venta ni entrar en recursión si falla el destino.
            if (! $this->falloAvisado) {
                error_log('SIA: fallo al persistir auditoría. Revisar conexión y tabla sis_auditorias.');
                $this->falloAvisado = true;
            }
        } finally {
            $this->escribiendo = false;
        }
    }

    public function modelo(string $evento, Model $modelo): void
    {
        if ($this->escribiendo || ! str_starts_with($modelo::class, 'App\\Models\\') || $modelo->getTable() === 'sis_auditorias') {
            return;
        }
        $cambios = $evento === 'updated' ? $modelo->getChanges() : $modelo->getAttributes();
        unset($cambios['updated_at']);
        if (! $cambios) {
            return;
        }
        $antes = $evento === 'created' ? null : array_intersect_key($modelo->getRawOriginal(), $cambios);
        $despues = $evento === 'deleted' ? null : $cambios;
        $categoria = strtolower(explode('\\', str_replace('App\\Models\\', '', $modelo::class))[0]);
        $nivel = $evento === 'deleted' || in_array($cambios['estado'] ?? null, ['anulado', 'anulada', 'cancelado', 'cancelada'], true)
            ? 'critical'
            : 'info';
        $datos = [
            'categoria' => $categoria, 'evento' => 'modelo.'.$evento, 'nivel' => $nivel,
            'entidad' => $modelo::class, 'entidad_id' => (string) $modelo->getKey(), 'tabla' => $modelo->getTable(),
            'descripcion' => class_basename($modelo).' #'.$modelo->getKey().' · '.$evento,
            'antes' => $antes, 'despues' => $despues,
            'contexto' => ['campos' => array_keys($cambios)],
        ];
        foreach (['empresa_id', 'sucursal_id'] as $campo) {
            if (array_key_exists($campo, $modelo->getAttributes())) {
                $datos[$campo] = $modelo->getRawOriginal($campo) ?? $modelo->getAttribute($campo);
            }
        }
        $this->registrar($datos, $modelo->getConnection());
    }

    public function consulta(QueryExecuted $evento): void
    {
        if ($this->escribiendo) {
            return;
        }
        // No almacenar SQL, bindings ni WHERE: pueden contener claves y datos personales.
        if (! preg_match('/^\s*(insert(?:\s+ignore)?\s+into|replace\s+into|update|delete\s+from|truncate(?:\s+table)?)\s+["`\[]?([\w.]+)/i', $evento->sql, $partes)) {
            return;
        }
        $tabla = $partes[2];
        if (in_array($tabla, config('auditoria.tablas_excluidas'), true)) {
            return;
        }
        $operacion = strtolower(explode(' ', $partes[1])[0]);
        $this->registrar([
            'categoria' => 'base_datos', 'evento' => 'sql.'.$operacion,
            'tabla' => $tabla, 'nivel' => in_array($operacion, ['delete', 'truncate']) ? 'warning' : 'info',
            'descripcion' => 'Escritura '.$operacion.' en '.$tabla,
            'contexto' => ['conexion' => $evento->connectionName, 'duracion_ms' => $evento->time, 'transaccion' => $evento->connection->transactionLevel()],
        ], $evento->connection);
    }

    public function excepcion(Throwable $exception): void
    {
        $this->registrar([
            'evento' => 'sistema.excepcion', 'nivel' => $exception instanceof \RuntimeException && ! $exception instanceof \Illuminate\Database\QueryException ? 'warning' : 'error',
            'descripcion' => 'Excepción: '.class_basename($exception),
            'contexto' => ['clase' => $exception::class, 'codigo' => (string) $exception->getCode()],
        ]);
    }

    public function sanear(mixed $valor, string $campo = '', int $profundidad = 0): mixed
    {
        if (preg_match('/password|passwd|secret|token|credential|authorization|cookie|api_key|private_key/i', $campo)) {
            return '[REDACTADO]';
        }
        if ($profundidad > 4) {
            return '[LÍMITE]';
        }
        if (is_array($valor)) {
            $salida = [];
            foreach (array_slice($valor, 0, 100, true) as $clave => $dato) {
                $salida[$clave] = $this->sanear($dato, is_int($clave) ? $campo : (string) $clave, $profundidad + 1);
            }

            return $salida;
        }
        if ($valor === null || is_bool($valor)) {
            return $valor;
        }
        // Lista permitida de valores operativos. Los demás campos quedan censurados.
        if (preg_match('/(^id$|_id$|^estado$|^moneda$|^tipo_|^cantidad|^precio|^costo|^monto|^saldo|^subtotal$|^total$|^descuento|^impuesto|^stock|^factor|^activo$|^fecha_|_at$|^campos$|^clase$|^codigo$|^conexion$|^duracion_ms$|^transaccion$|^metodo$|^ruta$|^http_status$|^nivel$|^huella$)/', $campo) && is_scalar($valor)) {
            return is_string($valor) ? Str::limit($valor, 500, '') : $valor;
        }

        return '[REDACTADO]';
    }
}
