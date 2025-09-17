<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Sistema\Parametro;

class GoogleParametroProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot()
    {
        // Obtener los parámetros
        $param = Parametro::first();        
            // Sobrescribir config en runtime
            config([
                'services.google.client_id' => $param->google_client_id,
                'services.google.client_secret' => $param->google_client_secret,
                'services.google.redirect' => $param->google_redirect_uri,
            ]);
        
    }
}