<?php

namespace App\Filament\Resources\Inventario;

use Exception;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
use App\Filament\Resources\Inventario\StockAlmacenResource\Pages\EditStockAlmacens;
use App\Filament\Resources\Inventario\StockAlmacenResource\Pages\ListStockAlmacens;
use App\Filament\Resources\Inventario\StockAlmacenResource\RelationManagers\ArticulosStockAlmacenRelationManager;
use App\Models\Inventario\Almacen;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;

class StockAlmacenResource extends Resource
{
    protected static ?string $model = Almacen::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Stock de Almacenes';

    protected static ?string $modelLabel = 'Almacén';

    protected static ?string $pluralModelLabel = 'Stock de Almacenes';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return AlmacenResource::getEloquentQuery();
    }

    private static function ubicacionesTieneAlmacenId(): bool
    {
        if (! Schema::hasTable('alm_ubicaciones')) {
            return false;
        }
        try {
            return in_array('almacen_id', Schema::getColumnListing('alm_ubicaciones'));
        } catch (Exception $e) {
            return false;
        }
    }

    private static function existenciasTieneAlmacenId(): bool
    {
        if (! Schema::hasTable('alm_existencias')) {
            return false;
        }
        try {
            return in_array('almacen_id', Schema::getColumnListing('alm_existencias'));
        } catch (Exception $e) {
            return false;
        }
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            Section::make('Resumen del almacén')
                ->description('Este módulo consulta disponibilidad por artículo. Las cantidades se registran desde Compras, Ventas o Kardex para conservar su historial.')
                ->icon('heroicon-o-building-storefront')
                ->columnSpanFull()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('codigo')->label('Código')->disabled()->columnSpan(1),
                        TextInput::make('nombre')->label('Nombre del almacén')->disabled()->columnSpan(1),
                        TextInput::make('sucursal.nombre')->label('Sucursal')->disabled()->visible(fn () => Schema::hasTable('conf_sucursales'))->columnSpan(1),
                    ]),
                    Textarea::make('direccion')->label('Ubicación física')->disabled()->rows(2)->placeholder('Sin dirección registrada')->columnSpanFull(),
                ]),
        ]);
    }
    public static function table(Table $table): Table
    {
        $ubicacionesExiste = self::ubicacionesTieneAlmacenId();
        $existenciasExiste = self::existenciasTieneAlmacenId();

        $columns = [
            TextColumn::make('codigo')
                ->label('Código')
                ->searchable()
                ->sortable()
                ->copyable()
                ->copyMessage('Código copiado')
                ->toggleable()
                ->weight('bold')
                ->placeholder('-'),

            TextColumn::make('nombre')
                ->label('Nombre')
                ->searchable()
                ->sortable()
                ->toggleable()
                ->placeholder('-'),

            TextColumn::make('sucursal.nombre')
                ->label('Sucursal')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('info')
                ->toggleable()
                ->placeholder('-')
                ->visible(fn () => Schema::hasTable('conf_sucursales')),

        ];

        if ($ubicacionesExiste) {
            $columns[] = TextColumn::make('ubicaciones_count')
                ->label('Ubicaciones')
                ->counts('ubicaciones')
                ->badge()
                ->color('warning')
                ->toggleable();
        }

        if ($existenciasExiste) {
            $columns[] = TextColumn::make('existencias_count')
                ->label('Stock')
                ->counts('existencias')
                ->badge()
                ->color('success')
                ->toggleable();
        }

        $columns[] = IconColumn::make('activo')
            ->label('Estado')
            ->boolean()
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle')
            ->trueColor('success')
            ->falseColor('danger')
            ->toggleable();

        $columns[] = TextColumn::make('created_at')
            ->label('Creado')
            ->dateTime('d/m/Y H:i')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        $columns[] = TextColumn::make('updated_at')
            ->label('Actualizado')
            ->dateTime('d/m/Y H:i')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);

        return $table
            ->columns($columns)
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),

                SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Schema::hasTable('conf_sucursales')),
            ])           
           
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar almacén...')
            ->emptyStateHeading('No hay almacenes registrados')
            ->emptyStateDescription('Crea tu primer almacén para comenzar a gestionar tu inventario.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            ArticulosStockAlmacenRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockAlmacens::route('/'),
            'edit' => EditStockAlmacens::route('/{record}/edit'),
        ];
    }
}
