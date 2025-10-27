<?php

namespace App\Models\Comercial;

use App\Models\Almacen\Articulo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Catalogo extends Model
{
    use HasFactory;

    protected $table = 'com_catalogo';

    protected $fillable = [
        'codigo_articulo',
        'foto_catalogo',
        'descripcion',
        'categoria',
        'stock_minimo',
        'activo',
    ];

    //Relación: un catálogo tiene muchos artículos
    public function articulos()
    {
        return $this->hasMany(Articulo::class, 'codigo', 'codigo_articulo');
    }
    public function articulo()
    {
        return $this->belongsTo(\App\Models\Almacen\Articulo::class, 'codigo_articulo', 'codigo');
    }
}