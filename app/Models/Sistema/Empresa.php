<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Empresa extends Model
{
    use SoftDeletes;

    protected $table = 'conf_empresas';
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
        'empresa_activo',
        'seguro_medico'
    ];

    public function areas()
    {
        return $this->belongsToMany(
            Area::class,
            'conf_area_empresa',   // nombre real de la tabla pivote
            'empresa_id',     // FK hacia conf_empresas
            'area_id'               // FK hacia conf_areas
        );
    }
    public function sucursales()
    {
        return $this->hasMany(Sucursal::class, 'empresa_id');
    }
    public function cargos()
    {
        return $this->hasManyThrough(
            Cargo::class,
            Area::class,
            'id',      // PK en areas
            'area_id', // FK en cargos
            'id',      // PK en empresas
            'id'       // PK en areas
        );
    }
}