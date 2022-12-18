<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Tramite extends Model
{
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'recepcion_user_id',
        'hoja_ruta',
        'fecha_ingreso',
        'hr_ext',
        'remitente_externo_id',
        'asunto_ext',
        'cite_ext',
        'cite_interno_id',
        'asunto_int',
        'tipo_documento_id',
        'estado',
        'fusionado',
        'hr_fusionado',
        'remitente_interno_id',
    ];

    protected $searchableFields = ['*'];

    protected $table = 'cor_tramites';

    protected $casts = [
        'fecha_ingreso' => 'datetime',
        'hr_ext' => 'boolean',
        'fusionado' => 'boolean',
    ];

    public function citeInterno()
    {
        return $this->belongsTo(CiteInterno::class);
    }

    public function remitenteExterno()
    {
        return $this->belongsTo(RemitenteExterno::class);
    }

    public function tipoDocumento()
    {
        return $this->belongsTo(TipoDocumento::class);
    }

    public function derivaciones()
    {
        return $this->hasMany(Derivacion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'recepcion_user_id');
    }

    public function remitente_interno()
    {
        return $this->belongsTo(User::class, 'remitente_interno_id');
    }

    public function attacheables()
    {
        return $this->morphMany(Attacheable::class, 'attacheableable');
    }
}
