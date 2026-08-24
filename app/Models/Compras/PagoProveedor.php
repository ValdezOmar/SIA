<?php

namespace App\Models\Compras;

use App\Models\Contabilidad\AsientoContable;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoProveedor extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_pagos_proveedor';

    protected $guarded = [];

    protected $casts = [
        'fecha_pago' => 'date',
        'fecha_cheque' => 'date',
        'monto' => 'decimal:6',
        'tasa_cambio' => 'decimal:6',
        'respaldos' => 'array',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
            if (empty($model->codigo)) {
                $model->codigo = self::generarCodigo();
            }
        });

        static::saving(function ($model) {
            if (! $model->factura_id) {
                return;
            }

            $factura = FacturaCompra::find($model->factura_id);

            if (! $factura) {
                throw ValidationException::withMessages(['factura_id' => 'La factura seleccionada ya no está disponible.']);
            }

            $model->proveedor_id ??= $factura->proveedor_id;
            $model->moneda ??= $factura->moneda;
            $model->tasa_cambio ??= $factura->tasa_cambio;

            if ($model->estado !== 'confirmado') {
                return;
            }

            if (empty($model->respaldos) || ! is_array($model->respaldos)) {
                throw ValidationException::withMessages(['respaldos' => 'Debe adjuntar al menos un respaldo del pago.']);
            }

            $otrosPagosConfirmados = $factura->pagos()
                ->where('estado', 'confirmado')
                ->when($model->exists, fn ($query) => $query->whereKeyNot($model->getKey()))
                ->sum('monto');

            if ((float) $model->monto > ((float) $factura->total - (float) $otrosPagosConfirmados)) {
                throw ValidationException::withMessages(['monto' => 'El monto no puede superar el saldo pendiente de la factura.']);
            }
        });

        static::saved(function ($model) {
            $model->factura?->actualizarSaldo();
        });

        static::deleted(function ($model) {
            FacturaCompra::find($model->factura_id)?->actualizarSaldo();
        });
    }

    // ========== RELACIONES ==========

    public function factura()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_id');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopeConfirmados($query)
    {
        return $query->where('estado', 'confirmado');
    }

    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match ($this->estado) {
            'pendiente' => 'Pendiente',
            'confirmado' => 'Confirmado',
            'rechazado' => 'Rechazado',
            'anulado' => 'Anulado',
            default => $this->estado,
        };
    }

    public function getTipoPagoLabelAttribute()
    {
        return match ($this->tipo_pago) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
            'cheque' => 'Cheque',
            'deposito' => 'Depósito',
            'nota_credito' => 'Nota de Crédito',
            'otros' => 'Otros',
            default => $this->tipo_pago,
        };
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'PAG-P-'.$gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo.str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    public function confirmar()
    {
        $this->estado = 'confirmado';
        $this->save();

        // Actualizar saldo de la factura
        $this->factura->actualizarSaldo();

        return $this;
    }

    public function rechazar($motivo = null)
    {
        $this->estado = 'rechazado';
        if ($motivo) {
            $this->observaciones = ($this->observaciones ? $this->observaciones."\n" : '').'Rechazado: '.$motivo;
        }
        $this->save();

        return $this;
    }

    public function anular(string $motivo): void
    {
        DB::transaction(function () use ($motivo): void {
            /** @var self $pago */
            $pago = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($pago->estado !== 'confirmado') {
                throw ValidationException::withMessages(['estado' => 'Solo se pueden anular pagos confirmados.']);
            }

            AsientoContable::query()
                ->where('documento_tipo', 'pago_proveedor')
                ->where('documento_id', $pago->id)
                ->where('estado', 'confirmado')
                ->each(fn (AsientoContable $asiento) => $asiento->anular($motivo));

            $pago->updateQuietly([
                'estado' => 'anulado',
                'observaciones' => trim(($pago->observaciones ? $pago->observaciones."\n" : '').'Pago anulado: '.$motivo),
            ]);

            $pago->factura?->actualizarSaldo();
        });
    }
}
