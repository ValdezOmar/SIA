<?php

namespace App\Filament\Resources\RRHH;

use App\Models\RRHH\Empleado;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;

class DirectorioResource extends Resource
{
    protected static ?string $model = Empleado::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Directorio';
    protected static ?string $pluralModelLabel = 'Directorio de Empleados';
    protected static ?string $navigationLabel = 'Directorio';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-avatar.jpg'))
                    ->width(50)
                    ->height(50),

                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Empleado $record) => $record->apellidos),


                TextColumn::make('cargo')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('correo_corporativo')
                    ->label('Correo')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->iconColor('primary'),

                TextColumn::make('numero_corporativo')
                    ->label('Teléfono')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->iconColor('primary'),

                TextColumn::make('sucursal')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'La Paz' => 'success',
                        'Cochabamba' => 'warning',
                        'Santa Cruz' => 'danger',
                        'Sucre' => 'info',
                        'Tarija' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('empresa')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Novanexa' => 'success',
                        'Ireilab' => 'warning',
                        'Requilab' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('empresa')
                    ->options([
                        'Novanexa' => 'Novanexa',
                        'Ireilab' => 'Ireilab',
                        'Requilab' => 'Requilab',
                    ]),

                Tables\Filters\SelectFilter::make('sucursal')
                    ->options([
                        'La Paz' => 'La Paz',
                        'Santa Cruz' => 'Santa Cruz',
                        'Cochabamba' => 'Cochabamba',
                        'Oruro' => 'Oruro',
                        //'Potosí' => 'Potosí',
                        'Tarija' => 'Tarija',
                        'Sucre' => 'Sucre',
                        //'Beni' => 'Beni',
                        //'Pando' => 'Pando',
                    ]),
            ])
            ->actions([]) // Sin acciones de edición/eliminación
            ->bulkActions([]); // Sin acciones masivas
    }

    // Prefijo de permisos
    protected static function getPermissionPrefix(): string
    {
        return 'directorio_';
    }

    public static function shouldRegisterShieldPermissions(): bool
    {
        return false; // Desactiva generación automática de permisos
    }
    //Premisos de acceso al directorio para que todos puedan ver
    public static function canViewAny(): bool
    {
        return true; // Todos pueden ver este recurso
    }
    public static function canCreate(): bool
    {
        return false; // Nadie puede crear en el directorio
    }

    public static function canEdit($record): bool
    {
        return false; // Nadie puede editar en el directorio
    }

    public static function canDelete($record): bool
    {
        return false; // Nadie puede eliminar en el directorio
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RRHH\DirectorioResource\Pages\ListDirectorio::route('/'),
            'view' => \App\Filament\Resources\RRHH\DirectorioResource\Pages\ViewDirectorioEmpleado::route('/{record}'),
        ];
    }
}
