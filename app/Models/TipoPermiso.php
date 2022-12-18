<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoPermiso extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'nombre_permiso',
        'tipo_permiso',
        'tiempo_permitido',
        'descripcion_permiso',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'rh_tipo_permisos';

    public function permisos()
    {
        return $this->hasMany(Permisos::class);
    }
}
