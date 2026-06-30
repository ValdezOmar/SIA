<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\AlmacenResource\Pages;
use App\Models\Inventario\Almacen;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Builder;

class AlmacenResource extends Resource
{
    protected static ?string $model = Almacen::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Almacenes';

    protected static ?string $modelLabel = 'Almacén';

    protected static ?string $pluralModelLabel = 'Almacenes';

    protected static ?int $navigationSort = 2;

    /**
     * Verificar si la tabla de ubicaciones tiene la columna almacen_id
     */
    private static function ubicacionesTieneAlmacenId(): bool
    {
        if (!Schema::hasTable('alm_ubicaciones')) {
            return false;
        }
        
        try {
            $columns = Schema::getColumnListing('alm_ubicaciones');
            return in_array('almacen_id', $columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Verificar si la tabla de existencias tiene la columna almacen_id
     */
    private static function existenciasTieneAlmacenId(): bool
    {
        if (!Schema::hasTable('alm_existencias')) {
            return false;
        }
        
        try {
            $columns = Schema::getColumnListing('alm_existencias');
            return in_array('almacen_id', $columns);
        } catch (\Exception $e) {
            return false;
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Almacén')
                    ->tabs([
                        
                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos Básicos')
                                    ->icon('heroicon-o-identification')
                                    ->description('Información principal del almacén')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Ej: ALM-001')
                                                    ->helperText('Código único para identificar el almacén')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre')
                                                    ->label('Nombre del Almacén')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Almacén Central')
                                                    ->helperText('Nombre descriptivo del almacén')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('sucursal_id')
                                                    ->label('Sucursal')
                                                    ->relationship('sucursal', 'nombre')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una sucursal')
                                                    ->helperText('Sucursal a la que pertenece este almacén')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasTable('conf_sucursales')),

                                                Textarea::make('direccion')
                                                    ->label('Dirección')
                                                    ->rows(3)
                                                    ->placeholder('Ej: Av. Principal #123, Zona Industrial')
                                                    ->helperText('Ubicación física del almacén')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Almacén Activo')
                                                    ->default(true)
                                                    ->helperText('Desactive para inhabilitar temporalmente este almacén')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: UBICACIONES ==========
                        Tabs\Tab::make('Ubicaciones')
                            ->icon('heroicon-o-map-pin')
                            ->schema([
                                Section::make('Gestión de Ubicaciones')
                                    ->icon('heroicon-o-map-pin')
                                    ->description('Organización espacial del almacén')
                                    ->schema([
                                        Forms\Components\Placeholder::make('ubicaciones_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return '<div class="text-sm text-gray-500">Las ubicaciones se gestionan después de guardar el almacén.</div>';
                                                }

                                                try {
                                                    $totalUbicaciones = $record->ubicaciones()->count();
                                                    $ubicacionesActivas = $record->ubicaciones()->where('activo', true)->count();

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                            <div class="grid grid-cols-2 gap-4">
                                                                <div>
                                                                    <p class="text-sm text-gray-600">Total de Ubicaciones</p>
                                                                    <p class="text-2xl font-bold text-gray-900">' . $totalUbicaciones . '</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">Ubicaciones Activas</p>
                                                                    <p class="text-2xl font-bold text-green-600">' . $ubicacionesActivas . '</p>
                                                                </div>
                                                            </div>
                                                            <p class="text-xs text-gray-500 mt-2">Gestiona las ubicaciones en la pestaña "Ubicaciones" en la sección de relaciones.</p>
                                                        </div>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return '<div class="text-sm text-gray-500">No hay ubicaciones disponibles.</div>';
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 3: ESTADÍSTICAS ==========
                        Tabs\Tab::make('Estadísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen del Almacén')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Forms\Components\Placeholder::make('estadisticas')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'Las estadísticas se mostrarán después de guardar el almacén.';
                                                }

                                                try {
                                                    $totalArticulos = 0;
                                                    $totalMovimientos = 0;
                                                    $totalExistencias = 0;

                                                    if (self::existenciasTieneAlmacenId()) {
                                                        $totalExistencias = $record->existencias()->sum('cantidad_disponible');
                                                        $totalArticulos = $record->existencias()->distinct('articulo_id')->count();
                                                    }

                                                    if (Schema::hasTable('alm_movimientos_inventario')) {
                                                        $totalMovimientos = $record->movimientos()->count();
                                                    }

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                                                <div class="text-sm text-blue-600 font-medium">Artículos en Stock</div>
                                                                <div class="text-2xl font-bold text-blue-900">' . number_format($totalArticulos) . '</div>
                                                                <div class="text-xs text-blue-500 mt-1">Productos disponibles en este almacén</div>
                                                            </div>
                                                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                                                <div class="text-sm text-green-600 font-medium">Existencias Totales</div>
                                                                <div class="text-2xl font-bold text-green-900">' . number_format($totalExistencias, 0) . '</div>
                                                                <div class="text-xs text-green-500 mt-1">Unidades disponibles en inventario</div>
                                                            </div>
                                                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                                                <div class="text-sm text-purple-600 font-medium">Movimientos</div>
                                                                <div class="text-2xl font-bold text-purple-900">' . number_format($totalMovimientos) . '</div>
                                                                <div class="text-xs text-purple-500 mt-1">Total de movimientos registrados</div>
                                                            </div>
                                                        </div>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return '<div class="text-sm text-gray-500">No hay estadísticas disponibles.</div>';
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
        // Verificar si las tablas existen antes de definir las columnas
        $ubicacionesExiste = self::ubicacionesTieneAlmacenId();
        $existenciasExiste = self::existenciasTieneAlmacenId();

        $columns = [
            TextColumn::make('codigo')
                ->label('Código')
                ->searchable()
                ->sortable()
                ->copyable()
                ->copyMessage('Código copiado')
                ->toggleable(),

            TextColumn::make('nombre')
                ->label('Nombre')
                ->searchable()
                ->sortable()
                ->toggleable(),

            TextColumn::make('sucursal.nombre')
                ->label('Sucursal')
                ->searchable()
                ->sortable()
                ->badge()
                ->color('info')
                ->toggleable()
                ->placeholder('-')
                ->visible(fn () => Schema::hasTable('conf_sucursales')),

            TextColumn::make('direccion')
                ->label('Dirección')
                ->searchable()
                ->toggleable()
                ->limit(30)
                ->placeholder('-'),
        ];

        // ✅ Agregar columna de Ubicaciones solo si la tabla existe y tiene la columna correcta
        if ($ubicacionesExiste) {
            $columns[] = TextColumn::make('ubicaciones_count')
                ->label('Ubicaciones')
                ->counts('ubicaciones')
                ->badge()
                ->color('warning')
                ->toggleable();
        }

        // ✅ Agregar columna de Existencias solo si la tabla existe y tiene la columna correcta
        if ($existenciasExiste) {
            $columns[] = TextColumn::make('existencias_count')
                ->label('Stock')
                ->counts('existencias')
                ->badge()
                ->color('success')
                ->toggleable();
        }

        // Columnas fijas al final
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
                    ->visible(fn () => Schema::hasTable('conf_sucursales')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

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

                    Tables\Actions\Action::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->color(fn ($record) => $record->activo ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update(['activo' => !$record->activo]);
                            \Filament\Notifications\Notification::make()
                                ->title($record->activo ? 'Almacén activado' : 'Almacén desactivado')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make(),
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
                        ->action(fn ($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado de almacenes')
                        ->modalSubheading('¿Deseas cambiar el estado de los almacenes seleccionados?'),
                ]),
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
            // RelationManagers\UbicacionesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlmacens::route('/'),
            'create' => Pages\CreateAlmacen::route('/create'),
            'edit' => Pages\EditAlmacen::route('/{record}/edit'),
        ];
    }
}