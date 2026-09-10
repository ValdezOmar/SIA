<?php

namespace App\Providers\Filament;

use App\Models\Sistema\Parametro;
use Filament\Actions\Action;
use Filament\Auth\Pages\Login;

class GoogleAuthProvider extends Login
{
    /**
     * Conserva los campos y el envio nativos de Filament 4.
     *
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        $parametros = Parametro::query()->first();
        $googleActivo = (bool) ($parametros?->google_activo ?? false);
        $loginNativo = (bool) ($parametros?->login_nativo ?? true);
        $actions = [];

        if ($googleActivo) {
            $actions[] = Action::make('google-login')
                ->label('Ingresar con Google')
                ->icon('heroicon-o-check-badge')
                ->color('danger')
                ->url(route('google.redirect'));
        }

        if ($loginNativo) {
            $actions[] = $this->getAuthenticateFormAction();
        }

        return $actions;
    }

    public function getHeading(): string
    {
        return '';
    }
}
