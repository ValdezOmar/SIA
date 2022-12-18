<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use Searchable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'fecha_alta',
        'fecha_baja',
        'fecha_cambio',
        'telefono_int',
        'activo',
        'cargo_id',
        'file_personal_id',
    ];

    protected $searchableFields = ['*'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'fecha_alta' => 'date',
        'fecha_baja' => 'date',
        'fecha_cambio' => 'date',
        'activo' => 'boolean',
    ];

    public function cargo()
    {
        return $this->belongsTo(Cargo::class);
    }

    public function filePersonal()
    {
        return $this->belongsTo(FilePersonal::class);
    }

    public function derivaciones()
    {
        return $this->hasMany(Derivacion::class, 'remitente_id');
    }

    public function tramites()
    {
        return $this->hasMany(Tramite::class, 'recepcion_user_id');
    }

    public function tramites2()
    {
        return $this->hasMany(Tramite::class, 'remitente_interno_id');
    }

    public function isSuperAdmin()
    {
        return in_array($this->email, config('auth.super_admins'));
    }
}
