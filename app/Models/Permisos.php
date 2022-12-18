<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Permisos extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'descripcion_rechazo',
        'permisosable_id',
        'permisosable_type',
        'tipo_permiso_id',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'rh_permisos';

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    public function tipoPermiso()
    {
        return $this->belongsTo(TipoPermiso::class);
    }

    public function permisosable()
    {
        return $this->morphTo();
    }
}
