<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'conf_areas';
    protected $fillable = ['nombre'];

    public function cargos()
    {
        return $this->hasMany(Cargo::class);
    }

   public function empresas()
    {
        return $this->belongsToMany(
            Empresa::class,
            'conf_area_empresa', // tabla pivote
            'area_id',            // FK de este modelo
            'empresa_id'         // FK del otro modelo
        );
    }
}