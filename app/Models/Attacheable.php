<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Attacheable extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['attacheableable_id', 'attacheableable_type'];

    protected $searchableFields = ['*'];

    public function permisos()
    {
        return $this->morphMany(Permisos::class, 'permisosable');
    }

    public function attacheableable()
    {
        return $this->morphTo();
    }
}
