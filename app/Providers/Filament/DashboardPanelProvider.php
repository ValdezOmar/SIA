<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Illuminate\Support\Facades\Auth;

class DashboardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            //RUTA PRINCIPAL MAIN
            ->default()
            ->id('dashboard')
            ->path('dashboard')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandName('SISTEMA INTEGRADO DE ADMINISTRACION')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,                
            ])

            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                \App\Filament\Widgets\NextcloudWidget::class,                
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            
            ->authMiddleware([
                Authenticate::class,
            ])

            // MENU DE GRUPOS DE LA APLICACION
            ->navigationGroups([
                'Recursos Humanos',
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

            // Se agrega correctamente el plugin de Shield
            ->plugins([
                FilamentShieldPlugin::make()
            ])   

            //asociacion de foto de perfil con avatar
            // ->userAvatarUrl(function ($user) {
            //     if ($user->empleado && $user->empleado->foto_url) {
            //         return $user->empleado->foto_url;
            //     }
            //     return null;
            // })

            //vista del perfil de empleado
            ->userMenuItems([
                'perfil' => \Filament\Navigation\MenuItem::make()
                    ->label('Mi Perfil')
                    ->url(function () {
                        $user = Auth::user();
                        $empleado = \App\Models\RRHH\Empleado::where('correo_corporativo', $user->email)->first();

                        return $empleado
                            ? \App\Filament\Resources\RRHH\DirectorioResource::getUrl('view', ['record' => $empleado->id])
                            : '#';
                    })
                    ->icon('heroicon-o-user'),
            ]);
    }
}
