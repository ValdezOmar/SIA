<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Derivacion extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'fecha_derivacion',
        'fecha_recepcion',
        'fecha_rechazo',
        'fecha_anulado',
        'fecha_archivo',
        'proveido',
        'tramite_id',
        'remitente_id',
        'destinatario_id',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'cor_derivaciones';

    protected $casts = [
        'fecha_derivacion' => 'datetime',
        'fecha_recepcion' => 'datetime',
        'fecha_rechazo' => 'datetime',
        'fecha_anulado' => 'datetime',
        'fecha_archivo' => 'datetime',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    public function attacheables()
    {
        return $this->morphMany(Attacheable::class, 'attacheableable');
    }
}
