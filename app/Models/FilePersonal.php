<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FilePersonal extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'nombres',
        'apellidos',
        'fecha_nacimiento',
        'CI',
        'direccion',
        'telefono_1',
        'telefono_2',
        'email_personal',
        'presentacion',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'rh_file_personal';

    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function imageable()
    {
        return $this->morphOne(Imageable::class, 'imageableable');
    }

    public function attacheables()
    {
        return $this->morphMany(Attacheable::class, 'attacheableable');
    }
}
