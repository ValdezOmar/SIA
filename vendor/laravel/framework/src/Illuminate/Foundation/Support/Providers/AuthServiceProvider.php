<?php

namespace Illuminate\Foundation\Support\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\RRHH\Empleado;
use app\Policies\RRHH\EmpleadoPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Empleado::class => EmpleadoPolicy::class, // Por defecto para el modelo
        \App\Models\Almacen\Articulo::class => \App\Policies\Almacen\ArticuloPolicy::class,
    ];
    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function register()
    {
        $this->booting(function () {
            $this->registerPolicies();
        });
    }

    /**
     * Register the application's policies.
     *
     * @return void
     */
    public function registerPolicies()
    {
        foreach ($this->policies() as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }

    /**
     * Get the policies defined on the provider.
     *
     * @return array<class-string, class-string>
     */
    public function policies()
    {
        return $this->policies;
    }
}