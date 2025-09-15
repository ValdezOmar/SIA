<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use SoftDeletes;

    protected $table = 'conf_sociedades';
    protected $fillable = [
        'razon_social',
        'nombre_comercial',
        'nit',
        'nro_matricula',
        'direccion',
        'ciudad',
        'pais',
        'telefono',
        'celular',
        'email',
        'sitio_web',
        'empresa_activo'
    ];

    public function areas()
    {
        return $this->belongsToMany(
            Area::class,
            'conf_area_sociedad',   // nombre real de la tabla pivote
            'sociedad_id',     // FK hacia conf_sociedades
            'area_id'               // FK hacia conf_areas
        );
    }
    public function sucursales()
    {
        return $this->hasMany(Sucursal::class, 'sociedad_id');
    }
}