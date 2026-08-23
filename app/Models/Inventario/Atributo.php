<?php

namespace App\Models\Inventario;

use Illuminate\Database\Eloquent\Model;

class Atributo extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_atributos';

    protected $guarded = [];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $atributo): void {
            $atributo->codigo ??= self::codigoCorrelativo('ATR');
        });
    }

    // ========== RELACIONES ==========

    public function articulos()
    {
        return $this->belongsToMany(
            Articulo::class,
            'alm_articulos_atributos',
            'atributo_id',
            'articulo_id'
        )->withPivot('valor');
    }

    public function articulosAtributos()
    {
        return $this->hasMany(ArticuloAtributo::class, 'atributo_id');
    }

    // ========== SCOPES ==========

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeByNombre($query, $nombre)
    {
        return $query->where('nombre', 'like', "%{$nombre}%");
    }
}
