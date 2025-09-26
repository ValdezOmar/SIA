<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table = 'conf_cargos';
    protected $fillable = ['nombre', 'area_id'];

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    // Acceso a la empresa a través del área
    public function empresas()
    {
        return $this->hasManyThrough(
            Empresa::class,
            Area::class,
            'id',          // FK en areas
            'id',          // PK en empresas
            'area_id',     // FK en cargos
            'id'           // PK en areas
        );
    }
}