<?php

namespace App\Filament\Resources\RRHH;

use App\Models\RRHH\Directorio;
use App\Models\RRHH\Empleado;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class DirectorioResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Directorio::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $modelLabel = 'Directorio';
    protected static ?string $pluralModelLabel = 'Directorio de Empleados';
    protected static ?string $navigationLabel = 'Directorio';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            // Filtra solo empleados activos
            ->modifyQueryUsing(fn($query) => $query->where('activo', true))
            ->columns([
                ImageColumn::make('foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-avatar.jpg'))
                    ->width(50)
                    ->height(50)
                    ->extraAttributes([
                        'class' => 'cursor-pointer hover:opacity-75',
                        'x-on:click' => 'window.open($event.target.src, "_blank", "width=600,height=600")'
                    ]),

                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Directorio $record) => $record->apellidos),


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
                    ->label('Empresa')
                    ->options(
                        Empleado::query()
                            ->select('empresa')
                            ->distinct()
                            ->orderBy('empresa')
                            ->pluck('empresa', 'empresa')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('sucursal')
                    ->label('Sucursal')
                    ->options(
                        Empleado::query()
                            ->select('sucursal')
                            ->distinct()
                            ->orderBy('sucursal')
                            ->pluck('sucursal', 'sucursal')
                            ->toArray()
                    ),
            ])
            ->actions([]) // Sin acciones de edición/eliminación
            ->recordUrl(null) // Desactiva el click en las filas
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->bulkActions([]); // Sin acciones masivas
    }

    //Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales  
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RRHH\DirectorioResource\Pages\ListDirectorio::route('/'),
        ];
    }
}