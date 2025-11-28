<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\Pages;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\RelationManagers;
use App\Models\HelpDesk\Cliente;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;
    protected static ?string $cluster = HelpDesk::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Parámetros';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?string $navigationDescription = 'Gestiona la información de los clientes de la organización';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información Básica del Cliente')
                    ->description('Complete la información principal del cliente')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextInput::make('razon_social')
                            ->label('Razón Social')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Empresa XYZ S.A.')
                            ->columnSpanFull()
                            ->helperText('Nombre legal completo de la empresa o institución')
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('razon_social', strtoupper($state));
                            })
                            ->live(onBlur: true),

                        TextInput::make('ci_nit')
                            ->label('NIT')
                            ->maxLength(50)
                            ->placeholder('Ej: 123456789')
                            ->helperText('Número de identificación tributaria o cédula de identidad'),
                    ]),

                Section::make('Información de Contacto')
                    ->description('Datos para comunicación con el cliente')
                    ->icon('heroicon-o-envelope')
                    ->schema([
                        TextInput::make('telefono')
                            ->tel()
                            ->prefixIcon('heroicon-o-phone')
                            ->placeholder('Ej: +591 12345678'),

                        TextInput::make('correo')
                            ->email()
                            ->prefixIcon('heroicon-o-envelope')
                            ->placeholder('Ej: contacto@empresa.com'),

                        TextInput::make('direccion')
                            ->label('Dirección')
                            ->prefixIcon('heroicon-o-map-pin')
                            ->placeholder('Dirección completa')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Información Adicional')
                    ->required()
                    ->description('Detalles complementarios del cliente')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Select::make('tipo_institucion')
                            ->label('Tipo de Institución')
                            ->placeholder('Seleccione el tipo de institución')
                            ->helperText('Clasificación del tipo de organización en Bolivia')
                            ->options([
                                'EMPRESA PRIVADA' => 'Empresa Privada',
                                'EMPRESA PUBLICA' => 'Empresa Pública',
                                'HOSPITAL PUBLICO' => 'Hospital Público',
                                'HOSPITAL PRIVADO' => 'Hospital Privado',
                                'EMPRESA MIXTA' => 'Empresa Mixta',
                                'GOBIERNO NACIONAL' => 'Gobierno Nacional',
                                'GOBIERNO DEPARTAMENTAL' => 'Gobierno Departamental',
                                'GOBIERNO MUNICIPAL' => 'Gobierno Municipal',
                                'UNIVERSIDAD PUBLICA' => 'Universidad Pública',
                                'UNIVERSIDAD PRIVADA' => 'Universidad Privada',
                                'ORGANISMO INTERNACIONAL' => 'Organismo Internacional',
                                'CLINICA' => 'Clínica',
                                'CONSULTORA' => 'Consultora',
                                'OTRO' => 'Otro',
                            ])
                            ->searchable()
                            ->preload()
                            ->native(false),

                        TextInput::make('ciudad')
                            ->label('DepartamentoX')
                            ->required()
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->placeholder('Ej: La Paz, Santa Cruz, Cochabamba')
                            ->formatStateUsing(fn($state) => $state ? ucwords(strtolower($state)) : '')
                            ->dehydrateStateUsing(fn($state) => $state ? ucwords(strtolower($state)) : null),

                        Textarea::make('observaciones')
                            ->rows(3)
                            ->placeholder('Notas adicionales sobre el cliente')
                            ->helperText('Información relevante para el equipo de soporte')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make('Estado del Cliente')
                    ->description('Control de activación del cliente en el sistema')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        Toggle::make('activo')
                            ->label('Cliente Activo')
                            ->default(true)
                            ->onColor('success')
                            ->offColor('danger')
                            ->helperText('Desactive para ocultar este cliente en el sistema'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('razon_social')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Cliente $record): string => $record->ci_nit ?? 'Sin NIT/CI')
                    ->weight('semibold')
                    ->icon('heroicon-o-building-office'),

                TextColumn::make('telefono')
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('correo')
                    ->icon('heroicon-o-envelope')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('ciudad')
                    ->icon('heroicon-o-map-pin')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('tipo_institucion')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'EMPRESA PRIVADA' => 'Empresa Privada',
                        'EMPRESA PUBLICA' => 'Empresa Pública',
                        'HOSPITAL PUBLICO' => 'Hospital Público',
                        'HOSPITAL PRIVADO' => 'Hospital Privado',
                        'EMPRESA MIXTA' => 'Empresa Mixta',
                        'GOBIERNO NACIONAL' => 'Gobierno Nacional',
                        'GOBIERNO DEPARTAMENTAL' => 'Gobierno Departamental',
                        'GOBIERNO MUNICIPAL' => 'Gobierno Municipal',
                        'UNIVERSIDAD PUBLICA' => 'Universidad Pública',
                        'UNIVERSIDAD PRIVADA' => 'Universidad Privada',
                        'ORGANISMO INTERNACIONAL' => 'Organismo Internacional',
                        'CLINICA' => 'Clínica',
                        'CONSULTORA' => 'Consultora',
                        'OTRO' => 'Otro',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        // Instituciones Privadas - Verde
                        'EMPRESA PRIVADA' => 'success',
                        'HOSPITAL PRIVADO' => 'success',
                        'UNIVERSIDAD PRIVADA' => 'success',
                        'CLINICA' => 'success',
                        'CONSULTORA' => 'success',

                        // Instituciones Públicas - Azul
                        'EMPRESA PUBLICA' => 'primary',
                        'HOSPITAL PUBLICO' => 'primary',
                        'GOBIERNO NACIONAL' => 'primary',
                        'GOBIERNO DEPARTAMENTAL' => 'primary',
                        'GOBIERNO MUNICIPAL' => 'primary',
                        'UNIVERSIDAD PUBLICA' => 'primary',

                        // Mixtas e Internacionales - Púrpura
                        'EMPRESA MIXTA' => 'purple',
                        'ORGANISMO INTERNACIONAL' => 'purple',

                        // Otros - Gris
                        'OTRO' => 'gray',

                        default => 'gray',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Estado Activo')
                    ->placeholder('Todos los clientes')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                SelectFilter::make('tipo_institucion')
                    ->label('Tipo de Institución')
                    ->options([
                        'Empresa Privada' => 'Empresa Privada',
                        'Institución Pública' => 'Institución Pública',
                        'ONG' => 'ONG',
                        'Educativa' => 'Institución Educativa',
                    ])
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->color('blue'),
                    EditAction::make()
                        ->color('emerald'),
                ])
                    ->label('Acciones')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->size('sm'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Eliminar seleccionados'),
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn($records) => $records->each->update(['activo' => true])),
                    Tables\Actions\BulkAction::make('desactivar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn($records) => $records->each->update(['activo' => false])),
                ]),
            ])
            ->emptyStateHeading('No hay clientes registrados')
            ->emptyStateDescription('Comienza creando tu primer cliente.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Cliente')
                    ->icon('heroicon-o-plus'),
            ])
            ->deferLoading()
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}