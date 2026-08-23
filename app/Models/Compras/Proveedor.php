<?php

namespace App\Models\Compras;

use App\Models\Inventario\Articulo;
use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    protected $table = 'cmp_proveedores';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $proveedor): void {
            $proveedor->codigo ??= self::generarCodigo();
        });
    }

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

    public static function generarCodigo(): string
    {
        $ultimo = self::query()
            ->where('codigo', 'LIKE', 'PROV-%')
            ->orderByDesc('codigo')
            ->first();

        $numero = $ultimo && preg_match('/^PROV-(\d+)$/', $ultimo->codigo, $coincidencias)
            ? (int) $coincidencias[1] + 1
            : 1;

        return 'PROV-'.str_pad((string) $numero, 6, '0', STR_PAD_LEFT);
    }
}
