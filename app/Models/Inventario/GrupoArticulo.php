<?php

namespace App\Models\Inventario;

use App\Models\Sistema\Empresa;
use Illuminate\Database\Eloquent\Model;

class GrupoArticulo extends Model
{
    use Concerns\GeneraCodigoInventario;

    protected $table = 'alm_grupos_articulos';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::creating(function (self $grupo): void {
            $grupo->codigo ??= self::codigoDosIniciales($grupo->nombre);
        });
    }

    public function grupoPadre()
    {
        return $this->belongsTo(self::class, 'grupo_padre_id');
    }

    public function subgrupos()
    {
        return $this->hasMany(self::class, 'grupo_padre_id');
    }

    public function articulos()
    {
        return $this->hasMany(Articulo::class);
    }

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }
}
