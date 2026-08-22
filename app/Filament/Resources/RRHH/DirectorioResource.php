<?php

namespace App\Filament\Resources\RRHH;

use App\Models\RRHH\Directorio;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DirectorioResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Directorio::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $modelLabel = 'Directorio';

    protected static ?string $pluralModelLabel = 'Directorio de empleados';

    protected static ?string $navigationLabel = 'Directorio';

    protected static ?string $navigationGroup = 'Recursos Humanos';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->where('activo', true)
                ->with([
                    'historialActivo.empresa',
                    'historialActivo.cargo',
                    'historialActivo.sucursal',
                ]))
            ->columns([
                ImageColumn::make('foto_url')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-avatar.jpg'))
                    ->size(48),
                TextColumn::make('nombres')
                    ->label('Empleado')
                    ->formatStateUsing(fn (Directorio $record): string => $record->full_name)
                    ->searchable(['nombres', 'apellidos', 'ci'])
                    ->sortable(['apellidos', 'nombres'])
                    ->description(fn (Directorio $record): string => "CI: {$record->ci}"),
                TextColumn::make('historialActivo.cargo.nombre')
                    ->label('Cargo')
                    ->placeholder('Sin asignar')
                    ->description(fn (Directorio $record): string => $record->historialActivo?->tipo_contrato ?? 'Sin contrato vigente'),
                TextColumn::make('historialActivo.empresa.razon_social')
                    ->label('Empresa')
                    ->badge()
                    ->color('primary')
                    ->placeholder('Sin asignar')
                    ->toggleable(),
                TextColumn::make('historialActivo.sucursal.nombre')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info')
                    ->placeholder('Sin asignar'),
                TextColumn::make('historialActivo.correo_corporativo')
                    ->label('Contacto corporativo')
                    ->icon('heroicon-o-envelope')
                    ->iconColor('primary')
                    ->url(fn (Directorio $record): ?string => $record->historialActivo?->correo_corporativo ? "mailto:{$record->historialActivo->correo_corporativo}" : null)
                    ->copyable()
                    ->copyMessage('Correo copiado')
                    ->description(fn (Directorio $record): string => $record->historialActivo?->numero_corporativo ?: 'Sin teléfono corporativo')
                    ->placeholder('Sin contacto asignado')
                    ->toggleable(),
                IconColumn::make('historialActivo.id')
                    ->label('Vínculo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (Directorio $record): string => $record->historialActivo ? 'Contrato laboral vigente' : 'Sin contrato laboral vigente'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->options(Empresa::query()->where('empresa_activo', true)->orderBy('razon_social')->pluck('razon_social', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $empresaId): Builder => $query->whereHas('historialActivo', fn (Builder $query): Builder => $query->where('empresa_id', $empresaId)))),
                Tables\Filters\SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->options(Sucursal::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when($data['value'] ?? null, fn (Builder $query, $sucursalId): Builder => $query->whereHas('historialActivo', fn (Builder $query): Builder => $query->where('sucursal_id', $sucursalId)))),
                Tables\Filters\TernaryFilter::make('con_vinculo_laboral')
                    ->label('Vínculo laboral')
                    ->placeholder('Todos')
                    ->trueLabel('Con contrato vigente')
                    ->falseLabel('Sin contrato vigente')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('historialActivo'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('historialActivo'),
                    ),
            ])
            ->actions([])
            ->recordUrl(null)
            ->defaultSort('apellidos')
            ->emptyStateHeading('No hay empleados activos')
            ->emptyStateDescription('Los empleados activos aparecerán aquí cuando se registren en Recursos Humanos.')
            ->emptyStateIcon('heroicon-o-user-group')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->striped()
            ->bulkActions([]);
    }

    public static function getPermissionPrefixes(): array
    {
        return ['view_any'];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\RRHH\DirectorioResource\Pages\ListDirectorio::route('/'),
        ];
    }
}
