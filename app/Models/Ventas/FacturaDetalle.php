<?php

namespace App\Models\Ventas;

use App\Models\User;
use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacturaDetalle extends Model
{
    use SoftDeletes;

    protected $table = 'ven_facturas_detalle';  // ✅ Nombre correcto de la tabla

    protected $guarded = [];

    protected $fillable = [
        'factura_id',
        'articulo_id',
        'lista_precio',
        'codigo_articulo',
        'descripcion_articulo',
        'unidad_medida',
        'cantidad',
        'precio_unitario',
        'precio_original',
        'descuento',
        'descuento_porcentaje',
        'subtotal',
        'impuesto',
        'total',
        'tipo_impuesto',
        'tasa_impuesto',
        'aplicar_iva',
        'observaciones',
        'series',
        'lotes',
        'creado_por',
    ];

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
        'aplicar_iva' => 'boolean',
        'series' => 'array',
        'lotes' => 'array',
    ];

    // ========== BOOT ==========

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Asegurar cálculos automáticos
            if ($model->subtotal == 0 && $model->cantidad > 0 && $model->precio_unitario > 0) {
                $model->subtotal = ($model->precio_unitario * $model->cantidad) - ($model->descuento ?? 0);
            }

            if ($model->impuesto == 0 && $model->subtotal > 0) {
                $model->impuesto = $model->subtotal * (13 / 100);
            }

            if ($model->total == 0 && $model->subtotal > 0) {
                $model->total = $model->subtotal + ($model->impuesto ?? 0);
            }
        });
    }

    // ========== RELACIONES ==========

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    // ========== ACCESORS ==========

    public function getSubtotalCalculadoAttribute()
    {
        return $this->cantidad * $this->precio_unitario;
    }

    public function getImpuestoCalculadoAttribute()
    {
        return $this->subtotal * (13 / 100);
    }

    public function getTotalCalculadoAttribute()
    {
        return $this->subtotal + $this->impuesto_calculado;
    }
}
