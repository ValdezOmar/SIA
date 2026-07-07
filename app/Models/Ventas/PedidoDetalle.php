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
        'cantidad' => 'decimal:6',
        'precio_unitario' => 'decimal:6',
        'precio_original' => 'decimal:6',
        'descuento' => 'decimal:6',
        'descuento_porcentaje' => 'decimal:6',
        'subtotal' => 'decimal:6',
        'impuesto' => 'decimal:6',
        'total' => 'decimal:6',
        'tasa_impuesto' => 'decimal:6',
    ];

    // ========== RELACIONES ==========

    public function pedido()
    {
        return $this->belongsTo(Pedido::class);
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
        return $this->subtotal * ($this->tasa_impuesto / 100);
    }

    public function getTotalCalculadoAttribute()
    {
        return $this->subtotal + $this->impuesto_calculado;
    }
}