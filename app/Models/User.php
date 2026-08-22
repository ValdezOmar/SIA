<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\HasAvatar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements HasAvatar
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_token',
        'google_refresh_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relacion de usuario empleado para edicion de perfil
    public function empleado()
    {
        return $this->hasOneThrough(
            \App\Models\RRHH\Empleado::class,
            \App\Models\RRHH\HistorialLaboral::class,
            'correo_corporativo',
            'id',
            'email',
            'empleado_id',
        )->where('rh_historial_laboral.activo', true);
    }

    // Asociacion de foto de perfil con avatar
    public function getFilamentAvatarUrl(): ?string
    {
        return $this->empleado?->foto_url ?? asset('images/default-avatar.jpg');
    }
}
