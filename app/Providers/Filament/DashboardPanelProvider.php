<?php

namespace App\Providers\Filament;

use App\Filament\Resources\RRHH\PerfilEmpleadoResource;
use App\Filament\Widgets\EmpresaMailWidget;
use App\Models\RRHH\PerfilEmpleado;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Nuxtifyts\DashStackTheme\DashStackThemePlugin;

class DashboardPanelProvider extends PanelProvider
{
    /**
     * Configuración principal del panel administrativo.
     *
     * Las opciones detalladas se agrupan en métodos privados para facilitar
     * su identificación y permitir cambios sin recorrer toda la clase.
     */
    public function panel(Panel $panel): Panel
    {
        return $panel
            // Identidad y acceso al panel.
            ->default() // Define este panel como el panel principal de Filament.
            ->id('dashboard') // Identificador interno único del panel.
            ->path('dashboard') // URL base desde la que se accede al sistema.
            ->login(GoogleAuthProvider::class) // Pantalla de acceso personalizada con Google y credenciales.
            ->authGuard('web') // Guardia de Laravel utilizada para autenticar a los usuarios.

            // Apariencia general.
            ->brandLogo(asset('/images/logo.png')) // Logo mostrado en la cabecera y barra lateral.
            ->brandLogoHeight('3rem') // Altura visual del logo dentro del panel.
            ->favicon(asset('/images/favicon.ico')) // Ícono mostrado en la pestaña del navegador.
            ->sidebarCollapsibleOnDesktop(true) // Permite contraer la barra lateral en escritorio.

            // Recursos, grupos y páginas disponibles.
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\\Filament\\Resources',
            ) // Descubre automáticamente todos los recursos CRUD de la aplicación.
            ->discoverClusters(
                in: app_path('Filament/Clusters'),
                for: 'App\\Filament\\Clusters',
            ) // Descubre los clústeres que agrupan recursos relacionados.
            ->pages($this->pages()) // Registra las páginas propias del panel.
            ->widgets($this->widgets()) // Registra los widgets visibles en el dashboard.
            ->navigationGroups($this->navigationGroups()) // Define el orden de los módulos del menú.

            // Sesión, seguridad y autenticación.
            ->middleware($this->middleware()) // Middleware requerido para cada petición del panel.
            ->authMiddleware([Authenticate::class]) // Impide el acceso de usuarios no autenticados.

            // Notificaciones, extensiones y menú del usuario.
            ->databaseNotifications() // Habilita notificaciones almacenadas en la base de datos.
            ->databaseNotificationsPolling('30s') // Consulta nuevas notificaciones cada 30 segundos.
            ->plugins($this->plugins()) // Activa permisos y el tema visual del sistema.
            ->userMenuItems($this->userMenuItems()); // Configura las opciones del menú del avatar.
    }

    /**
     * Páginas principales registradas manualmente en el panel.
     *
     * @return array<class-string>
     */
    private function pages(): array
    {
        return [
            Dashboard::class,
        ];
    }

    /**
     * Widgets que se muestran en la página principal del dashboard.
     *
     * @return array<class-string>
     */
    private function widgets(): array
    {
        return [
            EmpresaMailWidget::class,
        ];
    }

    /**
     * Orden de los grupos que aparecen en la navegación lateral.
     *
     * @return array<string>
     */
    private function navigationGroups(): array
    {
        return [
            'Recursos Humanos',
            'Contabilidad',
            'Compras',
            'Inventario',
            'Ventas',
            'Comercial',
            'Almacenes',
            'Configuración',
        ];
    }

    /**
     * Middleware aplicado a todas las rutas del panel.
     *
     * El orden es importante: primero se preparan cookies y sesión; después se
     * aplican seguridad, enlaces de rutas y componentes propios de Filament.
     *
     * @return array<class-string>
     */
    private function middleware(): array
    {
        return [
            EncryptCookies::class, // Cifra las cookies enviadas al navegador.
            AddQueuedCookiesToResponse::class, // Adjunta cookies pendientes a la respuesta.
            StartSession::class, // Inicia y restaura la sesión del usuario.
            AuthenticateSession::class, // Verifica que la sesión autenticada siga siendo válida.
            ShareErrorsFromSession::class, // Comparte errores de validación con las vistas.
            VerifyCsrfToken::class, // Protege formularios contra solicitudes CSRF.
            SubstituteBindings::class, // Resuelve automáticamente modelos desde parámetros de ruta.
            DisableBladeIconComponents::class, // Evita registrar componentes de íconos innecesarios.
            DispatchServingFilamentEvent::class, // Dispara el evento de inicialización de Filament.
        ];
    }

    /**
     * Extensiones activas para permisos y apariencia del panel.
     *
     * @return array<object>
     */
    private function plugins(): array
    {
        return [
            FilamentShieldPlugin::make(), // Gestiona roles y permisos de los recursos.
            DashStackThemePlugin::make(), // Aplica el tema visual personalizado.
        ];
    }

    /**
     * Opciones adicionales mostradas al abrir el menú del usuario.
     *
     * @return array<string, MenuItem>
     */
    private function userMenuItems(): array
    {
        return [
            'perfil' => MenuItem::make()
                ->label('Mi Perfil')
                ->icon('heroicon-o-user')
                ->url(fn (): string => $this->profileUrl()),
        ];
    }

    private function profileUrl(): string
    {
        // El menú también puede evaluarse durante procesos sin usuario autenticado.
        $email = Auth::user()?->email;

        if (! $email) {
            return '#';
        }

        // Relaciona al usuario con su perfil mediante el correo corporativo activo.
        $empleado = PerfilEmpleado::query()
            ->whereHas(
                'historialActivo',
                fn ($query) => $query->where('correo_corporativo', $email),
            )
            ->first();

        return $empleado
            ? PerfilEmpleadoResource::getUrl('edit', ['record' => $empleado->id])
            : '#';
    }
}
