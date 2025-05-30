<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Illuminate\Support\Facades\Log;

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
    }

    protected function ensureDirectoriesExist()
    {
        $directories = [
            'livewire-tmp' => storage_path('app/livewire-tmp'),
            'empleados' => storage_path('app/public/empleados'),
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
}
