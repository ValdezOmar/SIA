<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Imageable extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['imageableable_id', 'imageableable_type'];

    protected $searchableFields = ['*'];

    public function imageableable()
    {
        return $this->morphTo();
    }
}
