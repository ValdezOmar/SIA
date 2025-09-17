<?php

namespace App\Providers\Filament;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login;
use Filament\Forms\Components\Actions;
use Filament\Actions\Action;
use App\Models\Sistema\Parametro;

class GoogleAuthProvider extends Login
{


    public function form(Form $form): Form
    {
        $parametros = Parametro::first();

        $components = [];

        if ($parametros->google_activo) {
            $components[] = \Filament\Forms\Components\Actions::make([
                \Filament\Forms\Components\Actions\Action::make('google-login')
                    ->label('Ingresar con Google')
                    ->icon('heroicon-o-check-badge')
                    ->color('danger')
                    ->size('lg')
                    ->action(function () {
                        return redirect()->route('google.redirect');
                    }),
            ])->fullWidth();
        }

        if ($parametros->login_nativo) {
            $components[] = Section::make('Login con email')
                ->collapsible($parametros->google_activo)
                ->collapsed()
                ->schema([
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->autocomplete('email'),

                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->required(),

                    Actions::make([
                        \Filament\Forms\Components\Actions\Action::make('login')
                            ->label('Entrar')
                            ->submit('login')
                            ->color('primary')
                            ->size('lg')
                            ->extraAttributes(['class' => 'w-full']),
                    ])->fullWidth(),
                ]);
        }

        return $form->schema($components);
    }
    //Deshabilitar el boton nativo de login
    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->hidden();  // Esto oculta el botón nativo
    }
    // Mensaje de bienvenida
    public function getHeading(): string
    {
        // return 'SIA'; //
        return '';
    }
}