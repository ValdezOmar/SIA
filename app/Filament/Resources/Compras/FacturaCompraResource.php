<?php

namespace App\Filament\Resources\Compras;

use App\Filament\Resources\Compras\FacturaCompraResource\Pages;
use App\Models\Compras\FacturaCompra;
use App\Models\Compras\OrdenCompra;
use App\Models\Compras\Proveedor;
use App\Models\Compras\Recepcion;
use App\Models\Contabilidad\AsientoContable;
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

class FacturaCompraResource extends Resource
{
    protected static ?string $model = FacturaCompra::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Facturas de Compra';

    protected static ?string $modelLabel = 'Factura de Compra';

    protected static ?string $pluralModelLabel = 'Facturas de Compra';

    protected static ?int $navigationSort = 4;

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
                Tabs::make('Gestión de Factura')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Factura')
                                    ->icon('heroicon-o-document-text')
                                    ->description('Información principal de la factura de compra')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->disabled()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('FAC-000001')
                                                    ->helperText('Código único de la factura')
                                                    ->default(fn () => FacturaCompra::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                TextInput::make('numero_factura')
                                                    ->label('Número Factura')
                                                    ->maxLength(50)
                                                    ->placeholder('Número de factura del proveedor')
                                                    ->helperText('Número de factura del proveedor')
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_emision')
                                                    ->label('Fecha Emisión')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de emisión de la factura')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => 'Borrador',
                                                        'registrada' => 'Registrada',
                                                        'pagada' => 'Pagada',
                                                        'parcial' => 'Parcial',
                                                        'anulada' => 'Anulada',
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
                                                            ->pluck('nombre', 'id')
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un proveedor')
                                                    ->helperText('Proveedor de la factura')
                                                    ->prefixIcon('heroicon-o-building-office-2')
                                                    ->reactive()
                                                    ->columnSpan(2),

                                                Select::make('orden_compra_id')
                                                    ->label('Orden de Compra')
                                                    ->options(
                                                        fn () => OrdenCompra::whereIn('estado', ['confirmada', 'parcial', 'recibida', 'completada'])
                                                            ->orderBy('codigo')
                                                            ->pluck('codigo', 'id')
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una orden')
                                                    ->helperText('Orden de compra asociada')
                                                    ->prefixIcon('heroicon-o-shopping-cart')
                                                    ->columnSpan(1),

                                                Select::make('recepcion_id')
                                                    ->label('Recepción')
                                                    ->options(
                                                        fn ($get) => Recepcion::where('orden_compra_id', $get('orden_compra_id'))
                                                            ->whereIn('estado', ['parcial', 'completada'])
                                                            ->orderBy('codigo')
                                                            ->pluck('codigo', 'id')
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una recepción')
                                                    ->helperText('Recepción asociada')
                                                    ->prefixIcon('heroicon-o-inbox')
                                                    ->visible(fn ($get) => $get('orden_compra_id'))
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
                                                    ->helperText('Moneda de la factura')
                                                    ->prefixIcon('heroicon-o-currency-dollar')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_vencimiento')
                                                    ->label('Fecha Vencimiento')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(30))
                                                    ->native(false)
                                                    ->helperText('Fecha de vencimiento de la factura')
                                                    ->prefixIcon('heroicon-o-calendar-days')
                                                    ->columnSpan(1),

                                                TextInput::make('condicion_pago')
                                                    ->label('Condición de Pago')
                                                    ->maxLength(100)
                                                    ->placeholder('Crédito 30 días')
                                                    ->helperText('Condiciones de pago')
                                                    ->prefixIcon('heroicon-o-credit-card')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Totales')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(6)
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
                                                    ->label('Impuesto')
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

                                                Placeholder::make('monto_pagado')
                                                    ->label('Pagado')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $pagado = $record?->monto_pagado ?? 0;

                                                        return self::formatearMonto($pagado, $moneda);
                                                    }),

                                                Placeholder::make('saldo')
                                                    ->label('Saldo')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $total = floatval($record?->total ?? 0);
                                                        $pagado = floatval($record?->monto_pagado ?? 0);
                                                        $saldo = $total - $pagado;
                                                        $color = $saldo <= 0 ? 'text-success-600' : 'text-danger-600';

