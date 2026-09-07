<?php

namespace App\Support;

use App\Models\Ventas\Cliente;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ClienteRegistroForm
{
    public static function schema(): array
    {
        return [
            Section::make('Datos principales')
                ->description('Ingrese el nombre y el celular. El código se genera automáticamente.')
                ->icon('heroicon-o-user-plus')
                ->columns(2)
                ->schema([
                    TextInput::make('codigo')
                        ->label('Código')->disabled()
                        ->default(fn () => Cliente::generarCodigo())
                        ->prefixIcon('heroicon-o-hashtag'),
                    TextInput::make('nombre')
                        ->label('Nombre / Razón social')->required()->maxLength(255)
                        ->placeholder('Ej: JUAN QUISPE')->prefixIcon('heroicon-o-user'),
                    TextInput::make('celular')
                        ->label('Celular / WhatsApp')->tel()->required()->maxLength(50)
                        ->placeholder('Ej: 71234567')
                        ->helperText('Si el celular ya está registrado, se seleccionará el cliente existente.')
                        ->dehydrateStateUsing(fn ($state) => Cliente::normalizarCelular($state))
                        ->prefixIcon('heroicon-o-device-phone-mobile'),
                    TextInput::make('ci/nit')
                        ->label('CI / NIT')->maxLength(50)
                        ->placeholder('Documento de identidad o NIT')->prefixIcon('heroicon-o-identification'),
                ]),
            Section::make('Contacto adicional')
                ->description('Datos opcionales para comunicarse con el cliente.')
                ->columns(2)
                ->schema([
                    TextInput::make('telefono')->label('Teléfono')->tel()->maxLength(50)
                        ->placeholder('Ej: 2 2345678')->prefixIcon('heroicon-o-phone'),
                    TextInput::make('correo')->label('Correo electrónico')->email()->maxLength(255)
                        ->placeholder('cliente@email.com')->prefixIcon('heroicon-o-envelope'),
                ]),
            Section::make('Ubicación')
                ->columns(2)
                ->schema([
                    Select::make('ciudad')->label('Departamento')
                        ->options([
                            'BENI' => 'Beni', 'CHUQUISACA' => 'Chuquisaca',
                            'COCHABAMBA' => 'Cochabamba', 'LA PAZ' => 'La Paz',
                            'ORURO' => 'Oruro', 'PANDO' => 'Pando', 'POTOSÍ' => 'Potosí',
                            'SANTA CRUZ' => 'Santa Cruz', 'TARIJA' => 'Tarija',
                        ])->searchable()->placeholder('Seleccione un departamento')
                        ->prefixIcon('heroicon-o-map-pin'),
                    TextInput::make('zona')->label('Zona / Barrio')->maxLength(100)
                        ->placeholder('Ej: Centro'),
                    Textarea::make('direccion')->label('Dirección y referencias')->rows(2)
                        ->placeholder('Calle, número y referencia para la entrega')->columnSpanFull(),
                ]),
            Section::make('Datos comerciales')
                ->columns(2)
                ->schema([
                    Select::make('tipo_cliente')->label('Tipo de cliente')
                        ->options([
                            'persona_natural' => 'Persona natural', 'empresa' => 'Empresa',
                            'gobierno' => 'Gobierno', 'extranjero' => 'Extranjero',
                        ])->default('persona_natural'),
                    TextInput::make('condicion_pago')->label('Condición de pago')->maxLength(100)
                        ->placeholder('Ej: Contado')->prefixIcon('heroicon-o-credit-card'),
                    Toggle::make('activo')->label('Cliente activo')->default(true)->columnSpanFull(),
                ]),
        ];
    }
}
