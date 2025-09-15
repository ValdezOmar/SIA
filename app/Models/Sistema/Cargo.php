<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    protected $table = 'conf_cargos';
    protected $fillable = ['nombre', 'area_id'];

    public function area() {
        return $this->belongsTo(Area::class);
    }
}