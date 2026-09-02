<?php

namespace App\Models\Ventas;

use App\Models\Contabilidad\AsientoContable;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Pago extends Model
{
    use SoftDeletes;

    protected $table = 'ven_pagos';  // Nombre correcto

    protected $guarded = [];

    protected $casts = [
        'fecha_pago' => 'date',
        'fecha_cheque' => 'date',
        'monto' => 'decimal:2',
        'tasa_cambio' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->creado_por = Auth::id();
            }
            if (empty($model->numero)) {
                $model->numero = self::generarNumero();
            }
        });
    }

    // ========== RELACIONES ==========

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function confirmar(): self
    {
        return DB::transaction(function (): self {
            $pago = self::query()->lockForUpdate()->findOrFail($this->id);
            if ($pago->estado === 'confirmado') {
                return $pago;
            }
            if (in_array($pago->estado, ['rechazado', 'anulado'], true)) {
                throw new \RuntimeException('No se puede confirmar un pago rechazado o anulado.');
            }

            $pago->update(['estado' => 'confirmado']);
            AsientoContable::crearDesdePagoCliente($pago);
            $factura = $pago->factura;
            $factura->actualizarSaldo();
            $factura->refresh();
            if ((float) $factura->saldo <= 0) {
                $factura->procesarVentaAutomatica();
            } else {
                $factura->asegurarPedidoReservado();
            }

            return $pago;
        });
    }

    public function asientoContable()
    {
        return $this->hasOne(AsientoContable::class, 'documento_id')
            ->where('documento_tipo', 'pago_cliente');
    }

    // ========== MÉTODOS ==========

    public static function generarNumero()
    {
        $gestion = date('y');
        $prefijo = 'PAG-'.$gestion;

        $ultimo = self::withTrashed()
            ->where('numero', 'LIKE', $prefijo.'%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->numero, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo.str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}
