<?php

namespace App\Models\Ventas;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PedidoDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'ven_pedidos_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'precio_original' => 'decimal:2',
        'descuento' => 'decimal:2',
        'descuento_porcentaje' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'tasa_impuesto' => 'decimal:2',
        'linea' => 'integer',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Asignar número de línea
            if (!$model->linea) {
                $ultimo = static::where('pedido_id', $model->pedido_id)
                    ->orderBy('linea', 'desc')
                    ->first();
                $model->linea = $ultimo ? $ultimo->linea + 1 : 1;
            }

            // Asegurar cálculos automáticos
            if ($model->precio_original == 0 && $model->precio_unitario > 0) {
                $model->precio_original = $model->precio_unitario;
            }

            if ($model->subtotal == 0 && $model->cantidad > 0 && $model->precio_unitario > 0) {
                $model->subtotal = ($model->precio_unitario * $model->cantidad) - ($model->descuento ?? 0);
            }

            if ($model->impuesto == 0 && $model->subtotal > 0) {
                $model->impuesto = $model->subtotal * (($model->tasa_impuesto ?? 13) / 100);
            }

            if ($model->total == 0 && $model->subtotal > 0) {
                $model->total = $model->subtotal + ($model->impuesto ?? 0);
            }
        });
    }

    // ========== RELACIONES ==========

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class, 'articulo_id');
    }

    // ========== ACCESORS ==========

    public function getSubtotalCalculadoAttribute()
    {
        return $this->cantidad * $this->precio_unitario;
    }

    public function getImpuestoCalculadoAttribute()
    {
        return $this->subtotal * ($this->tasa_impuesto / 100);
    }

    public function getTotalCalculadoAttribute()
    {
        return $this->subtotal + $this->impuesto_calculado;
    }

    public function getDescuentoCalculadoAttribute()
    {
        if ($this->precio_original > 0) {
            return $this->precio_original - $this->precio_unitario;
        }
        return 0;
    }

    public function getDescuentoPorcentajeCalculadoAttribute()
    {
        if ($this->precio_original > 0 && $this->precio_original > $this->precio_unitario) {
            return (($this->precio_original - $this->precio_unitario) / $this->precio_original) * 100;
        }
        return 0;
    }
}