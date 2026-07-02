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
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
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

    /**
     * Obtener opciones de manera segura sin errores
     */
    private static function getSafeOptions(string $table, string $labelColumn, string $valueColumn = 'id', array $filters = [], array $additionalConditions = []): array
    {
        try {
            // Verificar si la tabla existe
            if (!Schema::hasTable($table)) {
                return [];
            }

            // Verificar si las columnas existen
            if (!Schema::hasColumn($table, $labelColumn) || !Schema::hasColumn($table, $valueColumn)) {
                return [];
            }

            $query = DB::table($table);

            // Aplicar filtros si existen
            if (!empty($filters)) {
                foreach ($filters as $column => $value) {
                    if (Schema::hasColumn($table, $column)) {
                        $query->where($column, $value);
                    }
                }
            }

            // Aplicar condiciones adicionales (como SoftDeletes)
            if (!empty($additionalConditions)) {
                foreach ($additionalConditions as $column => $value) {
                    if (Schema::hasColumn($table, $column)) {
                        $query->where($column, $value);
                    }
                }
            }

            // Ordenar por el label
            $query->orderBy($labelColumn);

            return $query->pluck($labelColumn, $valueColumn)->toArray();
        } catch (\Exception $e) {
            // Si hay cualquier error, devolver array vacío
            return [];
        }
    }

    /**
     * Verificar si una tabla tiene datos
     */
    private static function hasData(string $table): bool
    {
        try {
            if (!Schema::hasTable($table)) {
                return false;
            }
            return DB::table($table)->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtener opciones de fabricantes de manera segura
     */
    private static function getFabricanteOptions(): array
    {
        try {
            if (!Schema::hasTable('alm_fabricantes')) {
                return [];
            }

            // Verificar qué columna de nombre existe
            $labelColumn = 'nombre'; // Por defecto

            if (Schema::hasColumn('alm_fabricantes', 'nombre_comercial')) {
                $labelColumn = 'nombre_comercial';
            } elseif (Schema::hasColumn('alm_fabricantes', 'razon_social')) {
                $labelColumn = 'razon_social';
            } elseif (Schema::hasColumn('alm_fabricantes', 'nombre')) {
                $labelColumn = 'nombre';
            }

            return DB::table('alm_fabricantes')
                ->select('id', "$labelColumn as label")
                // ELIMINAR ESTA LÍNEA: ->whereNull('deleted_at')
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
                Tabs::make('Gestión de Artículo')
                    ->tabs([

                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos Básicos')
                                    ->icon('heroicon-o-identification')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Se genera automáticamente')
                                                    ->helperText('Déjalo vacío para generar automáticamente o ingresa uno personalizado')
                                                    ->disabled() // Deshabilitar en edición
                                                    ->dehydrated(fn($record) => $record === null), // Solo enviar en creación

                                                TextInput::make('codigo_alterno')
                                                    ->label('Código Alterno')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: REF-001')
                                                    ->helperText('Código alternativo o del proveedor'),

                                                TextInput::make('nombre_comercial')
                                                    ->label('Nombre Comercial')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Laptop HP ProBook 450')
                                                    ->helperText('Nombre comercial del artículo para mostrarlo en listados'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([

                                                Select::make('grupo_articulo_id')
                                                    ->label('Grupo de Artículo')
                                                    ->options(fn() => self::getSafeOptions('alm_grupos_articulos', 'nombre'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder(self::hasData('alm_grupos_articulos') ? 'Seleccione un grupo' : 'No hay grupos disponibles')
                                                    ->helperText('Clasificación del artículo')
                                                    ->disabled(!self::hasData('alm_grupos_articulos')),

                                                Select::make('fabricante_id')
                                                    ->label('Fabricante')
                                                    ->options(fn() => self::getFabricanteOptions())  // Usar el método específico
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder(self::hasData('alm_fabricantes') ? 'Seleccione un fabricante' : 'No hay fabricantes disponibles')
                                                    ->disabled(!self::hasData('alm_fabricantes')),
                                            ]),

                                        Grid::make(2)
                                            ->schema([

                                                Select::make('unidad_medida_id')
                                                    ->label('Unidad de Medida')
                                                    ->options(fn() => self::getSafeOptions('alm_unidades_medida', 'nombre'))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder(self::hasData('alm_unidades_medida') ? 'Seleccione una unidad' : 'No hay unidades disponibles')
                                                    ->helperText('Unidad base del artículo')
                                                    ->disabled(!self::hasData('alm_unidades_medida')),

                                                Select::make('empresa_id')
                                                    ->label('Empresa')
                                                    ->options(fn() => self::getSafeOptions(
                                                        'conf_empresas',
                                                        'nombre_comercial',  // Cambiar 'nombre' por 'nombre_comercial'
                                                        'id',
                                                        [],
                                                        ['deleted_at' => null]  // Filtrar empresas no eliminadas (SoftDeletes)
                                                    ))
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder(self::hasData('conf_empresas') ? 'Seleccione una empresa' : 'No hay empresas disponibles')
                                                    ->helperText('Empresa a la que pertenece el artículo')
                                                    ->disabled(!self::hasData('conf_empresas')),
                                            ]),

                                        Grid::make(2)
                                            ->schema([

                                                Toggle::make('activo')
                                                    ->label('Activo')
                                                    ->default(true)
                                                    ->helperText('Artículo disponible para su uso'),
                                            ]),
                                    ]),

                                Section::make('Descripción y Características')
                                    ->icon('heroicon-o-document-text')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([

                                                Textarea::make('descripcion')
                                                    ->label('Descripción')
                                                    ->placeholder('Descripción detallada del artículo... 255 caracteres Max.')
                                                    ->rows(6)
                                                    ->maxLength(255)  // Limita a 255 caracteres (como string)
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
                                                    ->helperText('Subir imagen principal del artículo'),

                                                FileUpload::make('documentacion_tecnica')
                                                    ->label('Documentación Técnica')
                                                    ->directory('articulos/documentacion')
                                                    ->visibility('public')
                                                    ->acceptedFileTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
                                                    ->maxSize(10240)
                                                    ->helperText('PDF, Word u otros documentos (máx. 10MB)'),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: CONTROL DE INVENTARIO ==========
                        Tabs\Tab::make('Inventario')
                            ->icon('heroicon-o-archive-box')
                            ->schema([
                                Section::make('Configuración de Inventario')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('inventariable')
                                                    ->label('¿Es inventariable?')
                                                    ->default(true)
                                                    ->helperText('Controla stock de este artículo')
                                                    ->live(),

                                                Toggle::make('maneja_lotes')
                                                    ->label('Maneja Lotes')
                                                    ->helperText('Control por número de lote')
                                                    ->visible(fn(Forms\Get $get) => $get('inventariable')),

                                                Toggle::make('maneja_series')
                                                    ->label('Maneja Series')
                                                    ->helperText('Control por número de serie')
                                                    ->visible(fn(Forms\Get $get) => $get('inventariable')),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('requiere_serie_en_salida')
                                                    ->label('Requerir Serie en Salida')
                                                    ->helperText('Obligatorio registrar serie al vender')
                                                    ->visible(fn(Forms\Get $get) => $get('maneja_series')),

                                                Select::make('metodo_costo')
                                                    ->label('Método de Costeo')
                                                    ->options([
                                                        'promedio' => '📊 Costo Promedio',
                                                        'fifo' => '📈 FIFO (Primero en entrar, primero en salir)',
                                                        'estandar' => '📉 Costo Estándar',
                                                    ])
                                                    ->default('promedio')
                                                    ->helperText('Método para calcular el costo del inventario'),
                                            ]),
                                    ]),

                                Section::make('Información de Stock')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Placeholder::make('stock_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                                    <p class="text-sm text-blue-700">
                                                        ⚡ La gestión de existencias, lotes y series se realiza en las 
                                                        <strong>pestañas de relaciones</strong> en la parte inferior del formulario.
                                                    </p>
                                                    <p class="text-xs text-blue-600 mt-2">
                                                        Puedes gestionar: Existencias por almacén, Lotes, Series, y Ubicaciones.
                                                    </p>
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
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('comprable')
                                                    ->label('¿Es comprable?')
                                                    ->default(true)
                                                    ->helperText('Permite comprar este artículo')
                                                    ->live(),

                                                TextInput::make('costo_referencial')
                                                    ->label('Costo Referencial')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->placeholder('0.00')
                                                    ->default(0)
                                                    ->step(0.000001)
                                                    ->dehydrateStateUsing(fn($state) => $state ?? 0)
                                                    ->helperText('Costo sugerido para compras')
                                                    ->visible(fn(Forms\Get $get) => $get('comprable')),
                                            ]),
                                    ]),

                                Section::make('Proveedores Asignados')
                                    ->icon('heroicon-o-users')
                                    ->description('Lista de proveedores que suministran este artículo')
                                    ->schema([
                                        Forms\Components\Placeholder::make('proveedores_resumen')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return '<div class="text-sm text-gray-500">Los proveedores se mostrarán después de guardar el artículo.</div>';
                                                }

                                                try {
                                                    $proveedores = $record->proveedores()->with('proveedor')->get();

                                                    if ($proveedores->isEmpty()) {
                                                        return '<div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                    <p class="text-sm text-yellow-700">No hay proveedores asignados a este artículo.</p>
                                    <p class="text-xs text-yellow-500 mt-1">Gestiona los proveedores en la pestaña "Proveedores" en la sección de relaciones.</p>
                                </div>';
                                                    }

                                                    $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';

                                                    foreach ($proveedores as $item) {
                                                        $proveedor = $item->proveedor;
                                                        $esPrincipal = $item->es_principal ? '⭐ ' : '';
                                                        $color = $item->es_principal ? 'border-yellow-400 bg-yellow-50' : 'border-gray-200 bg-white';
                                                        $icono = $item->es_principal ? '★' : '▸';

                                                        $html .= '<div class="p-3 rounded-lg border ' . $color . '">';
                                                        $html .= '<div class="flex justify-between items-start">';
                                                        $html .= '<div>';
                                                        $html .= '<p class="font-medium text-gray-800">' . $esPrincipal . ($proveedor->nombre ?? 'Proveedor') . '</p>';
                                                        $html .= '<p class="text-xs text-gray-500">Código: ' . ($proveedor->codigo ?? 'N/A') . '</p>';

                                                        if ($item->codigo_proveedor) {
                                                            $html .= '<p class="text-xs text-gray-500">Código Proveedor: ' . $item->codigo_proveedor . '</p>';
                                                        }

                                                        if ($item->costo_compra > 0) {
                                                            $html .= '<p class="text-xs text-green-600 font-medium">Costo: $ ' . number_format($item->costo_compra, 2) . '</p>';
                                                        }

                                                        if ($proveedor && isset($proveedor->telefono) && $proveedor->telefono) {
                                                            $html .= '<p class="text-xs text-gray-400">📞 ' . $proveedor->telefono . '</p>';
                                                        }

                                                        $html .= '</div>';

                                                        if ($item->es_principal) {
                                                            $html .= '<span class="text-xs bg-yellow-200 text-yellow-800 px-2 py-1 rounded-full">Principal</span>';
                                                        }

                                                        $html .= '</div>';
                                                        $html .= '</div>';
                                                    }

                                                    $html .= '</div>';

                                                    return $html;
                                                } catch (\Exception $e) {
                                                    return '<div class="text-sm text-gray-500">Error al cargar proveedores.</div>';
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
                                        Grid::make(3)
                                            ->schema([
                                                Toggle::make('vendible')
                                                    ->label('¿Es vendible?')
                                                    ->default(true)
                                                    ->helperText('Permite vender este artículo')
                                                    ->live(),

                                                TextInput::make('precio_base')
                                                    ->label('Precio Base')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->placeholder('0.00')
                                                    ->default(0)
                                                    ->step(0.000001)
                                                    ->dehydrateStateUsing(fn($state) => $state ?? 0)
                                                    ->helperText('Precio base para cálculos de venta')
                                                    ->visible(fn(Forms\Get $get) => $get('vendible')),

                                                TextInput::make('comision')
                                                    ->label('Comisión')
                                                    ->numeric()
                                                    ->suffix('%')
                                                    ->placeholder('0.00')
                                                    ->default(0)
                                                    ->step(0.000001)
                                                    ->dehydrateStateUsing(fn($state) => $state ?? 0)
                                                    ->helperText('Porcentaje de comisión para vendedores')
                                                    ->visible(fn(Forms\Get $get) => $get('vendible')),
                                            ]),
                                    ]),

                                Section::make('Precios Especiales')
                                    ->icon('heroicon-o-tag')
                                    ->schema([
                                        Placeholder::make('precios_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <p class="text-sm text-gray-600">
                                🏷️ Gestiona listas de precios, descuentos y precios especiales en la pestaña 
                                <strong>"Precios"</strong> en la sección de relaciones.
                            </p>
                        </div>'
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 5: RELACIONES Y EXTRA ==========
                        Tabs\Tab::make('Relaciones')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make('Unidades de Medida Alternas')
                                    ->icon('heroicon-o-scale')
                                    ->schema([
                                        Placeholder::make('unidades_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                    <p class="text-sm text-gray-600">
                                                        📏 Gestiona unidades alternas (ej: caja, pallet, docena) en la pestaña 
                                                        <strong>"Unidades"</strong> en la sección de relaciones.
                                                    </p>
                                                </div>'
                                            ))
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Imágenes y Atributos')
                                    ->icon('heroicon-o-photo')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('imagenes_info')
                                                    ->label('')
                                                    ->content(new HtmlString(
                                                        '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                            <p class="text-sm text-gray-600">
                                                                🖼️ Gestiona imágenes adicionales en la pestaña <strong>"Imágenes"</strong>.
                                                            </p>
                                                        </div>'
                                                    )),

                                                Placeholder::make('atributos_info')
                                                    ->label('')
                                                    ->content(new HtmlString(
                                                        '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                            <p class="text-sm text-gray-600">
                                                                🏷️ Gestiona atributos (talla, color, etc.) en la pestaña <strong>"Atributos"</strong>.
                                                            </p>
                                                        </div>'
                                                    )),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 6: CÓDIGOS DE BARRAS ==========
                        Tabs\Tab::make('Códigos de Barras')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                Section::make('Gestión de Códigos de Barras')
                                    ->schema([
                                        Placeholder::make('codigos_barras_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                                    <p class="text-sm text-gray-600">
                                                        📱 Gestiona múltiples códigos de barras por artículo en la pestaña 
                                                        <strong>"Códigos de Barras"</strong> en la sección de relaciones.
                                                    </p>
                                                    <p class="text-xs text-gray-500 mt-2">
                                                        Puedes tener diferentes códigos por unidad de medida, proveedor, etc.
                                                    </p>
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
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Código copiado'),

                TextColumn::make('nombre_comercial')
                    ->label('Nombre Comercial')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30)
                    ->default('-'),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->tooltip(fn($record) => $record->descripcion ?? ''),

                TextColumn::make('grupoArticulo.nombre')
                    ->label('Grupo')
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->searchable()
                    ->default('-'),

                TextColumn::make('fabricante.nombre')
                    ->label('Fabricante')
                    ->toggleable()
                    ->searchable()
                    ->default('-'),

                TextColumn::make('unidadMedida.abreviatura')
                    ->label('UM')
                    ->badge()
                    ->color('success')
                    ->toggleable()
                    ->searchable()
                    ->default('-'),

                TextColumn::make('empresa.nombre_comercial')  // Cambiar 'nombre' por 'nombre_comercial'
                    ->label('Empresa')
                    ->toggleable()
                    ->searchable()
                    ->default('-'),

                IconColumn::make('inventariable')
                    ->label('Stock')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                IconColumn::make('maneja_lotes')
                    ->label('Lotes')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

                IconColumn::make('maneja_series')
                    ->label('Series')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('costo_referencial')
                    ->label('Costo Ref.')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('precio_base')
                    ->label('Precio Base')
                    ->money('USD')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('activo')
                    ->label('Activo')
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
                    ->options(fn() => self::getFabricanteOptions())  // Usar el método específico
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
                        ->modalWidth('7xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(fn(Articulo $record) => static::duplicateRecord($record))
                        ->requiresConfirmation()
                        ->modalHeading('Duplicar Artículo')
                        ->modalSubheading('¿Deseas crear una copia de este artículo?'),

                    Tables\Actions\DeleteAction::make(),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado'),
                ]),
            ])
            ->defaultSort('codigo', 'asc')
            ->searchPlaceholder('Buscar artículo...')
            ->emptyStateHeading('No hay artículos registrados')
            ->emptyStateDescription('Crea tu primer artículo para comenzar.')
            ->emptyStateIcon('heroicon-o-cube')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        // Array de relation managers disponibles
        $relations = [];

        if (Schema::hasTable('alm_existencias')) {
            $relations[] = ExistenciasRelationManager::class;
        }

        if (Schema::hasTable('alm_precios_articulos') && Schema::hasColumn('alm_precios_articulos', 'lista_precio_id')) {
            $relations[] = PreciosRelationManager::class;
        }

        if (Schema::hasTable('alm_codigos_barras')) {
            $relations[] = CodigosBarrasRelationManager::class;
        }

        if (Schema::hasTable('alm_unidades_articulos') || Schema::hasTable('alm_articulo_unidades')) {
            $relations[] = UnidadesRelationManager::class;
        }

        if (Schema::hasTable('alm_articulo_imagenes')) {
            $relations[] = ImagenesRelationManager::class;
        }

        if (Schema::hasTable('alm_articulo_atributos')) {
            $relations[] = AtributosRelationManager::class;
        }

        if (Schema::hasTable('cmp_articulos_proveedores')) {
            $relations[] = ProveedoresRelationManager::class;
        }

        if (Schema::hasTable('alm_series')) {
            $relations[] = SeriesRelationManager::class;
        }

        if (Schema::hasTable('alm_lotes')) {
            $relations[] = LotesRelationManager::class;
        }

        if (Schema::hasTable('alm_articulos_atributos')) {
            $relations[] = AtributosRelationManager::class;
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

            // Asegurar valores por defecto
            $newRecord->costo_referencial = $record->costo_referencial ?? 0;
            $newRecord->precio_base = $record->precio_base ?? 0;
            $newRecord->comision = $record->comision ?? 0;

            $newRecord->created_at = now();
            $newRecord->updated_at = now();
            $newRecord->save();

            \Filament\Notifications\Notification::make()
                ->title('Artículo duplicado exitosamente')
                ->body('El artículo "' . ($newRecord->nombre_comercial ?? $newRecord->descripcion ?? $newRecord->codigo) . '" ha sido creado.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al duplicar')
                ->body('Ocurrió un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
