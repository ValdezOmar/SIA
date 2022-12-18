<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RemitenteExterno extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = [
        'nombre_completo',
        'cargo_empresa',
        'telefono_1',
        'telefono_2',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'cor_remitente_externos';

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }
}
