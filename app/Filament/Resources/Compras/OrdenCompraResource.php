<?php

namespace App\Filament\Resources\Compras;

use App\Filament\Resources\Compras\OrdenCompraResource\Pages;
use App\Models\Compras\CotizacionProveedor;
use App\Models\Compras\OrdenCompra;
use App\Models\Compras\Proveedor;
use App\Models\Compras\SolicitudCompra;
use App\Models\Inventario\Articulo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class OrdenCompraResource extends Resource
{
    protected static ?string $model = OrdenCompra::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Órdenes de Compra';

    protected static ?string $modelLabel = 'Orden de Compra';

    protected static ?string $pluralModelLabel = 'Órdenes de Compra';

    protected static ?int $navigationSort = 2;

    private static function getSimboloMoneda($moneda = 'BOB'): string
    {
        return match ($moneda) {
            'BOB' => 'Bs',
            'USD' => '$',
            'EUR' => '€',
            default => 'Bs',
        };
    }

    private static function formatearMonto($monto, $moneda = 'BOB'): string
    {
        return self::getSimboloMoneda($moneda).' '.number_format($monto ?? 0, 2);
    }

    private static function formatearMontoHtml($monto, $moneda = 'BOB', $clase = ''): HtmlString
    {
        return new HtmlString(
            '<span class="'.$clase.'">'.
                self::getSimboloMoneda($moneda).' '.number_format($monto ?? 0, 2).
                '</span>'
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Orden')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Orden')
                                    ->icon('heroicon-o-document-text')
                                    ->description('Información principal de la orden de compra')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->disabled()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('OC-000001')
                                                    ->helperText('Código único de la orden')
                                                    ->default(fn () => OrdenCompra::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_orden')
                                                    ->label('Fecha Orden')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de la orden')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_entrega_estimada')
                                                    ->label('Entrega Estimada')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(15))
                                                    ->native(false)
                                                    ->helperText('Fecha estimada de entrega')
                                                    ->prefixIcon('heroicon-o-calendar-days')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => 'Borrador',
                                                        'enviada' => 'Enviada',
                                                        'confirmada' => 'Confirmada',
                                                        'parcial' => 'Parcial',
                                                        'recibida' => 'Recibida',
                                                        'completada' => 'Completada',
                                                        'cancelada' => 'Cancelada',
                                                    ])
                                                    ->default('borrador')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Select::make('proveedor_id')
                                                    ->label('Proveedor')
                                                    ->options(
                                                        fn () => Proveedor::where('activo', true)
                                                            ->orderBy('nombre')
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
                                                                $item->id => $item->codigo.' - '.$item->nombre,
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable(['nombre', 'codigo'])
                                                    ->preload()
                                                    ->placeholder('Seleccione un proveedor')
                                                    ->helperText('Proveedor de la orden')
                                                    ->prefixIcon('heroicon-o-building-office-2')
                                                    ->reactive()
                                                    ->columnSpan(2),

                                                Select::make('solicitud_id')
                                                    ->label('Solicitud Origen')
                                                    ->options(
                                                        fn () => SolicitudCompra::where('estado', 'aprobada')
                                                            ->orderBy('codigo')
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
                                                                $item->id => $item->codigo.' - '.($item->solicitante?->name ?? 'Sin solicitante'),
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una solicitud')
                                                    ->helperText('Solicitud de compra origen')
                                                    ->prefixIcon('heroicon-o-document-plus')
                                                    ->reactive()
                                                    ->columnSpan(1),

                                                Select::make('cotizacion_proveedor_id')
                                                    ->label('Cotización')
                                                    ->options(
                                                        fn ($get) => CotizacionProveedor::where('proveedor_id', $get('proveedor_id'))
                                                            ->where('estado', 'aceptada')
                                                            ->orderBy('codigo')
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
                                                                $item->id => $item->codigo.' - '.self::formatearMonto($item->total, $item->moneda ?? 'BOB'),
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una cotización')
                                                    ->helperText('Cotización de proveedor asociada')
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->visible(fn ($get) => $get('proveedor_id'))
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('moneda')
                                                    ->label('Moneda')
                                                    ->options([
                                                        'BOB' => '🇧🇴 Bolivianos',
                                                        'USD' => '🇺🇸 Dólares',
                                                        'EUR' => '🇪🇺 Euros',
                                                    ])
                                                    ->default('BOB')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Moneda de la orden')
                                                    ->prefixIcon('heroicon-o-currency-dollar')
                                                    ->columnSpan(1),

                                                TextInput::make('condicion_pago')
                                                    ->label('Condición de Pago')
                                                    ->maxLength(100)
                                                    ->placeholder('Crédito 30 días')
                                                    ->helperText('Condiciones de pago acordadas')
                                                    ->prefixIcon('heroicon-o-credit-card')
                                                    ->columnSpan(1),

                                                TextInput::make('metodo_envio')
                                                    ->label('Método de Envío')
                                                    ->maxLength(100)
                                                    ->placeholder('Transporte, Courier, etc.')
                                                    ->helperText('Método de envío acordado')
                                                    ->prefixIcon('heroicon-o-truck')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Totales')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                Placeholder::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $totales = self::calcularTotales($get, $record);

                                                        return self::formatearMonto($totales['subtotal'], $moneda);
                                                    }),

                                                Placeholder::make('descuento')
                                                    ->label('Descuento')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $totales = self::calcularTotales($get, $record);

                                                        return self::formatearMonto($totales['descuento'], $moneda);
                                                    }),

                                                Placeholder::make('impuesto')
                                                    ->label('Impuesto (13%)')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $totales = self::calcularTotales($get, $record);

                                                        return self::formatearMonto($totales['impuesto'], $moneda);
                                                    }),

                                                Placeholder::make('total')
                                                    ->label('Total')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $totales = self::calcularTotales($get, $record);

                                                        return self::formatearMontoHtml(
                                                            $totales['total'],
                                                            $moneda,
                                                            'font-bold text-lg text-primary-600 dark:text-primary-400'
                                                        );
                                                    }),
                                            ]),
                                    ]),

                                Section::make('Información Adicional')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Textarea::make('direccion_entrega')
                                                    ->label('Dirección de Entrega')
                                                    ->rows(3)
                                                    ->placeholder('Dirección completa de entrega...')
                                                    ->helperText('Lugar de entrega de la mercadería')
                                                    ->columnSpan(1),

                                                Textarea::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->rows(3)
                                                    ->placeholder('Observaciones adicionales...')
                                                    ->helperText('Información adicional sobre la orden')
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('terminos_condiciones')
                                            ->label('Términos y Condiciones')
                                            ->rows(3)
                                            ->placeholder('Términos y condiciones de la orden...')
                                            ->helperText('Términos y condiciones acordados')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Productos')
                            ->icon('heroicon-o-shopping-bag')
                            ->badge(function ($record) {
                                if (! $record) {
                                    return 0;
                                }

                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle de Productos')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Artículos incluidos en la orden')
                                    ->schema([
                                        Repeater::make('detalles')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                Grid::make(12)
                                                    ->schema([
                                                        Select::make('articulo_id')
                                                            ->label('Artículo')
                                                            ->options(
                                                                fn () => Articulo::where('activo', true)
                                                                    ->orderBy('codigo')
                                                                    ->get()
                                                                    ->mapWithKeys(fn ($item) => [
                                                                        $item->id => $item->codigo.' - '.($item->nombre_comercial ?? $item->descripcion ?? 'Sin descripción'),
                                                                    ])
                                                                    ->toArray()
                                                            )
                                                            ->required()
                                                            ->searchable(['codigo', 'descripcion', 'nombre_comercial'])
                                                            ->preload()
                                                            ->placeholder('Buscar artículo...')
                                                            ->prefixIcon('heroicon-o-cube')
                                                            ->columnSpan(4)
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                if ($state) {
                                                                    $articulo = Articulo::find($state);
                                                                    if ($articulo) {
                                                                        $set('codigo_articulo', $articulo->codigo);
                                                                        $set('descripcion_articulo', $articulo->descripcion ?? $articulo->nombre_comercial ?? '');
                                                                        $set('unidad_medida', $articulo->unidadMedida?->abreviatura ?? 'UND');
                                                                    }
                                                                }
                                                            }),

                                                        TextInput::make('cantidad')
                                                            ->label('Cantidad')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->step(1.00)
                                                            ->default(1)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-numbered-list')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::recalcularTotales($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('precio_unitario')
                                                            ->label('Precio Unit.')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix(fn ($get) => self::getSimboloMoneda($get('../../moneda') ?? 'BOB'))
                                                            ->helperText('Precio por unidad')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::recalcularTotales($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('descuento')
                                                            ->label('Descuento')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix(fn ($get) => self::getSimboloMoneda($get('../../moneda') ?? 'BOB'))
                                                            ->prefixIcon('heroicon-o-gift')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::recalcularTotales($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('subtotal_linea')
                                                            ->label('Subtotal')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                $cantidad = floatval($get('cantidad') ?? 0);
                                                                $precio = floatval($get('precio_unitario') ?? 0);
                                                                $descuento = floatval($get('descuento') ?? 0);
                                                                $subtotal = ($cantidad * $precio) - $descuento;

                                                                return self::formatearMonto($subtotal, $moneda);
                                                            })
                                                            ->extraAttributes(['class' => 'font-bold'])
                                                            ->columnSpan(2),
                                                    ]),

                                                TextInput::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->maxLength(255)
                                                    ->placeholder('Observaciones sobre este producto...')
                                                    ->prefixIcon('heroicon-o-clipboard-document')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(1)
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

                                                $cantidad = floatval($data['cantidad'] ?? 1);
                                                $precio = floatval($data['precio_unitario'] ?? 0);
                                                $descuento = floatval($data['descuento'] ?? 0);
                                                $subtotal = ($cantidad * $precio) - $descuento;
                                                $impuesto = $subtotal * 0.13;
                                                $total = $subtotal + $impuesto;

                                                $data['codigo_articulo'] = $articulo ? $articulo->codigo : 'SIN_CODIGO';
                                                $data['descripcion_articulo'] = $articulo ? ($articulo->descripcion ?? $articulo->nombre_comercial ?? 'Sin descripción') : '';
                                                $data['unidad_medida'] = $articulo ? ($articulo->unidadMedida?->abreviatura ?? 'UND') : 'UND';
                                                $data['subtotal'] = $subtotal;
                                                $data['impuesto'] = $impuesto;
                                                $data['total'] = $total;

                                                return $data;
                                            }),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull(),
            ]);
    }

    private static function calcularTotales($get, $record = null): array
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        if ($record && $record->exists) {
            if ($record->subtotal > 0 || $record->total > 0) {
                return [
                    'subtotal' => floatval($record->subtotal ?? 0),
                    'descuento' => floatval($record->descuento ?? 0),
                    'impuesto' => floatval($record->impuesto ?? 0),
                    'total' => floatval($record->total ?? 0),
                ];
            }

            $detallesBD = $record->detalles()->get();
            foreach ($detallesBD as $detalle) {
                $subtotal += floatval($detalle->subtotal ?? 0);
                $descuento += floatval($detalle->descuento ?? 0);
                $impuesto += floatval($detalle->impuesto ?? 0);
                $total += floatval($detalle->total ?? 0);
            }

            return compact('subtotal', 'descuento', 'impuesto', 'total');
        }

        $detalles = $get('detalles') ?? [];
        foreach ($detalles as $detalle) {
            if (is_array($detalle)) {
                $cantidad = floatval($detalle['cantidad'] ?? 0);
                $precio = floatval($detalle['precio_unitario'] ?? 0);
                $descuentoItem = floatval($detalle['descuento'] ?? 0);
                $subtotal += ($cantidad * $precio) - $descuentoItem;
                $descuento += $descuentoItem;
            }
        }

        $impuesto = $subtotal * 0.13;
        $total = $subtotal + $impuesto;

        return compact('subtotal', 'descuento', 'impuesto', 'total');
    }

    private static function recalcularTotales(callable $set, callable $get): void
    {
        $totales = self::calcularTotales($get);
        $set('subtotal', $totales['subtotal']);
        $set('descuento', $totales['descuento']);
        $set('impuesto', $totales['impuesto']);
        $set('total', $totales['total']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->toggleable()
                    ->width('120px')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_orden')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_entrega_estimada')
                    ->label('Entrega Estimada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'success'),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'borrador' => 'Borrador',
                        'enviada' => 'Enviada',
                        'confirmada' => 'Confirmada',
                        'parcial' => 'Parcial',
                        'recibida' => 'Recibida',
                        'completada' => 'Completada',
                        'cancelada' => 'Cancelada',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'borrador',
                        'info' => 'enviada',
                        'success' => 'confirmada',
                        'warning' => 'parcial',
                        'success' => 'recibida',
                        'success' => 'completada',
                        'danger' => 'cancelada',
                    ])
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(function ($state, $record) {
                        $moneda = $record->moneda ?? 'BOB';

                        return self::formatearMonto($state, $moneda);
                    })
                    ->sortable()
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
                        'borrador' => 'Borrador',
                        'enviada' => 'Enviada',
                        'confirmada' => 'Confirmada',
                        'parcial' => 'Parcial',
                        'recibida' => 'Recibida',
                        'completada' => 'Completada',
                        'cancelada' => 'Cancelada',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('enviar')
                        ->label('Enviar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->action(function ($record) {
                            $record->enviar();
                            Notification::make()
                                ->title('Orden enviada')
                                ->body('La orden '.$record->codigo.' ha sido enviada al proveedor.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->estado === 'borrador'),

                    Tables\Actions\Action::make('confirmar')
                        ->label('Confirmar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->confirmar();
                            Notification::make()
                                ->title('Orden confirmada')
                                ->body('La orden '.$record->codigo.' ha sido confirmada.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->estado === 'enviada'),

                    Tables\Actions\Action::make('cancelar')
                        ->label('Cancelar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('motivo')
                                ->label('Motivo de cancelación')
                                ->rows(2)
                                ->placeholder('Indique el motivo...'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->cancelar($data['motivo'] ?? null);
                            Notification::make()
                                ->title('Orden cancelada')
                                ->body('La orden '.$record->codigo.' ha sido cancelada.')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn ($record) => ! in_array($record->estado, ['recibida', 'completada', 'cancelada'])),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => in_array($record->estado, ['borrador', 'cancelada'])),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])

            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar orden de compra...')
            ->emptyStateHeading('No hay órdenes de compra')
            ->emptyStateDescription('Crea una orden de compra para comenzar.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrdenCompras::route('/'),
            'create' => Pages\CreateOrdenCompra::route('/create'),
            'edit' => Pages\EditOrdenCompra::route('/{record}/edit'),
        ];
    }
}
