<?php

namespace App\Models\Ventas;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CotizacionDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'ven_cotizaciones_detalle';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:6',
        'precio_unitario' => 'decimal:6',
        'precio_original' => 'decimal:6',
        'descuento' => 'decimal:6',
        'descuento_porcentaje' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'tasa_impuesto' => 'decimal:6',
        'linea' => 'integer',
        'aplicar_iva' => 'boolean',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Asignar número de línea
            if (!$model->linea) {
                $ultimo = static::where('cotizacion_id', $model->cotizacion_id)
                    ->orderBy('linea', 'desc')
                    ->first();
                $model->linea = $ultimo ? $ultimo->linea + 1 : 1;
            }

            // ✅ Asegurar que precio_original tenga el precio de la lista
            if ($model->precio_original == 0 && $model->precio_unitario > 0) {
                $model->precio_original = $model->precio_unitario;
            }

            // ✅ Asegurar que subtotal esté calculado
            if ($model->subtotal == 0 && $model->cantidad > 0 && $model->precio_unitario > 0) {
                $model->subtotal = ($model->precio_unitario * $model->cantidad) - ($model->descuento ?? 0);
            }

            // ✅ Asegurar que impuesto esté calculado
            if ($model->impuesto == 0 && $model->subtotal > 0 && $model->aplicar_iva) {
                $model->impuesto = $model->subtotal * (13 / 100);
            }

            // ✅ Asegurar que total esté calculado
            if ($model->total == 0 && $model->subtotal > 0) {
                $model->total = $model->subtotal + ($model->impuesto ?? 0);
            }
        });
    }

    // ========== ACCESSORS ==========

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

    // ========== RELACIONES ==========

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }
}
