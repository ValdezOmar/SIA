<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Support\Facades\Auth;
use Nuxtifyts\DashStackTheme\DashStackThemePlugin;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            //PErsonalizacion de sistema
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->colors([
                'primary'       => '#009BA4', // principal (botones, enlaces, etc.)
                'primary-hover' => '#51CDD2', // hover o acento
                'secondary'     => '#3066BE', // secundario
                'accent'        => '#6D9DC5', // campos activos, bordes, detalles
                'muted'         => '#AEECEF', // fondos suaves, elementos pasivosFFF
            ])
            // ->brandName('SISTEMA INTEGRADO DE ADMINISTRACION')
            ->brandLogo(asset('images/logo.svg'))// Logo que se muestra en la esquina superior izquierda del panel
            ->brandLogoHeight('2.1rem')// Altura del logo
            ->sidebarCollapsibleOnDesktop(true)// Permite que la barra lateral (sidebar) sea colapsable en escritorio
            //Gestion de rutas automaticasr
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources') // Descubre y registra automáticamente los recursos (CRUDs) dentro de app/Filament/Resources
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')// Descubre y registra automáticamente las páginas personalizadas en app/Filament/Pages
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Pages\Dashboard::class,
            ])
            //->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\NextcloudWidget::class,
                \App\Filament\Widgets\EstadoVencimientoChart::class,
                ])
                
            //Gestion de los middelewares
            ->middleware([
                EncryptCookies::class,                // Cifra las cookies para seguridad
                AddQueuedCookiesToResponse::class,   // Añade cookies pendientes a la respuesta HTTP
                StartSession::class,                  // Inicia la sesión del usuario
                AuthenticateSession::class,           // Middleware para autenticar sesión
                ShareErrorsFromSession::class,       // Comparte errores desde la sesión para mostrar en vistas
                VerifyCsrfToken::class,               // Verifica token CSRF para proteger formularios
                SubstituteBindings::class,            // Reemplaza bindings de rutas
                DisableBladeIconComponents::class,   // Deshabilita componentes Blade para íconos (optimización)
                DispatchServingFilamentEvent::class, // Evento disparado cuando Filament sirve una página
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            // MENU DE GRUPOS DE LA APLICACION
            ->navigationGroups([
                'Recursos Humanos',
                'Almacenes',
                'Configuración',
                // ... otros grupos
            ])
            //RUTA FAVICON
            ->favicon(asset('images/favicon.ico'))
            //Spatie configuration
            ->login(GoogleAuthProvider::class)
            ->authGuard('web')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            // GEstion de los plugins
            ->plugins([
                FilamentShieldPlugin::make(),
                DashStackThemePlugin::make()
            ])
            //Items del menu superior del avatar
            ->userMenuItems([
                'perfil' => \Filament\Navigation\MenuItem::make()
                    ->label('Mi Perfil')
                    ->url(function () {
                        $user = Auth::user();
                        $empleado = \App\Models\RRHH\PerfilEmpleado::where('correo_corporativo', $user->email)->first();

                        return $empleado
                            ? \App\Filament\Resources\RRHH\PerfilEmpleadoResource::getUrl('view', ['record' => $empleado->id])
                            : '#';
                    })
                    ->icon('heroicon-o-user'),
            ]);
    }
}