                                                        return self::formatearMontoHtml(
                                                            $saldo,
                                                            $moneda,
                                                            'font-bold text-lg '.$color
                                                        );
                                                    }),
                                            ]),
                                    ]),

                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales...')
                                    ->helperText('Información adicional sobre la factura')
                                    ->columnSpanFull(),
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
                                    ->description('Productos incluidos en la factura')
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
                                                            ->step(0.01)
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
                                                            ->step(0.01)
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
                                                            ->step(0.01)
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

                        Tabs\Tab::make('Pagos')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Información de Pagos')
                                    ->icon('heroicon-o-credit-card')
                                    ->schema([
                                        Placeholder::make('pagos_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (! $record) {
                                                    return 'Guardar la factura para gestionar pagos.';
                                                }

                                                $pagos = $record->pagos()->get();
                                                $totalPagos = $pagos->count();
                                                $totalMonto = $pagos->sum('monto');

                                                if ($totalPagos == 0) {
                                                    return new HtmlString(
                                                        '<div class="text-sm text-gray-500">No hay pagos registrados para esta factura.</div>'
                                                    );
                                                }

                                                $html = '<div class="space-y-2">';
                                                $html .= '<p class="text-sm font-medium">Total pagos: '.$totalPagos.' - Monto: '.self::formatearMonto($totalMonto, $record->moneda ?? 'BOB').'</p>';
                                                $html .= '<div class="grid grid-cols-1 gap-2">';
                                                foreach ($pagos as $pago) {
                                                    $html .= '<div class="flex justify-between items-center p-2 bg-gray-50 dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-700">';
                                                    $html .= '<span class="text-sm">'.$pago->codigo.' - '.$pago->fecha_pago->format('d/m/Y').'</span>';
                                                    $html .= '<span class="font-bold text-success-600">'.self::formatearMonto($pago->monto, $pago->moneda ?? 'BOB').'</span>';
                                                    $html .= '<span class="text-xs badge badge-'.($pago->estado === 'confirmado' ? 'success' : 'warning').'">'.ucfirst($pago->estado).'</span>';
                                                    $html .= '</div>';
                                                }
                                                $html .= '</div></div>';

                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
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

                TextColumn::make('numero_factura')
                    ->label('N° Factura')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'borrador' => 'Borrador',
                        'registrada' => 'Registrada',
                        'pagada' => 'Pagada',
                        'parcial' => 'Parcial',
                        'anulada' => 'Anulada',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'borrador',
                        'info' => 'registrada',
                        'success' => 'pagada',
                        'warning' => 'parcial',
                        'danger' => 'anulada',
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

                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->formatStateUsing(function ($state, $record) {
                        $moneda = $record->moneda ?? 'BOB';
                        $saldo = ($record->total ?? 0) - ($record->monto_pagado ?? 0);

                        return self::formatearMonto($saldo, $moneda);
                    })
                    ->sortable()
                    ->toggleable(),
                // ->color(fn($record) => {
                //     $saldo = ($record->total ?? 0) - ($record->monto_pagado ?? 0);
                //     return $saldo <= 0 ? 'success' : 'danger';
                // }),

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
                        'registrada' => 'Registrada',
                        'pagada' => 'Pagada',
                        'parcial' => 'Parcial',
                        'anulada' => 'Anulada',
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

                    Tables\Actions\Action::make('contabilizar')
                        ->label('Generar asiento')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Generar asiento de compra')
                        ->modalDescription('Se creará un asiento balanceado por la factura. La operación solo puede realizarse una vez.')
                        ->action(function ($record): void {
                            $asiento = AsientoContable::crearDesdeCompra($record);

                            Notification::make()
                                ->title('Asiento generado')
                                ->body('Se registró el asiento '.$asiento->codigo.'.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record): bool => in_array($record->estado, ['registrada', 'parcial', 'pagada']) && ! AsientoContable::where('documento_tipo', 'compra')->where('documento_id', $record->id)->exists()),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('registrar_pago')
                        ->label('Registrar Pago')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->form([
                            TextInput::make('monto')
                                ->label('Monto a Pagar')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->maxValue(fn ($record) => ($record->total ?? 0) - ($record->monto_pagado ?? 0))
                                ->prefix(fn ($get, $record) => self::getSimboloMoneda($record->moneda ?? 'BOB')),

                            DatePicker::make('fecha_pago')
                                ->label('Fecha Pago')
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->default(now())
                                ->native(false),

                            Select::make('tipo_pago')
                                ->label('Tipo de Pago')
                                ->options([
                                    'efectivo' => 'Efectivo',
                                    'transferencia' => 'Transferencia',
                                    'cheque' => 'Cheque',
                                    'deposito' => 'Depósito',
                                    'nota_credito' => 'Nota de crédito',
                                    'otros' => 'Otros',
                                ])
                                ->required()
                                ->searchable(),
                        ])
                        ->action(function (array $data, $record) {
                            $pago = $record->registrarPago($data);
                            Notification::make()
                                ->title('Pago registrado exitosamente')
                                ->body('Se ha registrado el pago de '.$data['monto'].' '.$record->moneda)
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ! in_array($record->estado, ['pagada', 'anulada'])),

                    Tables\Actions\Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Anular Factura')
                        ->modalSubheading('¿Estás seguro de que deseas anular esta factura?')
                        ->action(function ($record) {
                            $record->update(['estado' => 'anulada']);
                            Notification::make()
                                ->title('Factura anulada')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ! in_array($record->estado, ['pagada', 'anulada'])),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->estado === 'borrador'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])

            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar factura de compra...')
            ->emptyStateHeading('No hay facturas de compra')
            ->emptyStateDescription('Crea una factura de compra para comenzar.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\PagosProveedorRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacturaCompras::route('/'),
            'create' => Pages\CreateFacturaCompra::route('/create'),
            'edit' => Pages\EditFacturaCompra::route('/{record}/edit'),
        ];
    }
}
