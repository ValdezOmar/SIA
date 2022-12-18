<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class VariablesEmpresa extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['type_id', 'type', 'value', 'status'];

    protected $searchableFields = ['*'];

    protected $table = 'variables_empresas';

    protected $casts = [
        'status' => 'boolean',
    ];
}
