<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class ListaPrecio extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_listas_precios';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $lista): void {
            $lista->codigo ??= self::codigoCorrelativo('LPR');
            $lista->empresa_id ??= auth()->user()?->empresa_id;
        });
    }

    public function precios()
    {
        return $this->hasMany(PrecioArticulo::class, 'lista_precio_id');
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
