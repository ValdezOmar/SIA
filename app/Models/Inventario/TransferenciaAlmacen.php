<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TransferenciaAlmacen extends Model
{
    protected $table = 'alm_transferencias_almacenes';

    protected $guarded = [];

    protected $casts = [
        'fecha_envio' => 'datetime',
        'fecha_recepcion' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $transferencia): void {
            $transferencia->codigo ??= self::generarCodigo();
            $transferencia->creado_por ??= auth()->id();
            $transferencia->enviado_por ??= auth()->id();
            $transferencia->fecha_envio ??= now();
            $transferencia->estado ??= 'en_transito';
        });

        static::saving(function (self $transferencia): void {
            $origen = Almacen::query()->find($transferencia->almacen_origen_id);
            $destino = Almacen::query()->find($transferencia->almacen_destino_id);

            if (! $origen || ! $destino) {
                throw ValidationException::withMessages([
                    'almacen_origen_id' => 'Debe seleccionar almacenes válidos para el traspaso.',
                ]);
            }

            if ((int) $origen->empresa_id !== (int) $destino->empresa_id) {
                throw ValidationException::withMessages([
                    'almacen_destino_id' => 'No se puede transferir inventario entre empresas diferentes.',
                ]);
            }

            $user = auth()->user();
            if ($user && ! $user->hasAnyRole(['super_admin', 'admin'])
                && (int) $origen->empresa_id !== (int) $user->empresa_id) {
                throw ValidationException::withMessages([
                    'almacen_origen_id' => 'El almacén de origen no pertenece a su empresa.',
                ]);
            }
        });
    }

    public function almacenOrigen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_origen_id');
    }

    public function almacenDestino(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_destino_id');
    }

    public function receptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receptor_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function receptorConfirmador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recibido_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(TransferenciaAlmacenDetalle::class, 'transferencia_id');
    }

    public static function generarCodigo(): string
    {
        return DB::transaction(function (): string {
            $prefijo = 'TRA-'.now()->format('y');
            $siguiente = 1;

            $codigos = self::query()
                ->where('codigo', 'like', $prefijo.'%')
                ->lockForUpdate()
                ->pluck('codigo');

            foreach ($codigos as $codigo) {
                if (preg_match('/^'.preg_quote($prefijo, '/').'([0-9]{4})$/', $codigo, $coincidencia)) {
                    $siguiente = max($siguiente, (int) $coincidencia[1] + 1);
                }
            }

            return $prefijo.str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
        });
    }

    public function recibir(?string $observaciones = null): void
    {
        DB::transaction(function () use ($observaciones): void {
            /** @var self $transferencia */
            $transferencia = self::query()->lockForUpdate()->findOrFail($this->id);
            $transferencia->validarReceptor();

            if ($transferencia->estado !== 'en_transito') {
                throw new RuntimeException('Sólo se pueden aceptar solicitudes de traspaso pendientes.');
            }

            foreach ($transferencia->detalles()->lockForUpdate()->get() as $detalle) {
                if ($detalle->kardex_salida_id || $detalle->kardex_entrada_id) {
                    throw new RuntimeException('Esta solicitud ya tiene movimientos de inventario registrados.');
                }

                $salida = Kardex::registrarMovimiento([
                    'articulo_id' => $detalle->articulo_id,
                    'almacen_id' => $transferencia->almacen_origen_id,
                    'tipo_movimiento' => 'transferencia_salida',
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => 0,
                    'documento_tipo' => 'transferencia_almacen',
                    'documento_id' => $transferencia->id,
                    'documento_detalle_id' => $detalle->id,
                    'documento_codigo' => $transferencia->codigo,
                    'observaciones' => 'Traspaso aceptado hacia '.$transferencia->almacenDestino->nombre,
                    'series' => $detalle->series,
                    'lotes' => $detalle->lotes,
                ]);

                $detalle->update([
                    'kardex_salida_id' => $salida->id,
                    'costo_unitario_salida' => $salida->costo_unitario,
                ]);

                $entrada = Kardex::registrarMovimiento([
                    'articulo_id' => $detalle->articulo_id,
                    'almacen_id' => $transferencia->almacen_destino_id,
                    'tipo_movimiento' => 'transferencia_entrada',
                    'cantidad' => $detalle->cantidad,
                    'costo_unitario' => $detalle->costo_unitario_salida,
                    'documento_tipo' => 'transferencia_almacen',
                    'documento_id' => $transferencia->id,
                    'documento_detalle_id' => $detalle->id,
                    'documento_codigo' => $transferencia->codigo,
                    'movimiento_relacionado_id' => $salida->id,
                    'observaciones' => 'Recepción aprobada desde '.$transferencia->almacenOrigen->nombre,
                    'series' => $detalle->series,
                    'lotes' => $detalle->lotes,
                ]);

                $detalle->update(['kardex_entrada_id' => $entrada->id]);
            }

            $transferencia->update([
                'estado' => 'recibida',
                'recibido_por' => auth()->id(),
                'fecha_recepcion' => now(),
                'observaciones_resolucion' => $this->limpiarObservaciones($observaciones),
            ]);
        });
    }

    public function rechazar(string $motivo, ?string $observaciones = null): void
    {
        $this->validarReceptor();

        $motivo = trim($motivo);
        if ($motivo === '') {
            throw ValidationException::withMessages(['motivo' => 'Debe indicar el motivo del rechazo.']);
        }

        DB::transaction(function () use ($motivo, $observaciones): void {
            /** @var self $transferencia */
            $transferencia = self::query()->lockForUpdate()->findOrFail($this->id);
            $transferencia->validarReceptor();

            if ($transferencia->estado !== 'en_transito') {
                throw new RuntimeException('Sólo se pueden rechazar solicitudes de traspaso pendientes.');
            }

            foreach ($transferencia->detalles()->lockForUpdate()->get() as $detalle) {
                if ($detalle->kardex_salida_id || $detalle->kardex_entrada_id) {
                    throw new RuntimeException('Esta solicitud ya tiene movimientos de inventario registrados.');
                }
            }

            $transferencia->update([
                'estado' => 'rechazada',
                'recibido_por' => auth()->id(),
                'fecha_recepcion' => now(),
                'motivo_rechazo' => $motivo,
                'observaciones_resolucion' => $this->limpiarObservaciones($observaciones),
            ]);
        });
    }

    public function anular(): void
    {
        $this->validarCreador();

        DB::transaction(function (): void {
            /** @var self $transferencia */
            $transferencia = self::query()->lockForUpdate()->findOrFail($this->id);
            $transferencia->validarCreador();

            if ($transferencia->estado !== 'en_transito') {
                throw new RuntimeException('Sólo se pueden anular solicitudes pendientes.');
            }

            if ($transferencia->detalles()->where(fn ($query) => $query
                ->whereNotNull('kardex_salida_id')->orWhereNotNull('kardex_entrada_id'))->exists()) {
                throw new RuntimeException('No se puede anular una solicitud con movimientos de inventario registrados.');
            }

            $transferencia->update([
                'estado' => 'borrador',
                'motivo_rechazo' => 'Solicitud anulada por quien la creó.',
                'recibido_por' => auth()->id(),
                'fecha_recepcion' => now(),
            ]);
        });
    }

    private function validarReceptor(): void
    {
        if (! auth()->check() || (int) $this->receptor_id !== (int) auth()->id()) {
            throw new RuntimeException('Sólo la persona responsable del almacén de destino puede aceptar o rechazar esta solicitud.');
        }
    }

    private function validarCreador(): void
    {
        if (! auth()->check() || (int) $this->creado_por !== (int) auth()->id()) {
            throw new RuntimeException('Sólo el usuario que creó esta solicitud puede anularla.');
        }
    }

    private function limpiarObservaciones(?string $observaciones): ?string
    {
        $observaciones = trim((string) $observaciones);

        return $observaciones === '' ? null : $observaciones;
    }
}
