<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\ArticuloResource\Pages\CreateArticulo;
use App\Filament\Resources\Inventario\ArticuloResource\Pages\EditArticulo;
use App\Filament\Resources\Inventario\ArticuloResource\Pages\ListArticulos;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\AtributosRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\CodigosBarrasRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\ExistenciasRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\ImagenesRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\LotesRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\PreciosRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\ProveedoresRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\SeriesRelationManager;
use App\Filament\Resources\Inventario\ArticuloResource\RelationManagers\UnidadesRelationManager;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Fabricante;
use App\Models\Inventario\GrupoArticulo;
use App\Models\Inventario\UnidadMedida;
use App\Models\Sistema\Empresa;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulo::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Artículos';

    protected static ?string $modelLabel = 'Artículo';

    protected static ?string $pluralModelLabel = 'Artículos';

    protected static ?int $navigationSort = 1;

    // ========== HELPERS ==========
    private static function getSafeOptions(string $table, string $labelColumn, string $valueColumn = 'id', array $filters = [], array $additionalConditions = []): array
    {
        try {
            if (!Schema::hasTable($table)) return [];
            if (!Schema::hasColumn($table, $labelColumn) || !Schema::hasColumn($table, $valueColumn)) return [];

            $query = DB::table($table);

            foreach ($filters as $column => $value) {
                if (Schema::hasColumn($table, $column)) $query->where($column, $value);
            }

            foreach ($additionalConditions as $column => $value) {
                if (Schema::hasColumn($table, $column)) $query->where($column, $value);
            }

            return $query->orderBy($labelColumn)->pluck($labelColumn, $valueColumn)->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    private static function hasData(string $table): bool
    {
        try {
            if (!Schema::hasTable($table)) return false;
            return DB::table($table)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    private static function getFabricanteOptions(): array
    {
        try {
            if (!Schema::hasTable('alm_fabricantes')) return [];

            $labelColumn = 'nombre';
            if (Schema::hasColumn('alm_fabricantes', 'nombre_comercial')) {
                $labelColumn = 'nombre_comercial';
            } elseif (Schema::hasColumn('alm_fabricantes', 'razon_social')) {
                $labelColumn = 'razon_social';
            }

            return DB::table('alm_fabricantes')
                ->select('id', "$labelColumn as label")
                ->orderBy($labelColumn)
                ->get()
                ->pluck('label', 'id')
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('')
                    ->tabs([

                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Información Principal')
                                    ->icon('heroicon-o-identification')
                                    ->description('Datos básicos del artículo')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Se genera automáticamente')
                                                    ->helperText('Déjalo vacío para auto-generar')
                                                    ->disabled()
                                                    ->dehydrated(fn($record) => $record === null)
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                TextInput::make('codigo_alterno')
                                                    ->label('Código Alterno')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: REF-001')
                                                    ->helperText('Código del proveedor')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre_comercial')
                                                    ->label('Nombre Comercial')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Laptop HP ProBook 450')
                                                    ->helperText('Nombre para mostrar')
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('grupo_articulo_id')
                                                    ->label('Grupo')
                                                    ->options(fn() => self::getSafeOptions('alm_grupos_articulos', 'nombre'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccionar grupo')
                                                    ->helperText('Clasificación del artículo')
                                                    ->prefixIcon('heroicon-o-folder')
                                                    ->disabled(!self::hasData('alm_grupos_articulos'))
                                                    ->columnSpan(1),

                                                Select::make('fabricante_id')
                                                    ->label('Fabricante')
                                                    ->options(fn() => self::getFabricanteOptions())
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccionar fabricante')
                                                    ->prefixIcon('heroicon-o-building-office-2')
                                                    ->disabled(!self::hasData('alm_fabricantes'))
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Select::make('unidad_medida_id')
                                                    ->label('Unidad de Medida')
                                                    ->options(fn() => self::getSafeOptions('alm_unidades_medida', 'nombre'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccionar unidad')
                                                    ->helperText('Unidad base del artículo')
                                                    ->prefixIcon('heroicon-o-scale')
                                                    ->disabled(!self::hasData('alm_unidades_medida'))
                                                    ->columnSpan(1),

                                                Select::make('empresa_id')
                                                    ->label('Empresa')
                                                    ->options(fn() => self::getSafeOptions('conf_empresas', 'nombre_comercial', 'id', [], ['deleted_at' => null]))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccionar empresa')
                                                    ->helperText('Empresa propietaria')
                                                    ->prefixIcon('heroicon-o-building-storefront')
                                                    ->disabled(!self::hasData('conf_empresas'))
                                                    ->default(function () {
                                                        $empresas = DB::table('conf_empresas')
                                                            ->whereNull('deleted_at')
                                                            ->select('id')
                                                            ->get();
                                                        return $empresas->count() === 1 ? $empresas->first()->id : null;
                                                    })
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Descripción y Características')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Textarea::make('descripcion')
                                                    ->label('Descripción')
                                                    ->placeholder('Descripción detallada del artículo...')
                                                    ->rows(6)
                                                    ->maxLength(255)
                                                    ->helperText('Máximo 255 caracteres')
                                                    //->prefixIcon('heroicon-o-document')
                                                    ->columnSpan(1),

                                                RichEditor::make('caracteristicas')
                                                    ->label('Características Técnicas')
                                                    ->placeholder('Especificaciones técnicas del artículo...')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                        'bulletList',
                                                        'orderedList',
                                                        'link',
                                                    ])
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Documentación y Multimedia')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                FileUpload::make('foto_catalogo')
                                                    ->label('Foto de Catálogo')
                                                    ->image()
                                                    ->imageResizeTargetWidth('400')
                                                    ->imageResizeTargetHeight('400')
                                                    ->directory('articulos/catalogo')
                                                    ->visibility('public')
                                                    ->helperText('Subir imagen principal')
                                                    ->imagePreviewHeight('200')
                                                    ->loadingIndicatorPosition('left')
                                                    ->panelLayout('grid')
                                                    ->columnSpan(1),

                                                FileUpload::make('documentacion_tecnica')
                                                    ->label('Documentación Técnica')
                                                    ->multiple()
                                                    ->directory('articulos/documentacion')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes([
                                                        'application/pdf',
                                                        'application/msword',
                                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                                        'application/vnd.ms-excel',
                                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                                        'image/jpeg',
                                                        'image/png',
                                                        'image/gif'
                                                    ])
                                                    ->maxSize(15360)
                                                    ->maxFiles(5)
                                                    ->downloadable()
                                                    ->openable()
                                                    ->previewable(true)
                                                    ->reorderable()
                                                    ->appendFiles()
                                                    ->panelLayout('grid')
                                                    ->uploadingMessage('Subiendo documentación...')
                                                    ->helperText('PDF, Word, Excel, Imágenes (máx. 15MB)')
                                                    ->storeFileNamesIn('documentacion_tecnica')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Toggle::make('activo')
                                            ->label('Activo')
                                            ->default(true)
                                            ->helperText('Artículo disponible para uso')
                                            ->columnSpan(1),
                                    ]),
                            ]),

                        // ========== TAB 2: INVENTARIO ==========
                        Tabs\Tab::make('Inventario')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Section::make('Configuración de Inventario')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->description('Control de inventario para este artículo')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('inventariable')
                                                    ->label('📊 ¿Es inventariable?')
                                                    ->default(true)
                                                    ->helperText('Controla el stock en inventario')
                                                    ->live()
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('maneja_lotes')
                                                    ->label('Maneja Lotes')
                                                    ->helperText('Control por número de lote')
                                                    ->visible(fn(Forms\Get $get) => $get('inventariable'))
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                        if ($state) {
                                                            $set('maneja_series', false);
                                                            $set('requiere_serie_en_salida', false);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                Toggle::make('maneja_series')
                                                    ->label('Maneja Series')
                                                    ->helperText('Control por número de serie')
                                                    ->visible(fn(Forms\Get $get) => $get('inventariable'))
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                        if ($state) {
                                                            $set('maneja_lotes', false);
                                                        }
                                                    })
                                                    ->columnSpan(1),

                                                Toggle::make('requiere_serie_en_salida')
                                                    ->label('Requerir Serie en Salida')
                                                    ->helperText('Obligatorio registrar serie al vender')
                                                    ->visible(fn(Forms\Get $get) => $get('maneja_series'))
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(1)
                                            ->schema([
                                                Select::make('metodo_costo')
                                                    ->label('Método de Costeo')
                                                    ->options([
                                                        'promedio' => '📊 Costo Promedio',
                                                        'fifo' => '📈 FIFO',
                                                        'estandar' => '📉 Costo Estándar',
                                                    ])
                                                    ->default('promedio')
                                                    ->helperText('Método para calcular el costo')
                                                    ->prefixIcon('heroicon-o-calculator')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Gestión de Stock')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Placeholder::make('stock_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-xl border border-primary-200 dark:border-primary-800">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-2xl">📊</span>
                                                        <div>
                                                            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">
                                                                Gestión de existencias, lotes y series
                                                            </p>
                                                            <p class="text-xs text-primary-600 dark:text-primary-400 mt-1">
                                                                Gestiona el stock en las pestañas de relaciones al final del formulario
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>'
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 3: COMPRAS ==========
                        Tabs\Tab::make('Compras')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Section::make('Configuración de Compras')
                                    ->icon('heroicon-o-truck')
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                Toggle::make('comprable')
                                                    ->label('¿Es comprable?')
                                                    ->default(true)
                                                    ->helperText('Permite comprar este artículo')
                                                    ->live()
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Proveedores Asignados')
                                    ->icon('heroicon-o-users')
                                    ->description('Lista de proveedores que suministran este artículo')
                                    ->schema([
                                        Placeholder::make('proveedores_resumen')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return new HtmlString(
                                                        '<div class="text-sm text-gray-500 dark:text-gray-400">Los proveedores se mostrarán después de guardar el artículo.</div>'
                                                    );
                                                }

                                                try {
                                                    $proveedores = $record->proveedores()->with('proveedor')->get();

                                                    if ($proveedores->isEmpty()) {
                                                        return new HtmlString(
                                                            '<div class="bg-warning-50 dark:bg-warning-900/20 p-4 rounded-xl border border-warning-200 dark:border-warning-800">
                                                                <p class="text-sm text-warning-700 dark:text-warning-400">No hay proveedores asignados.</p>
                                                                <p class="text-xs text-warning-600 dark:text-warning-500 mt-1">Gestiona los proveedores en la pestaña "Proveedores" en relaciones.</p>
                                                            </div>'
                                                        );
                                                    }

                                                    $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
                                                    foreach ($proveedores as $item) {
                                                        $proveedor = $item->proveedor;
                                                        $esPrincipal = $item->es_principal;
                                                        $bg = $esPrincipal ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400 dark:border-yellow-700' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700';

                                                        $html .= '<div class="p-4 rounded-xl border ' . $bg . '">';
                                                        $html .= '<div class="flex items-start justify-between">';
                                                        $html .= '<div class="flex-1">';
                                                        $html .= '<p class="font-medium text-gray-900 dark:text-gray-100">' . ($proveedor->nombre ?? 'Proveedor') . '</p>';
                                                        $html .= '<p class="text-xs text-gray-500 dark:text-gray-400">Código: ' . ($proveedor->codigo ?? 'N/A') . '</p>';
                                                        if ($item->codigo_proveedor) {
                                                            $html .= '<p class="text-xs text-gray-500 dark:text-gray-400">Código Prov.: ' . $item->codigo_proveedor . '</p>';
                                                        }
                                                        if ($item->costo_compra > 0) {
                                                            $html .= '<p class="text-xs text-green-600 dark:text-green-400 font-medium">Costo: $ ' . number_format($item->costo_compra, 2) . '</p>';
                                                        }
                                                        $html .= '</div>';
                                                        if ($esPrincipal) {
                                                            $html .= '<span class="text-xs bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 px-2 py-1 rounded-full font-medium">⭐ Principal</span>';
                                                        }
                                                        $html .= '</div>';
                                                        $html .= '</div>';
                                                    }
                                                    $html .= '</div>';

                                                    return new HtmlString($html);
                                                } catch (\Exception $e) {
                                                    return new HtmlString('<div class="text-sm text-gray-500">Error al cargar proveedores.</div>');
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 4: VENTAS ==========
                        Tabs\Tab::make('Ventas')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Configuración de Ventas')
                                    ->icon('heroicon-o-bolt')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('vendible')
                                                    ->label('¿Es vendible?')
                                                    ->default(true)
                                                    ->helperText('Permite vender este artículo')
                                                    ->live()
                                                    ->columnSpan(1),

                                                TextInput::make('comision')
                                                    ->label('Comisión')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->placeholder('0.00')
                                                    ->default(0)
                                                    ->step(0.01)
                                                    ->dehydrateStateUsing(fn($state) => $state ?? 0)
                                                    ->helperText('Porcentaje para vendedores')
                                                    ->prefixIcon('heroicon-o-percent-badge')
                                                    ->visible(fn(Forms\Get $get) => $get('vendible'))
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Precios Especiales')
                                    ->icon('heroicon-o-tag')
                                    ->schema([
                                        Placeholder::make('precios_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="bg-primary-50 dark:bg-primary-900/20 p-4 rounded-xl border border-primary-200 dark:border-primary-800">
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-2xl">🏷️</span>
                                                        <div>
                                                            <p class="text-sm font-medium text-primary-700 dark:text-primary-300">
                                                                Listas de precios, descuentos y precios especiales
                                                            </p>
                                                            <p class="text-xs text-primary-600 dark:text-primary-400 mt-1">
                                                                Gestiona los precios en la pestaña "Precios" en relaciones
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>'
                                            ))
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
        return $table
            ->columns([
                ImageColumn::make('foto_catalogo')
                    ->label('')
                    ->square()
                    ->size(40)
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->nombre_comercial ?? $record->codigo) . '&color=7F9CF5&background=EBF4FF';
                    })
                    ->toggleable(),

                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('✅ Código copiado')
                    ->weight('bold')
                    ->color('primary')
                    ->toggleable(),

                TextColumn::make('codigo_alterno')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('✅ Modelo copiado')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('nombre_comercial')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->default('-')
                    ->weight('medium')
                    ->toggleable(),

                TextColumn::make('grupoArticulo.nombre')
                    ->label('Grupo')
                    ->badge()
                    ->color('info')
                    ->searchable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('fabricante.nombre')
                    ->label('Fabricante')
                    ->searchable()
                    ->default('-')
                    ->toggleable(),

                TextColumn::make('unidadMedida.abreviatura')
                    ->label('UM')
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->default('-')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(25)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn($record) => $record->descripcion ?? ''),

                IconColumn::make('inventariable')
                    ->label('Stock')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('maneja_lotes')
                    ->label('Lotes')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('maneja_series')
                    ->label('Series')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('stock_total')
                    ->label('Stock')
                    ->getStateUsing(fn($record) => $record->stock_total ?? 0)
                    ->numeric(0)
                    ->sortable()
                    ->color(fn($state) => $state <= 0 ? 'danger' : ($state <= 10 ? 'warning' : 'success'))
                    ->badge()
                    ->tooltip(function ($record) {
                        $stockPorAlmacen = $record->stock_por_almacen ?? [];
                        if (empty($stockPorAlmacen)) {
                            return 'Sin stock disponible';
                        }
                        $tooltip = "Stock por almacén:\n";
                        foreach ($stockPorAlmacen as $almacen => $cantidad) {
                            $tooltip .= "• {$almacen}: {$cantidad} unidades\n";
                        }
                        return $tooltip;
                    })
                    ->toggleable(),
                    

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('grupo_articulo_id')
                    ->label('Grupo')
                    ->options(fn() => self::getSafeOptions('alm_grupos_articulos', 'nombre'))
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('fabricante_id')
                    ->label('Fabricante')
                    ->options(fn() => self::getFabricanteOptions())
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->options(fn() => self::getSafeOptions('conf_empresas', 'nombre_comercial', 'id', [], ['deleted_at' => null]))
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('inventariable')
                    ->label('Es inventariable')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No')
                    ->placeholder('Todos'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->icon('heroicon-o-pencil-square'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->icon('heroicon-o-eye'),

                    // Tables\Actions\Action::make('duplicate')
                    //     ->label('Duplicar')
                    //     ->icon('heroicon-o-document-duplicate')
                    //     ->color('info')
                    //     ->action(fn(Articulo $record) => static::duplicateRecord($record))
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Duplicar Artículo')
                    //     ->modalSubheading('¿Crear una copia de este artículo?'),

                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado'),
                ]),
            ])
            ->defaultSort('codigo', 'asc')
            //->searchPlaceholder('Buscar artículo por código, nombre, modelo...')
            ->emptyStateHeading('No hay artículos registrados')
            ->emptyStateDescription('Crea tu primer artículo para comenzar a gestionar tu inventario.')
            ->emptyStateIcon('heroicon-o-cube')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        $relations = [];

        if (Schema::hasTable('alm_existencias')) {
            $relations[] = ExistenciasRelationManager::class;
        }

        if (Schema::hasTable('alm_precios_articulos') && Schema::hasColumn('alm_precios_articulos', 'lista_precio_id')) {
            $relations[] = PreciosRelationManager::class;
        }

        if (Schema::hasTable('alm_articulo_unidades')) {
            $relations[] = UnidadesRelationManager::class;
        }

        if (Schema::hasTable('alm_articulos_atributos')) {
            $relations[] = AtributosRelationManager::class;
        }

        if (Schema::hasTable('cmp_articulos_proveedores')) {
            $relations[] = ProveedoresRelationManager::class;
        }

        if (Schema::hasTable('alm_lotes')) {
            $relations[] = LotesRelationManager::class;
        }

        if (Schema::hasTable('alm_series')) {
            $relations[] = SeriesRelationManager::class;
        }

        if (Schema::hasTable('alm_codigos_barras')) {
            $relations[] = CodigosBarrasRelationManager::class;
        }

        return $relations;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticulos::route('/'),
            'create' => CreateArticulo::route('/create'),
            'edit' => EditArticulo::route('/{record}/edit'),
        ];
    }

    public static function duplicateRecord(Articulo $record): void
    {
        try {
            $newRecord = $record->replicate();
            $newRecord->codigo = $record->codigo . '-COPY-' . time();
            $newRecord->comision = $record->comision ?? 0;
            $newRecord->created_at = now();
            $newRecord->updated_at = now();
            $newRecord->save();

            \Filament\Notifications\Notification::make()
                ->title('✅ Artículo duplicado exitosamente')
                ->body('El artículo "' . ($newRecord->nombre_comercial ?? $newRecord->descripcion ?? $newRecord->codigo) . '" ha sido creado.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('❌ Error al duplicar')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
