<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CiteInterno extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = ['cite_interno', 'asunto'];

    protected $searchableFields = ['*'];

    protected $table = 'cor_cite_internos';

    public function tramites()
    {
        return $this->hasMany(Tramite::class);
    }
}
