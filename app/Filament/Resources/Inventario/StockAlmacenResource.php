<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\StockAlmacenResource\Pages\ListStockAlmacens;
use App\Filament\Resources\Inventario\StockAlmacenResource\Pages\EditStockAlmacens;
use App\Filament\Resources\Inventario\StockAlmacenResource\RelationManagers\ArticulosStockRelationManager;
use App\Models\Inventario\Almacen;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Stock Almacenes';

    protected static ?string $modelLabel = 'Almacén';

    protected static ?string $pluralModelLabel = 'Almacenes';

    protected static ?int $navigationSort = 2;

    private static function ubicacionesTieneAlmacenId(): bool
    {
        if (!Schema::hasTable('alm_ubicaciones')) return false;
        try {
            return in_array('almacen_id', Schema::getColumnListing('alm_ubicaciones'));
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function existenciasTieneAlmacenId(): bool
    {
        if (!Schema::hasTable('alm_existencias')) return false;
        try {
            return in_array('almacen_id', Schema::getColumnListing('alm_existencias'));
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos del Almacén')
                                    ->icon('heroicon-o-building-storefront')
                                    ->description('Información principal del almacén')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->disabled()
                                                    ->placeholder('Sin código registrado')
                                                    ->helperText('Código único del almacén')
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->formatStateUsing(fn($state) => $state ?? 'Sin datos registrados')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre')
                                                    ->label('Nombre')
                                                    ->disabled()
                                                    ->placeholder('Sin nombre registrado')
                                                    ->helperText('Nombre del almacén')
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->formatStateUsing(fn($state) => $state ?? 'Sin datos registrados')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('sucursal.nombre')
                                                    ->label('Sucursal')
                                                    ->disabled()
                                                    ->placeholder('Sin sucursal asignada')
                                                    ->helperText('Sucursal a la que pertenece')
                                                    ->prefixIcon('heroicon-o-map-pin')
                                                    ->formatStateUsing(fn($state) => $state ?? 'Sin datos registrados')
                                                    ->visible(fn() => Schema::hasTable('conf_sucursales'))
                                                    ->columnSpan(1),

                                                Textarea::make('direccion')
                                                    ->label('Dirección')
                                                    ->disabled()
                                                    ->placeholder('Sin dirección registrada')
                                                    ->rows(3)
                                                    ->helperText('Ubicación física del almacén')
                                                    ->formatStateUsing(fn($state) => $state ?? 'Sin datos registrados')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->default(true)
                                                    ->helperText('Estado del almacén')
                                                    ->formatStateUsing(fn($state) => $state ? '🟢 Activo' : '🔴 Inactivo')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Ubicaciones')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Ubicaciones del Almacén')
                                    ->icon('heroicon-o-map-pin')
                                    ->description('Organización espacial del almacén')
                                    ->schema([
                                        Placeholder::make('ubicaciones_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new HtmlString(
                                                        '<div class="text-sm text-gray-500 dark:text-gray-400">Guardar el almacén para gestionar ubicaciones.</div>'
                                                    );
                                                }

                                                try {
                                                    $totalUbicaciones = $record->ubicaciones()->count();
                                                    $ubicacionesActivas = $record->ubicaciones()->where('activo', true)->count();

                                                    if ($totalUbicaciones === 0) {
                                                        return new HtmlString(
                                                            '<div class="bg-yellow-50 dark:bg-yellow-900/20 p-4 rounded-xl border border-yellow-200 dark:border-yellow-800">
                                                                <div class="flex items-center gap-2">
                                                                    <span class="text-2xl">📍</span>
                                                                    <div>
                                                                        <p class="text-sm text-yellow-700 dark:text-yellow-400">No hay ubicaciones registradas</p>
                                                                        <p class="text-xs text-yellow-600 dark:text-yellow-500 mt-1">Gestiona las ubicaciones en la pestaña "Ubicaciones" en relaciones.</p>
                                                                    </div>
                                                                </div>
                                                            </div>'
                                                        );
                                                    }

                                                    return new HtmlString(
                                                        '<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div class="text-center">
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Ubicaciones</p>
                                                                    <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">' . $totalUbicaciones . '</p>
                                                                </div>
                                                                <div class="text-center">
                                                                    <p class="text-sm text-gray-600 dark:text-gray-400">Ubicaciones Activas</p>
                                                                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">' . $ubicacionesActivas . '</p>
                                                                </div>
                                                            </div>
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2">Gestiona las ubicaciones en la sección de relaciones.</p>
                                                        </div>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return new HtmlString('<div class="text-sm text-gray-500">Error al cargar ubicaciones.</div>');
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Estadísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen del Almacén')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Placeholder::make('estadisticas')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new HtmlString(
                                                        '<div class="text-sm text-gray-500 dark:text-gray-400">Guardar el almacén para ver estadísticas.</div>'
                                                    );
                                                }

                                                try {
                                                    $totalArticulos = 0;
                                                    $totalMovimientos = 0;
                                                    $totalExistencias = 0;
                                                    $totalUbicaciones = $record->ubicaciones()->count();

                                                    if (self::existenciasTieneAlmacenId()) {
                                                        $totalExistencias = $record->existencias()->sum('cantidad_disponible');
                                                        $totalArticulos = $record->existencias()->distinct('articulo_id')->count();
                                                    }

                                                    if (Schema::hasTable('alm_movimientos_inventario')) {
                                                        $totalMovimientos = $record->movimientos()->count();
                                                    }

                                                    $noData = $totalArticulos === 0 && $totalExistencias === 0 && $totalMovimientos === 0 && $totalUbicaciones === 0;

                                                    if ($noData) {
                                                        return new HtmlString(
                                                            '<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 text-center">
                                                                <span class="text-4xl block mb-2">📊</span>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">Sin datos estadísticos disponibles</p>
                                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Agrega productos y movimientos para ver estadísticas.</p>
                                                            </div>'
                                                        );
                                                    }

                                                    return new HtmlString(
                                                        '<div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                            <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-xl border border-blue-200 dark:border-blue-800 text-center">
                                                                <p class="text-sm text-blue-600 dark:text-blue-400 font-medium">Artículos</p>
                                                                <p class="text-2xl font-bold text-blue-900 dark:text-blue-100">' . number_format($totalArticulos) . '</p>
                                                                <p class="text-xs text-blue-500 dark:text-blue-400 mt-1">Productos en stock</p>
                                                            </div>
                                                            <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-xl border border-green-200 dark:border-green-800 text-center">
                                                                <p class="text-sm text-green-600 dark:text-green-400 font-medium">Existencias</p>
                                                                <p class="text-2xl font-bold text-green-900 dark:text-green-100">' . number_format($totalExistencias, 0) . '</p>
                                                                <p class="text-xs text-green-500 dark:text-green-400 mt-1">Unidades disponibles</p>
                                                            </div>
                                                            <div class="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-xl border border-purple-200 dark:border-purple-800 text-center">
                                                                <p class="text-sm text-purple-600 dark:text-purple-400 font-medium">Movimientos</p>
                                                                <p class="text-2xl font-bold text-purple-900 dark:text-purple-100">' . number_format($totalMovimientos) . '</p>
                                                                <p class="text-xs text-purple-500 dark:text-purple-400 mt-1">Transacciones registradas</p>
                                                            </div>
                                                            <div class="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-xl border border-orange-200 dark:border-orange-800 text-center">
                                                                <p class="text-sm text-orange-600 dark:text-orange-400 font-medium">Ubicaciones</p>
                                                                <p class="text-2xl font-bold text-orange-900 dark:text-orange-100">' . number_format($totalUbicaciones) . '</p>
                                                                <p class="text-xs text-orange-500 dark:text-orange-400 mt-1">Espacios físicos</p>
                                                            </div>
                                                        </div>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return new HtmlString('<div class="text-sm text-gray-500">Error al cargar estadísticas.</div>');
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull(),
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
                ->visible(fn() => Schema::hasTable('conf_sucursales')),

            TextColumn::make('direccion')
                ->label('Dirección')
                ->searchable()
                ->toggleable()
                ->limit(30)
                ->placeholder('-'),
        ];

        if ($ubicacionesExiste) {
            $columns[] = TextColumn::make('ubicaciones_count')
                ->label('📍 Ubicaciones')
                ->counts('ubicaciones')
                ->badge()
                ->color('warning')
                ->toggleable();
        }

        if ($existenciasExiste) {
            $columns[] = TextColumn::make('existencias_count')
                ->label('📦 Stock')
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

                Tables\Filters\SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => Schema::hasTable('conf_sucursales')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->label('Ver Detalles'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->codigo = $record->codigo . '-COPY-' . time();
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Almacén duplicado exitosamente')
                                ->success()
                                ->send();
                        }),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_active_bulk')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->action(fn($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado de almacenes'),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('🔍 Buscar almacén...')
            ->emptyStateHeading('📦 No hay almacenes registrados')
            ->emptyStateDescription('Crea tu primer almacén para comenzar a gestionar tu inventario.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            ArticulosStockRelationManager::class,
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