<?php

namespace App\Models\Sistema;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Parametro extends Model
{
    use HasFactory;

    protected $table = 'conf_parametros';

    protected $fillable = [
        // Logos y branding
        'logo_path',
        'favicon_path',
        'fondo_path',
        'color_principal',
        'color_secundario',

        // Integraciones externas
        'google_activo',
        'google_client_id',
        'google_client_secret',
        'google_redirect_uri',

        // Configuración interna
        'timezone',
    ];
}