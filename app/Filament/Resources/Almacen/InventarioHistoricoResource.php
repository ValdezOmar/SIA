<?php

namespace App\Filament\Resources\Almacen;

use App\Filament\Resources\Almacen\InventarioHistoricoResource\Pages\ListInventariosHistoricos;
use App\Models\Almacen\Inventario;
use App\Models\Inventario\Almacen;
use App\Models\Sistema\Empresa;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventarioHistoricoResource extends Resource
{
    protected static ?string $model = Inventario::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $pluralModelLabel = 'Registros del inventario anterior';

    public static function canViewAny(): bool
    {
        return InventarioResource::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->when($user->empresa_id, function ($query, $id) {
                $empresa = Empresa::find($id);
                $query->whereIn('empresa', array_filter([$empresa?->nombre_comercial, $empresa?->razon_social]));
            })
            ->when($user->sucursal_id, fn ($query, $id) => $query->whereIn('nombre_almacen', Almacen::where('sucursal_id', $id)->pluck('nombre')));
    }

    public static function table(Table $table): Table
    {
        return $table->description('Archivo de solo lectura. Conserva los datos del módulo anterior, incluidos registros inactivos. La empresa y el almacén se identificaban por nombre; los registros sin correspondencia deben ser revisados por un administrador.')
            ->columns([
                TextColumn::make('fecha_conteo_inventario')->label('Fecha')->date('d/m/Y')->sortable(),
                TextColumn::make('empresa')->label('Empresa')->searchable(),
                TextColumn::make('nombre_almacen')->label('Almacén')->searchable(),
                TextColumn::make('codigo')->label('Código')->searchable(),
                TextColumn::make('descripcion')->label('Artículo')->searchable()->wrap(),
                TextColumn::make('saldo_actual')->label('Referencia')->numeric(2),
                TextColumn::make('saldo_contado')->label('Contado')->numeric(2)->placeholder('Sin conteo'),
                TextColumn::make('sn_qr_correcto')->label('QR leído')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('observacion')->label('Observaciones')->wrap(),
                TextColumn::make('usuario')->label('Usuario'),
            ])->filters([
                SelectFilter::make('empresa')->label('Empresa')->options(fn () => static::getEloquentQuery()->whereNotNull('empresa')->distinct()->pluck('empresa', 'empresa')),
            ])->actions([])->bulkActions([])->defaultSort('fecha_conteo_inventario', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListInventariosHistoricos::route('/')];
    }
}
