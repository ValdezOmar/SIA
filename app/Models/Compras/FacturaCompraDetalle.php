<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaCompraDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'cmp_facturas_compra_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
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
                $ultimo = static::where('factura_id', $model->factura_id)
                    ->orderBy('linea', 'desc')
                    ->first();
                $model->linea = $ultimo ? $ultimo->linea + 1 : 1;
            }
        });
    }

    // ========== RELACIONES ==========

    public function factura()
    {
        return $this->belongsTo(FacturaCompra::class, 'factura_id');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_id');
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
}
