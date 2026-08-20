<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proveedor extends Model
{
    

    protected $table = 'cmp_proveedores';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    // ========== RELACIONES ==========

    public function cotizaciones()
    {
        return $this->hasMany(CotizacionProveedor::class);
    }

    public function ordenesCompra()
    {
        return $this->hasMany(OrdenCompra::class);
    }

    public function recepciones()
    {
        return $this->hasMany(Recepcion::class);
    }

    public function facturas()
    {
        return $this->hasMany(FacturaCompra::class);
    }

    public function pagos()
    {
        return $this->hasMany(PagoProveedor::class);
    }

    public function articulos()
    {
        return $this->belongsToMany(
            Articulo::class,
            'cmp_articulos_proveedores',
            'proveedor_id',
            'articulo_id'
        );
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    // ========== SCOPES ==========

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    // ========== ACCESORS ==========

    public function getNombreCompletoAttribute()
    {
        return $this->razon_social ?? $this->nombre;
    }

    // ========== MÉTODOS ==========

    public static function generarCodigo()
    {
        $ultimo = self::withTrashed()
            ->where('codigo', 'LIKE', 'PROV-%')
            ->orderBy('id', 'desc')
            ->first();

        $numero = $ultimo ? intval(substr($ultimo->codigo, -6)) + 1 : 1;
        return 'PROV-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}