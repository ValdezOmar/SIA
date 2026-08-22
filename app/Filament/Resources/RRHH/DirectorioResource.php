<?php

namespace App\Filament\Resources\RRHH;

use App\Models\RRHH\Directorio;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->modifyQueryUsing(fn ($query) => $query->where('activo', true))
            ->columns([
                ImageColumn::make('foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-avatar.jpg'))
                    ->width(50)
                    ->height(50)
                    ->extraAttributes([
                        'class' => 'cursor-pointer hover:opacity-75',
                        'x-on:click' => 'window.open($event.target.src, "_blank", "width=600,height=600")',
                    ]),

                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Directorio $record) => $record->apellidos),

                TextColumn::make('historialActivo.cargo.nombre')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('historialActivo.correo_corporativo')
                    ->label('Correo')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-envelope')
                    ->iconColor('primary'),

                TextColumn::make('historialActivo.numero_corporativo')
                    ->label('Teléfono')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->iconColor('primary'),

                TextColumn::make('historialActivo.sucursal.nombre')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'La Paz' => 'success',
                        'Cochabamba' => 'warning',
                        'Santa Cruz' => 'danger',
                        'Sucre' => 'info',
                        'Tarija' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('historialActivo.empresa.razon_social')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Novanexa' => 'success',
                        'Ireilab' => 'warning',
                        'Requilab' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->options(
                        \App\Models\Sistema\Empresa::query()
                            ->orderBy('razon_social')
                            ->pluck('razon_social', 'id')
                            ->toArray()
                    )
                    ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($query, $empresaId) => $query->whereHas('historialActivo', fn ($query) => $query->where('empresa_id', $empresaId)))),

                Tables\Filters\SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->options(
                        \App\Models\Sistema\Sucursal::query()
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->toArray()
                    )
                    ->query(fn ($query, array $data) => $query->when($data['value'] ?? null, fn ($query, $sucursalId) => $query->whereHas('historialActivo', fn ($query) => $query->where('sucursal_id', $sucursalId)))),
            ])
            ->actions([]) // Sin acciones de edición/eliminación
            ->recordUrl(null) // Desactiva el click en las filas
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->bulkActions([]); // Sin acciones masivas
    }

    // Permisos personalizados de filament shield
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
