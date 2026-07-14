<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use Illuminate\Database\Eloquent\Model;

class ArticuloProveedor extends Model
{
    protected $table = 'cmp_articulos_proveedores';

    protected $guarded = [];

    protected $casts = [
        'costo_compra' => 'decimal:2',
        'es_principal' => 'boolean',
    ];

    public function articulo()
    {
        return $this->belongsTo(Articulo::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    // Scope para proveedores principales
    public function scopePrincipal($query)
    {
        return $query->where('es_principal', true);
    }

    // Scope para proveedores activos
    public function scopeActivos($query)
    {
        return $query->whereHas('proveedor', function ($q) {
            $q->where('activo', true);
        });
    }
}