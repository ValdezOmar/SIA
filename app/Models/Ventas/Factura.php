<?php

namespace App\Models\Ventas;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Factura extends Model
{
    use SoftDeletes;

    protected $table = 'ven_facturas';  // ✅ Nombre correcto

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'saldo' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'monto_restante' => 'decimal:2',
        'tasa_cambio' => 'decimal:6',
        'tasa_impuesto' => 'decimal:6',
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
            $model->saldo = $model->total ?? 0;
            $model->monto_pagado = 0;
            $model->monto_restante = $model->total ?? 0;
        });
    }

    // ========== RELACIONES ==========

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
    }

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class, 'factura_id');
    }

    public function pagos()
    {
        return $this->hasMany(Pago::class, 'factura_id');
    }

    public function notasCredito()
    {
        return $this->hasMany(NotaCredito::class, 'factura_id');
    }

    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function vendedor()
    {
        return $this->belongsTo(User::class, 'vendedor_id');
    }

    public function cobrador()
    {
        return $this->belongsTo(User::class, 'cobrador_id');
    }

    // ========== MÉTODOS ==========

    public function registrarPago($data)
    {
        $pago = Pago::create([
            'factura_id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'numero' => Pago::generarNumero(),
            'fecha_pago' => $data['fecha_pago'],
            'tipo_pago' => $data['tipo_pago'],
            'monto' => $data['monto'],
            'moneda' => $this->moneda,
            'tasa_cambio' => $this->tasa_cambio,
            'referencia' => $data['referencia'] ?? null,
            'creado_por' => Auth::id(),
            'empresa_id' => $this->empresa_id,
            'estado' => 'confirmado',
        ]);

        $this->actualizarSaldo();
        return $pago;
    }

    public function actualizarSaldo()
    {
        $totalPagado = $this->pagos()->where('estado', 'confirmado')->sum('monto');
        $this->monto_pagado = $totalPagado;
        $this->saldo = $this->total - $totalPagado;

        if ($this->saldo <= 0) {
            $this->estado = 'pagada';
        } elseif ($this->monto_pagado > 0 && $this->saldo > 0) {
            $this->estado = 'parcial';
        }

        $this->save();
    }

    public static function generarNumero()
    {
        $gestion = date('y');
        $prefijo = 'FAC-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('numero', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->numero, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }
}