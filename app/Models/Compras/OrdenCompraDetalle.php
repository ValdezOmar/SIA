<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdenCompraDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_ordenes_compra_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'cantidad_recibida' => 'decimal:6',
        'cantidad_facturada' => 'decimal:6',
        'precio_unitario' => 'decimal:6',
        'descuento' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'linea' => 'integer',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->linea) {
                $ultimo = static::where('orden_id', $model->orden_id)
                    ->orderBy('linea', 'desc')
                    ->first();
                $model->linea = $ultimo ? $ultimo->linea + 1 : 1;
            }
        });
    }

    // ========== RELACIONES ==========

    // ✅ Usar 'orden_id' en lugar de 'orden_compra_id'
    public function orden()
    {
        return $this->belongsTo(OrdenCompra::class, 'orden_id');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_id');
    }

    public function recepciones()
    {
        return $this->hasMany(RecepcionDetalle::class, 'orden_detalle_id');
    }

    // ========== ACCESORS ==========

    public function getSubtotalCalculadoAttribute()
    {
        return ($this->cantidad * $this->precio_unitario) - $this->descuento;
    }

    public function getImpuestoCalculadoAttribute()
    {
        return $this->subtotal * 0.13;
    }

    public function getTotalCalculadoAttribute()
    {
        return $this->subtotal + $this->impuesto_calculado;
    }

    public function getCantidadPendienteAttribute()
    {
        return $this->cantidad - $this->cantidad_recibida;
    }

    public function getPorcentajeRecibidoAttribute()
    {
        if ($this->cantidad == 0) return 0;
        return ($this->cantidad_recibida / $this->cantidad) * 100;
    }

    public function getEstaCompletoAttribute()
    {
        return $this->cantidad_recibida >= $this->cantidad;
    }
}