<?php

namespace App\Models\Inventario;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Illuminate\Validation\ValidationException;

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
            $transferencia->codigo ??= 'TRA-'.now()->format('Ymd-His').'-'.strtoupper(Str::random(4));
            $transferencia->creado_por ??= auth()->id();
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

    public function enviar(): void
    {
        DB::transaction(function (): void {
            /** @var self $transferencia */
            $transferencia = self::query()->lockForUpdate()->findOrFail($this->id);
            $transferencia->validarParaEnvio();

            foreach ($transferencia->detalles()->lockForUpdate()->get() as $detalle) {
                if ($detalle->kardex_salida_id) {
                    continue;
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
                    'observaciones' => 'Salida en tránsito hacia '.$transferencia->almacenDestino->nombre,
                    'series' => $detalle->series,
                    'lotes' => $detalle->lotes,
                ]);

                $detalle->update([
                    'kardex_salida_id' => $salida->id,
                    'costo_unitario_salida' => $salida->costo_unitario,
                ]);
            }

            $transferencia->update([
                'estado' => 'en_transito',
                'enviado_por' => auth()->id(),
                'fecha_envio' => now(),
            ]);
        });
    }

    public function recibir(): void
    {
        DB::transaction(function (): void {
            /** @var self $transferencia */
            $transferencia = self::query()->lockForUpdate()->findOrFail($this->id);
            $transferencia->validarReceptor();

            if ($transferencia->estado !== 'en_transito') {
                throw new RuntimeException('Sólo se pueden recibir transferencias que estén en tránsito.');
            }

            foreach ($transferencia->detalles()->lockForUpdate()->get() as $detalle) {
                if (! $detalle->kardex_salida_id) {
                    throw new RuntimeException('El detalle #'.$detalle->id.' no tiene una salida de origen confirmada.');
                }
                if ($detalle->kardex_entrada_id) {
                    continue;
                }

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
            ]);
        });
    }

    public function rechazar(string $motivo): void
    {
        DB::transaction(function () use ($motivo): void {
            /** @var self $transferencia */
            $transferencia = self::query()->lockForUpdate()->findOrFail($this->id);
            $transferencia->validarReceptor();

            if ($transferencia->estado !== 'en_transito') {
                throw new RuntimeException('Sólo se pueden rechazar transferencias que estén en tránsito.');
            }

            foreach ($transferencia->detalles()->lockForUpdate()->get() as $detalle) {
                if ($detalle->kardex_entrada_id) {
                    throw new RuntimeException('No se puede rechazar una transferencia ya recibida. Registre un nuevo traspaso de devolución.');
                }
                $detalle->kardexSalida?->revertirMovimiento('Transferencia rechazada: '.$motivo);
            }

            $transferencia->update([
                'estado' => 'rechazada',
                'recibido_por' => auth()->id(),
                'fecha_recepcion' => now(),
                'motivo_rechazo' => $motivo,
            ]);
        });
    }

    private function validarParaEnvio(): void
    {
        if ($this->estado !== 'borrador') {
            throw new RuntimeException('Sólo se pueden enviar transferencias en borrador.');
        }
        if ($this->almacen_origen_id === $this->almacen_destino_id) {
            throw new RuntimeException('El almacén origen y destino deben ser diferentes.');
        }
        if (! $this->detalles()->exists()) {
            throw new RuntimeException('Agregue al menos un artículo antes de enviar la transferencia.');
        }
    }

    private function validarReceptor(): void
    {
        if ((int) $this->receptor_id !== (int) auth()->id()) {
            throw new RuntimeException('Sólo el receptor asignado puede aprobar o rechazar esta transferencia.');
        }
    }
}
