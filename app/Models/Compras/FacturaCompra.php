<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class FacturaCompra extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_facturas_compra';

    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_pago' => 'date',
        'subtotal' => 'decimal:6',
        'descuento' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'saldo' => 'decimal:6',
        'monto_pagado' => 'decimal:6',
        'tasa_cambio' => 'decimal:6',
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
            $model->saldo = $model->total ?? 0;
            $model->monto_pagado = 0;
        });

        static::saved(function ($model) {
            $model->actualizarEstado();
        });
    }

    // ========== RELACIONES ==========

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function ordenCompra()
    {
        return $this->belongsTo(OrdenCompra::class);
    }

    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function detalles()
    {
        return $this->hasMany(FacturaCompraDetalle::class, 'factura_id')->orderBy('linea');
    }

    public function pagos()
    {
        return $this->hasMany(PagoProveedor::class);
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

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['registrada', 'parcial']);
    }

    public function scopePorProveedor($query, $proveedorId)
    {
        return $query->where('proveedor_id', $proveedorId);
    }

    // ========== ACCESORS ==========

    public function getEstadoLabelAttribute()
    {
        return match($this->estado) {
            'borrador' => 'Borrador',
            'registrada' => 'Registrada',
            'pagada' => 'Pagada',
            'parcial' => 'Parcial',
            'anulada' => 'Anulada',
            default => $this->estado,
        };
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $gestion = date('y');
        $prefijo = 'FAC-' . $gestion;

        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', $prefijo . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($ultimo) {
            $correlativo = intval(substr($ultimo->codigo, -4)) + 1;
        } else {
            $correlativo = 1;
        }

        return $prefijo . str_pad($correlativo, 4, '0', STR_PAD_LEFT);
    }

    public function registrarPago($data)
    {
        $pago = PagoProveedor::create([
            'factura_id' => $this->id,
            'proveedor_id' => $this->proveedor_id,
            'codigo' => PagoProveedor::generarCodigo(),
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

        $this->save();
    }

    private function actualizarEstado()
    {
        if ($this->saldo <= 0) {
            $this->estado = 'pagada';
        } elseif ($this->monto_pagado > 0 && $this->saldo > 0) {
            $this->estado = 'parcial';
        } elseif ($this->estado === 'borrador') {
            $this->estado = 'registrada';
        }

        $this->saveQuietly();
    }

    public function recalcularTotales()
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        foreach ($this->detalles as $detalle) {
            $subtotal += floatval($detalle->subtotal ?? 0);
            $descuento += floatval($detalle->descuento ?? 0);
            $impuesto += floatval($detalle->impuesto ?? 0);
            $total += floatval($detalle->total ?? 0);
        }

        $this->update([
            'subtotal' => $subtotal,
            'descuento' => $descuento,
            'impuesto' => $impuesto,
            'total' => $total,
        ]);
    }
}