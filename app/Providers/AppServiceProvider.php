<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use App\Models\Sistema\Parametro;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuración para subidas temporales de Livewire
        FileUploadConfiguration::disk('local');
        FileUploadConfiguration::middleware('throttle:60,1');
        FileUploadConfiguration::rules(['file', 'max:2048']);
        FileUploadConfiguration::directory('livewire-tmp');

        // Crear directorios con verificación adicional
        $this->ensureDirectoriesExist();

        // Establecer zona horaria desde la base de datos
        $this->setTimezoneFromDatabase();

        // Establecer configuración de Google desde la base de datos
        $this->setGoogleConfigFromDatabase();
    }
    /**
     * Crea directorios requeridos si no existen
     */
    protected function ensureDirectoriesExist(): void
    {
        $directories = [
            'livewire-tmp' => storage_path('app/livewire-tmp'),
            'empleados'    => storage_path('app/public/empleados'),
        ];

        foreach ($directories as $name => $path) {
            if (!file_exists($path)) {
                mkdir($path, 0755, true);
                Log::info("Directorio {$name} creado en: {$path}");
            }

            if (!is_writable($path)) {
                Log::error("El directorio {$name} no tiene permisos de escritura: {$path}");
            }
        }
    }

    /**
     * Configura la zona horaria en base a la BD
     */
    protected function setTimezoneFromDatabase(): void
    {
        try {
            if (Schema::hasTable('conf_parametros')) {
                $param = Parametro::first();

                if ($param && $param->timezone) {
                    config(['app.timezone' => $param->timezone]);
                    date_default_timezone_set($param->timezone);
                    //Log::info("Zona horaria establecida desde BD: {$param->timezone}");
                    return;
                }
            }

            Log::warning("No se encontró zona horaria en BD, usando la de .env");
        } catch (\Exception $e) {
            Log::error("Error estableciendo timezone desde BD: " . $e->getMessage());
        }

        // Fallback a .env
        $defaultTz = env('APP_TIMEZONE', 'UTC');
        config(['app.timezone' => $defaultTz]);
        date_default_timezone_set($defaultTz);
    }

    /**
     * Configura credenciales de Google en runtime desde la BD
     */
    protected function setGoogleConfigFromDatabase(): void
    {
        try {
            if (Schema::hasTable('conf_parametros')) {
                $param = Parametro::first();

                if ($param && $param->google_client_id) {
                    config([
                        'services.google.client_id'     => $param->google_client_id,
                        'services.google.client_secret' => $param->google_client_secret,
                        'services.google.redirect'      => $param->google_redirect_uri,
                    ]);

                    //Log::info("Configuración de Google cargada desde BD.");
                    return;
                }
            }

            Log::warning("No se encontraron credenciales de Google en BD. Usando .env.");
        } catch (\Exception $e) {
            Log::error("Error cargando configuración Google desde BD: " . $e->getMessage());
        }
    }
}