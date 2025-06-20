<?php

namespace App\Providers\Filament;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login;
use Filament\Forms\Components\Actions;
use Filament\Actions\Action;

class GoogleAuthProvider extends Login
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Login con Google')
                    ->schema([
                        \Filament\Forms\Components\Actions::make([
                            \Filament\Forms\Components\Actions\Action::make('google-login')
                                ->label('Continuar con Google')
                                //->icon('fab-google')
                                ->color('danger')
                                ->size('lg')
                                ->action(function () {
                                    return redirect()->route('google.redirect');
                                }),
                        ])->fullWidth(),
                    ]),

                Section::make('Login con email')
                    ->collapsible()
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
                                ->submit('login') // Nombre del formulario
                                ->color('primary')
                                ->size('lg')
                                ->extraAttributes(['class' => 'w-full']),
                        ])->fullWidth(),
                    ]),
            ]);
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