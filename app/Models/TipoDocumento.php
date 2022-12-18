<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoDocumento extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['tipo_documento'];

    protected $searchableFields = ['*'];

    protected $table = 'cor_tipo_documentos';

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }
}
