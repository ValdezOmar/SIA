<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cargo extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['nombre_cargo', 'descripcion_funciones', 'area_id'];

    protected $searchableFields = ['*'];

    protected $table = 'rh_cargos';

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }
}
