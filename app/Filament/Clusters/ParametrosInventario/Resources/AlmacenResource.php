<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource\Pages;
use App\Models\Inventario\Almacen;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class AlmacenResource extends Resource
{
    protected static ?string $model = Almacen::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Almacenes';

    protected static ?string $modelLabel = 'Almacén';

    protected static ?string $pluralModelLabel = 'Almacenes';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->hasAnyRole(['super_admin', 'admin'])) {
            return $query;
        }

        if (! $user->empresa_id) {
            return $query->whereRaw('1 = 0');
        }

        $query->where('empresa_id', $user->empresa_id);

        if ($user->sucursal_id) {
            $query->where(fn (Builder $q) => $q
                ->where('sucursal_id', $user->sucursal_id)
                ->orWhereNull('sucursal_id'));
        }

        return $query;
    }

    public static function empresasDisponibles(): array
    {
        $user = Auth::user();
        $query = Empresa::query()->orderByRaw("CASE WHEN nombre_comercial IS NULL OR nombre_comercial = '' THEN razon_social ELSE nombre_comercial END");

        if ($user && ! $user->hasAnyRole(['super_admin', 'admin'])) {
            $query->whereKey($user->empresa_id ?? 0);
        }

        return $query->get()
            ->mapWithKeys(fn (Empresa $empresa) => [
                $empresa->id => $empresa->nombre_comercial ?: $empresa->razon_social,
            ])
            ->all();
    }

    public static function empresaPredeterminada(): ?int
    {
        if (Auth::user()?->empresa_id) {
            return (int) Auth::user()->empresa_id;
        }

        return array_key_first(self::empresasDisponibles());
    }

    public static function sucursalesDisponibles(?int $empresaId = null): array
    {
        $user = Auth::user();
        $empresaId ??= $user?->empresa_id;

        return Sucursal::query()
            ->where('activo', true)
            ->when($empresaId, fn (Builder $q) => $q->where('empresa_id', $empresaId))
            ->when($user && ! $user->hasAnyRole(['super_admin', 'admin']) && $user->sucursal_id,
                fn (Builder $q) => $q->whereKey($user->sucursal_id))
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    /**
     * Verificar si la tabla de ubicaciones tiene la columna almacen_id
     */
    private static function ubicacionesTieneAlmacenId(): bool
    {
        if (! Schema::hasTable('alm_ubicaciones')) {
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
        if (! Schema::hasTable('alm_existencias')) {
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
                                Section::make('Asignación organizacional')
                                    ->icon('heroicon-o-building-office')
                                    ->description('Define a qué empresa y sucursal pertenece el almacén.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('empresa_id')
                                                    ->label('Empresa')
                                                    ->hint('Obligatorio')
                                                    ->prefixIcon('heroicon-o-building-office-2')
                                                    ->options(fn () => self::empresasDisponibles())
                                                    ->default(fn () => self::empresaPredeterminada())
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->live()
                                                    ->afterStateUpdated(fn ($set) => $set('sucursal_id', null))
                                                    ->disabled(fn () => Auth::user() && ! Auth::user()->hasAnyRole(['super_admin', 'admin']))
                                                    ->dehydrated()
                                                    ->helperText('La empresa controla el aislamiento de stock, ventas y movimientos contables.'),

                                                Select::make('sucursal_id')
                                                    ->label('Sucursal')
                                                    ->hint('Opcional')
                                                    ->prefixIcon('heroicon-o-map-pin')
                                                    ->options(fn ($get) => self::sucursalesDisponibles($get('empresa_id') ? (int) $get('empresa_id') : null))
                                                    ->default(fn () => Auth::user()?->sucursal_id)
                                                    ->searchable()
                                                    ->preload()
                                                    ->disabled(fn () => Auth::user()?->sucursal_id && ! Auth::user()->hasAnyRole(['super_admin', 'admin']))
                                                    ->dehydrated()
                                                    ->placeholder('General para toda la empresa')
                                                    ->helperText('Seleccione una sucursal para uso exclusivo. Déjelo vacío si estará disponible para todas las sucursales de la empresa.')
                                                    ->visible(fn () => Schema::hasTable('conf_sucursales')),
                                            ]),
                                    ]),

                                Section::make('Identificación del almacén')
                                    ->icon('heroicon-o-identification')
                                    ->description('Datos utilizados para reconocer el almacén en formularios y reportes.')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('nombre')
                                                    ->label('Nombre del almacén')
                                                    ->hint('Obligatorio')
                                                    ->prefixIcon('heroicon-o-building-storefront')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej.: Almacén Central')
                                                    ->helperText('Use un nombre breve y fácil de distinguir, especialmente si existen varias sucursales.'),

                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->prefixIcon('heroicon-o-qr-code')
                                                    ->disabled()
                                                    ->dehydrated(false)
                                                    ->placeholder('Se genera automáticamente')
                                                    ->helperText('El sistema asignará un código único al guardar el almacén.'),
                                            ]),

                                        Textarea::make('direccion')
                                            ->label('Dirección física')
                                            ->hint('Opcional')
                                            ->rows(2)
                                            ->maxLength(500)
                                            ->placeholder('Ej.: Av. Principal N.º 123, Zona Industrial')
                                            ->helperText('Indique dónde se recibe o despacha la mercadería. Facilita transferencias y documentos logísticos.')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Disponibilidad operativa')
                                    ->icon('heroicon-o-check-circle')
                                    ->description('Controla si el almacén puede utilizarse en ventas, reservas, compras y movimientos de inventario.')
                                    ->schema([
                                        Toggle::make('activo')
                                            ->label('Almacén disponible')
                                            ->inline(false)
                                            ->default(true)
                                            ->helperText('Si lo desactiva, conservará su historial y stock, pero no aparecerá para nuevas operaciones.'),
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
                                                if (! $record) {
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
                                                                    <p class="text-2xl font-bold text-gray-900">'.$totalUbicaciones.'</p>
                                                                </div>
                                                                <div>
                                                                    <p class="text-sm text-gray-600">Ubicaciones Activas</p>
                                                                    <p class="text-2xl font-bold text-green-600">'.$ubicacionesActivas.'</p>
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
                                                if (! $record) {
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
                                                                <div class="text-2xl font-bold text-blue-900">'.number_format($totalArticulos).'</div>
                                                                <div class="text-xs text-blue-500 mt-1">Productos disponibles en este almacén</div>
                                                            </div>
                                                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                                                <div class="text-sm text-green-600 font-medium">Existencias Totales</div>
                                                                <div class="text-2xl font-bold text-green-900">'.number_format($totalExistencias, 0).'</div>
                                                                <div class="text-xs text-green-500 mt-1">Unidades disponibles en inventario</div>
                                                            </div>
                                                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                                                <div class="text-sm text-purple-600 font-medium">Movimientos</div>
                                                                <div class="text-2xl font-bold text-purple-900">'.number_format($totalMovimientos).'</div>
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
            TextColumn::make('empresa.nombre_comercial')
                ->label('Empresa')
                ->formatStateUsing(fn ($state, Almacen $record) => $state ?: $record->empresa?->razon_social)
                ->searchable()
                ->sortable()
                ->toggleable(),

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

        // Agregar columna de Ubicaciones solo si la tabla existe y tiene la columna correcta
        if ($ubicacionesExiste) {
            $columns[] = TextColumn::make('ubicaciones_count')
                ->label('Ubicaciones')
                ->counts('ubicaciones')
                ->badge()
                ->color('warning')
                ->toggleable();
        }

        // Agregar columna de Existencias solo si la tabla existe y tiene la columna correcta
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
                    ->options(fn () => self::sucursalesDisponibles())
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
                            $newRecord->codigo = $record->codigo.'-COPY-'.time();
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
                            $record->update(['activo' => ! $record->activo]);
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
                        ->action(fn ($records) => $records->each->update(['activo' => ! $records->first()->activo]))
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
