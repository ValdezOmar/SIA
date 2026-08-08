<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecepcionDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_recepciones_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'cantidad_aceptada' => 'decimal:6',
        'cantidad_rechazada' => 'decimal:6',
        'costo_unitario' => 'decimal:6',
        'costo_total' => 'decimal:6',
        'linea' => 'integer',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->linea) {
                $ultimo = static::where('recepcion_id', $model->recepcion_id)
                    ->orderBy('linea', 'desc')
                    ->first();
                $model->linea = $ultimo ? $ultimo->linea + 1 : 1;
            }
        });
    }

    // ========== RELACIONES ==========

    public function recepcion()
    {
        return $this->belongsTo(Recepcion::class);
    }

    public function ordenDetalle()
    {
        return $this->belongsTo(OrdenCompraDetalle::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    // ========== ACCESORS ==========

    public function getCantidadTotalAttribute()
    {
        return $this->cantidad_aceptada + $this->cantidad_rechazada;
    }

    public function getTasaAceptacionAttribute()
    {
        if ($this->cantidad == 0) return 0;
        return ($this->cantidad_aceptada / $this->cantidad) * 100;
    }

    public function getEstadoAttribute()
    {
        if ($this->cantidad_rechazada > 0 && $this->cantidad_aceptada > 0) {
            return 'parcial';
        } elseif ($this->cantidad_rechazada > 0) {
            return 'rechazado';
        } elseif ($this->cantidad_aceptada > 0) {
            return 'aceptado';
        }
        return 'pendiente';
    }

    public function getEstadoLabelAttribute()
    {
        return match($this->estado) {
            'aceptado' => '✅ Aceptado',
            'parcial' => '⚠️ Parcial',
            'rechazado' => '❌ Rechazado',
            default => '⏳ Pendiente',
        };
    }

    public function getEstadoColorAttribute()
    {
        return match($this->estado) {
            'aceptado' => 'success',
            'parcial' => 'warning',
            'rechazado' => 'danger',
            default => 'gray',
        };
    }

    // ========== MÉTODOS ==========

    public function aceptar($cantidad = null)
    {
        $cantidad = $cantidad ?? $this->cantidad;
        $this->cantidad_aceptada = $cantidad;
        $this->cantidad_rechazada = $this->cantidad - $cantidad;
        $this->save();

        return $this;
    }

    public function rechazar($motivo = null)
    {
        $this->cantidad_aceptada = 0;
        $this->cantidad_rechazada = $this->cantidad;
        if ($motivo) {
            $this->motivo_rechazo = $motivo;
        }
        $this->save();

        return $this;
    }

    public function actualizarCostoTotal()
    {
        $this->costo_total = $this->cantidad_aceptada * $this->costo_unitario;
        $this->save();
        return $this;
    }
}