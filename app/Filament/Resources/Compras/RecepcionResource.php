<?php

namespace App\Filament\Resources\Compras;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\Compras\RecepcionDetalle;
use App\Forms\Components\CalculoRepeater;
use App\Models\Compras\OrdenCompraDetalle;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Compras\RecepcionResource\Pages\ListRecepcions;
use App\Filament\Resources\Compras\RecepcionResource\Pages\CreateRecepcion;
use App\Filament\Resources\Compras\RecepcionResource\Pages\EditRecepcion;
use App\Filament\Resources\Compras\RecepcionResource\Pages;
use App\Models\Compras\OrdenCompra;
use App\Models\Compras\Proveedor;
use App\Models\Compras\Recepcion;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RecepcionResource extends Resource
{
    protected static ?string $model = Recepcion::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-inbox';

    protected static string | \UnitEnum | null $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Recepciones';

    protected static ?string $modelLabel = 'RecepciÃƒÆ’Ã‚Â³n';

    protected static ?string $pluralModelLabel = 'Recepciones';

    protected static ?int $navigationSort = 3;

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && ! $record->inventario_procesado_at;
    }

    private static function formatearMonto($monto): string
    {
        return 'Bs '.number_format($monto ?? 0, 2);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('GestiÃƒÆ’Ã‚Â³n de RecepciÃƒÆ’Ã‚Â³n')
                    ->tabs([
                        Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la RecepciÃƒÆ’Ã‚Â³n')
                                    ->icon('heroicon-o-inbox')
                                    ->description('InformaciÃƒÆ’Ã‚Â³n principal de la recepciÃƒÆ’Ã‚Â³n')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('CÃƒÆ’Ã‚Â³digo')
                                                    ->required()
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('REC-000001')
                                                    ->helperText('CÃƒÆ’Ã‚Â³digo ÃƒÆ’Ã‚Âºnico de la recepciÃƒÆ’Ã‚Â³n')
                                                    ->default(fn () => Recepcion::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_recepcion')
                                                    ->label('Fecha RecepciÃƒÆ’Ã‚Â³n')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native()
                                                    ->helperText('Fecha de recepciÃƒÆ’Ã‚Â³n')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'pendiente' => 'Pendiente',
                                                        'parcial' => 'Parcial',
                                                        'completada' => 'Completada',
                                                        'rechazada' => 'Rechazada',
                                                    ])
                                                    ->default('pendiente')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),

                                                TextInput::make('guia_remision')
                                                    ->label('GuÃƒÆ’Ã‚Â­a de RemisiÃƒÆ’Ã‚Â³n')
                                                    ->maxLength(50)
                                                    ->placeholder('NÃƒÆ’Ã‚Âºmero de guÃƒÆ’Ã‚Â­a')
                                                    ->helperText('NÃƒÆ’Ã‚Âºmero de guÃƒÆ’Ã‚Â­a de remisiÃƒÆ’Ã‚Â³n')
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Select::make('orden_compra_id')
                                                    ->label('Orden de Compra')
                                                    ->options(
                                                        fn () => OrdenCompra::whereIn('estado', ['confirmada', 'parcial', 'recibida'])
                                                            ->orderBy('codigo')
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
                                                                $item->id => $item->codigo.' - '.($item->proveedor?->nombre ?? 'Sin proveedor'),
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una orden')
                                                    ->helperText('Orden de compra asociada')
                                                    ->prefixIcon('heroicon-o-shopping-cart')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            $orden = OrdenCompra::with('detalles.articulo')->find($state);
                                                            if ($orden) {
                                                                $set('proveedor_id', $orden->proveedor_id);
                                                                $set('moneda', $orden->moneda ?? 'BOB');
                                                                $set('tasa_cambio', $orden->tasa_cambio ?? 1);

                                                                $detalles = [];
                                                                foreach ($orden->detalles as $detalle) {
                                                                    $recibido = RecepcionDetalle::where('orden_detalle_id', $detalle->id)->sum('cantidad_aceptada');
                                                                    $pendiente = $detalle->cantidad - $recibido;

                                                                    if ($pendiente > 0) {
                                                                        $detalles[] = [
                                                                            'orden_detalle_id' => $detalle->id,
                                                                            'articulo_id' => $detalle->articulo_id,
                                                                            'codigo_articulo' => $detalle->codigo_articulo,
                                                                            'descripcion_articulo' => $detalle->descripcion_articulo,
                                                                            'unidad_medida' => $detalle->unidad_medida,
                                                                            'cantidad' => $pendiente,
                                                                            'cantidad_aceptada' => 0,
                                                                            'cantidad_rechazada' => 0,
                                                                            'costo_unitario' => $detalle->precio_unitario,
                                                                            'costo_total' => 0,
                                                                            'observaciones' => $detalle->observaciones,
                                                                        ];
                                                                    }
                                                                }
                                                                $set('detalles', $detalles);
                                                            }
                                                        } else {
                                                            $set('detalles', []);
                                                            $set('proveedor_id', null);
                                                        }
                                                    })
                                                    ->columnSpan(2),

                                                Select::make('proveedor_id')
                                                    ->label('Proveedor')
                                                    ->options(
                                                        fn () => Proveedor::where('activo', true)
                                                            ->orderBy('nombre')
                                                            ->pluck('nombre', 'id')
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un proveedor')
                                                    ->helperText('Proveedor de la recepciÃƒÆ’Ã‚Â³n')
                                                    ->prefixIcon('heroicon-o-building-office-2')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(1),

                                                Select::make('almacen_id')
                                                    ->label('AlmacÃƒÆ’Ã‚Â©n de ingreso')
                                                    ->options(fn (): array => Almacen::query()->where('activo', true)->orderBy('nombre')->pluck('nombre', 'id')->all())
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->helperText('El stock aceptado se ingresarÃƒÆ’Ã‚Â¡ ÃƒÆ’Ã‚Âºnicamente en este almacÃƒÆ’Ã‚Â©n.')
                                                    ->prefixIcon('heroicon-o-building-storefront')
                                                    ->columnSpan(1),

                                                TextInput::make('transportista')
                                                    ->label('Transportista')
                                                    ->maxLength(100)
                                                    ->placeholder('Nombre del transportista')
                                                    ->helperText('Transportista de la mercaderÃƒÆ’Ã‚Â­a')
                                                    ->prefixIcon('heroicon-o-truck')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)->schema([
                                            Select::make('moneda')->label('Moneda de compra')->options(['BOB' => 'Bolivianos (BOB)', 'USD' => 'DÃƒÆ’Ã‚Â³lares (USD)', 'EUR' => 'Euros (EUR)'])->default('BOB')->required()->live()->afterStateUpdated(fn ($state, callable $set) => $set('tasa_cambio', $state === 'BOB' ? 1 : null))->helperText('Kardex se valoriza en BOB y conserva esta moneda.'),
                                            TextInput::make('tasa_cambio')->label('Tipo de cambio a BOB')->numeric()->minValue(0.000001)->step(0.000001)->default(1)->required()->disabled(fn ($get): bool => ($get('moneda') ?? 'BOB') === 'BOB')->dehydrated()->helperText('Bolivianos por unidad de compra.'),
                                        ]),
                                        Textarea::make('observaciones')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->placeholder('Observaciones de la recepciÃƒÆ’Ã‚Â³n...')
                                            ->helperText('InformaciÃƒÆ’Ã‚Â³n adicional sobre la recepciÃƒÆ’Ã‚Â³n')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Productos')
                            ->icon('heroicon-o-shopping-bag')
                            ->badge(function ($record) {
                                if (! $record) {
                                    return 0;
                                }

                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle de RecepciÃƒÆ’Ã‚Â³n')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('ArtÃƒÆ’Ã‚Â­culos recibidos')
                                    ->schema([
                                        CalculoRepeater::make('detalles')->calculo('recepcion')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->schema([
                                                Grid::make(['default' => 1, 'lg' => 12])
                                                    ->schema([
                                                        Select::make('orden_detalle_id')
                                                            ->label('Producto')
                                                            ->options(function ($get, $livewire) {
                                                                $ordenId = $get('../../orden_compra_id') ?? $livewire->getOwnerRecord()?->orden_compra_id;
                                                                if (! $ordenId) {
                                                                    return [];
                                                                }

                                                                $orden = OrdenCompra::with('detalles.articulo')->find($ordenId);
                                                                if (! $orden) {
                                                                    return [];
                                                                }

                                                                return $orden->detalles->mapWithKeys(function ($detalle) {
                                                                    $recibido = RecepcionDetalle::where('orden_detalle_id', $detalle->id)->sum('cantidad_aceptada');
                                                                    $pendiente = $detalle->cantidad - $recibido;
                                                                    if ($pendiente <= 0) {
                                                                        return [];
                                                                    }

                                                                    return [
                                                                        $detalle->id => $detalle->articulo->codigo.' - '.$detalle->articulo->nombre_comercial.
                                                                            ' (Pendiente: '.number_format($pendiente, 2).')',
                                                                    ];
                                                                })->toArray();
                                                            })
                                                            ->required()
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione un producto')
                                                            ->helperText('Producto de la orden de compra')
                                                            ->prefixIcon('heroicon-o-cube')
                                                            ->reactive()
                                                            ->columnSpan(4)
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                if ($state) {
                                                                    $detalle = OrdenCompraDetalle::with('articulo')->find($state);
                                                                    if ($detalle) {
                                                                        $set('articulo_id', $detalle->articulo_id);
                                                                        $set('codigo_articulo', $detalle->codigo_articulo);
                                                                        $set('descripcion_articulo', $detalle->descripcion_articulo);
                                                                        $set('unidad_medida', $detalle->unidad_medida);
                                                                        $set('costo_unitario', $detalle->precio_unitario);
                                                                        $recibido = RecepcionDetalle::where('orden_detalle_id', $state)->sum('cantidad_aceptada');
                                                                        $pendiente = $detalle->cantidad - $recibido;
                                                                        $set('cantidad', $pendiente);
                                                                    }
                                                                }
                                                            }),

                                                        TextInput::make('articulo_id')
                                                            ->label('ArtÃƒÆ’Ã‚Â­culo ID')
                                                            ->hidden()
                                                            ->dehydrated(),

                                                        TextInput::make('cantidad')
                                                            ->label('Cantidad')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->step(1.00)
                                                            ->default(1)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-numbered-list')
                                                            ->columnSpan(2),

                                                        TextInput::make('cantidad_aceptada')
                                                            ->label('Cantidad Aceptada')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->maxValue(fn ($get): float => max(0, (float) ($get('cantidad') ?? 0) - (float) ($get('cantidad_rechazada') ?? 0)))
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-check-circle')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($state);
                                                                $costo = floatval($get('costo_unitario') ?? 0);
                                                                $set('costo_total', $cantidad * $costo);
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('cantidad_rechazada')
                                                            ->label('Cantidad Rechazada')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->maxValue(fn ($get): float => max(0, (float) ($get('cantidad') ?? 0) - (float) ($get('cantidad_aceptada') ?? 0)))
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-x-circle')
                                                            ->columnSpan(2),

                                                        TextInput::make('costo_unitario')
                                                            ->label('Costo Unitario')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix('$')
                                                            ->helperText('Costo unitario del producto')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($get('cantidad_aceptada') ?? 0);
                                                                $costo = floatval($state);
                                                                $set('costo_total', $cantidad * $costo);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('costo_total')
                                                            ->label('Costo Total')
                                                            ->content(function ($get) {
                                                                return '$ '.number_format($get('costo_total') ?? 0, 2);
                                                            })
                                                            ->extraAttributes(['class' => 'font-bold'])
                                                            ->columnSpan(2),

                                                        Textarea::make('series')
                                                            ->label('NÃƒÆ’Ã‚Âºmeros de serie')
                                                            ->rows(2)
                                                            ->placeholder('SERIE-001, SERIE-002')
                                                            ->helperText('Una serie por unidad aceptada. Solo para artÃƒÆ’Ã‚Â­culos que manejan series.')
                                                            ->visible(fn ($get) => (bool) (($get('articulo_id') ? Articulo::find($get('articulo_id')) : null)?->maneja_series))
                                                            ->columnSpan(6),

                                                        Textarea::make('lotes')
                                                            ->label('Lotes y cantidades')
                                                            ->rows(2)
                                                            ->placeholder('LOTE-A:2, LOTE-B:1')
                                                            ->helperText('Formato NUMERO_LOTE:CANTIDAD. La suma debe coincidir con la cantidad aceptada.')
                                                            ->visible(fn ($get) => (bool) (($get('articulo_id') ? Articulo::find($get('articulo_id')) : null)?->maneja_lotes))
                                                            ->columnSpan(6),
                                                    ]),

                                                Textarea::make('motivo_rechazo')
                                                    ->label('Motivo de Rechazo')
                                                    ->rows(2)
                                                    ->placeholder('Motivo del rechazo...')
                                                    ->helperText('Especificar motivo si hay cantidad rechazada')
                                                    ->visible(fn ($get) => floatval($get('cantidad_rechazada') ?? 0) > 0)
                                                    ->columnSpanFull(),

                                                TextInput::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->maxLength(255)
                                                    ->placeholder('Observaciones sobre este producto...')
                                                    ->prefixIcon('heroicon-o-clipboard-document')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->cloneable()
                                            ->addActionLabel('Agregar producto')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                                $articulo = null;

                                                if (isset($data['articulo_id']) && $data['articulo_id']) {
                                                    $articulo = Articulo::find($data['articulo_id']);
                                                }

                                                if (! $articulo && isset($data['orden_detalle_id']) && $data['orden_detalle_id']) {
                                                    $ordenDetalle = OrdenCompraDetalle::with('articulo')->find($data['orden_detalle_id']);
                                                    if ($ordenDetalle) {
                                                        $data['articulo_id'] = $ordenDetalle->articulo_id;
                                                        $articulo = $ordenDetalle->articulo;
                                                    }
                                                }

                                                $cantidad = floatval($data['cantidad'] ?? 0);
                                                $cantidadAceptada = floatval($data['cantidad_aceptada'] ?? $cantidad);
                                                $costoUnitario = floatval($data['costo_unitario'] ?? 0);

                                                $data['cantidad'] = $cantidad > 0 ? $cantidad : ($cantidadAceptada > 0 ? $cantidadAceptada : 0);
                                                $data['cantidad_aceptada'] = $cantidadAceptada;
                                                $data['cantidad_rechazada'] = floatval($data['cantidad_rechazada'] ?? 0);
                                                $data['codigo_articulo'] = $articulo ? $articulo->codigo : ($data['codigo_articulo'] ?? 'SIN_CODIGO');
                                                $data['descripcion_articulo'] = $articulo ? ($articulo->descripcion ?? $articulo->nombre_comercial ?? 'Sin descripciÃƒÆ’Ã‚Â³n') : ($data['descripcion_articulo'] ?? '');
                                                $data['unidad_medida'] = $articulo ? ($articulo->unidadMedida?->abreviatura ?? 'UND') : ($data['unidad_medida'] ?? 'UND');
                                                $data['costo_total'] = $cantidadAceptada * $costoUnitario;
                                                $data['series'] = filled($data['series'] ?? null)
                                                    ? array_values(array_filter(array_map('trim', preg_split('/[,\n]+/', $data['series']))))
                                                    : null;
                                                $data['lotes'] = filled($data['lotes'] ?? null)
                                                    ? array_values(array_map(function ($item) {
                                                        [$numero, $cantidad] = array_map('trim', explode(':', $item, 2));

                                                        return ['numero_lote' => $numero, 'cantidad' => (float) $cantidad];
                                                    }, array_filter(preg_split('/[,\n]+/', $data['lotes']))))
                                                    : null;

                                                return $data;
                                            }),
                                    ]),
                            ]),
                        Tab::make('Costos adicionales')->icon('heroicon-o-receipt-percent')->badge(fn ($record) => $record?->gastosAdicionales()->count() ?? 0)->schema([
                            Section::make('Costos de importaciÃƒÆ’Ã‚Â³n y recepciÃƒÆ’Ã‚Â³n')->icon('heroicon-o-truck')->description('Registre flete, seguros, aranceles, impuestos no recuperables u otros costos antes de procesar inventario. Los gastos capitalizables se prorratean en el costo de los artÃƒÆ’Ã‚Â­culos.')->schema([
                                Repeater::make('gastosAdicionales')->relationship()->label('')->schema([
                                    Select::make('tipo')->label('Tipo')->options(['flete' => 'Flete / transporte', 'seguro' => 'Seguro', 'arancel' => 'Arancel', 'impuesto_no_recuperable' => 'Impuesto no recuperable', 'despacho' => 'Despacho aduanero', 'otro' => 'Otro'])->default('otro')->required(),
                                    TextInput::make('descripcion')->label('DescripciÃƒÆ’Ã‚Â³n')->required()->maxLength(255)->columnSpan(2),
                                    Select::make('moneda')->label('Moneda')->options(['BOB' => 'BOB', 'USD' => 'USD', 'EUR' => 'EUR'])->default('BOB')->required()->live()->afterStateUpdated(fn ($state, callable $set) => $set('tasa_cambio', $state === 'BOB' ? 1 : null)),
                                    TextInput::make('tasa_cambio')->label('Cambio a BOB')->numeric()->minValue(0.000001)->default(1)->required()->disabled(fn ($get): bool => ($get('moneda') ?? 'BOB') === 'BOB')->dehydrated(),
                                    TextInput::make('monto')->label('Importe')->numeric()->minValue(0)->default(0)->required(),
                                    Select::make('criterio_prorrateo')->label('Prorratear por')->options(['valor' => 'Valor', 'cantidad' => 'Cantidad'])->default('valor')->required(),
                                    Toggle::make('capitalizable')->label('Incorporar al costo de inventario')->default(true),
                                    TextInput::make('documento_referencia')->label('Documento de respaldo')->maxLength(100),
                                    Textarea::make('observaciones')->label('Observaciones')->rows(2)->columnSpanFull(),
                                ])->columns(4)->defaultItems(0)->addActionLabel('Agregar gasto')->columnSpanFull(),
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
                    ->label('CÃƒÆ’Ã‚Â³digo')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('CÃƒÆ’Ã‚Â³digo copiado')
                    ->toggleable()
                    ->width('120px')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('ordenCompra.codigo')
                    ->label('Orden Compra')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Parcial',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'info' => 'parcial',
                        'success' => 'completada',
                        'danger' => 'rechazada',
                    ])
                    ->toggleable(),

                TextColumn::make('total_items')
                    ->label('Items')
                    ->getStateUsing(fn ($record) => $record->detalles()->count())
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->width('60px'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Parcial',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('orden_compra_id')
                    ->label('Orden Compra')
                    ->relationship('ordenCompra', 'codigo')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->visible(fn ($record) => ! $record->inventario_procesado_at),

                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Action::make('procesar_ingreso')
                        ->label('Procesar ingreso')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar ingreso a inventario')
                        ->modalDescription('Revise cantidades aceptadas y almacÃƒÆ’Ã‚Â©n. Esta acciÃƒÆ’Ã‚Â³n crea el kardex una sola vez.')
                        ->action(function ($record) {
                            $record->procesarEntradaInventario();
                            Notification::make()
                                ->title('Ingreso procesado')
                                ->body('La recepciÃƒÆ’Ã‚Â³n '.$record->codigo.' ya actualizÃƒÆ’Ã‚Â³ el inventario.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ! $record->inventario_procesado_at && in_array($record->estado, ['pendiente', 'parcial'])),

                    DeleteAction::make()
                        ->visible(fn ($record) => $record->estado === 'pendiente'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar recepciÃƒÆ’Ã‚Â³n...')
            ->emptyStateHeading('No hay recepciones registradas')
            ->emptyStateDescription('Crea una recepciÃƒÆ’Ã‚Â³n para registrar ingreso de mercaderÃƒÆ’Ã‚Â­a.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRecepcions::route('/'),
            'create' => CreateRecepcion::route('/create'),
            'edit' => EditRecepcion::route('/{record}/edit'),
        ];
    }
}
