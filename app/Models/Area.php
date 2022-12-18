<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Area extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = ['nombre_area', 'descripcion_area', 'prefijo_cite'];

    protected $searchableFields = ['*'];

    protected $table = 'rh_areas';

    public function cargos()
    {
        return $this->hasMany(Cargo::class);
    }
}
