<?php

namespace App\Filament\Resources\Compras;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Forms\Components\CalculoRepeater;
use App\Forms\Components\ImporteVenta;
use App\Support\CalculoDetalle;
use App\Support\ArticuloSelectOptions;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Compras\FacturaCompraResource\Pages\ListFacturaCompras;
use App\Filament\Resources\Compras\FacturaCompraResource\Pages\CreateFacturaCompra;
use App\Filament\Resources\Compras\FacturaCompraResource\Pages\EditFacturaCompra;
use App\Filament\Resources\Compras\FacturaCompraResource\Pages;
use App\Filament\Resources\Compras\FacturaCompraResource\RelationManagers\PagosProveedorRelationManager;
use App\Models\Compras\FacturaCompra;
use App\Models\Compras\OrdenCompra;
use App\Models\Compras\Proveedor;
use App\Models\Compras\Recepcion;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Inventario\Articulo;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;

class FacturaCompraResource extends Resource
{
    protected static ?string $model = FacturaCompra::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Facturas de Compra';

    protected static ?string $modelLabel = 'Factura de Compra';

    protected static ?string $pluralModelLabel = 'Facturas de Compra';

    protected static ?int $navigationSort = 3;

    public static function canEdit(Model $record): bool
    {
        return ! in_array($record->estado, ['parcial', 'pagada', 'anulada'], true)
            && ! $record->pagos()->where('estado', 'confirmado')->exists();
    }

    public static function canDelete(Model $record): bool
    {
        return $record->estado === 'borrador' && ! $record->pagos()->exists();
    }

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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Gestión de Factura')
                    ->tabs([
                        Tab::make('General')
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
                                                    ->dehydrated()
                                                    ->maxLength(50)
                                                    ->rule(function () {
                                                        return function (string $attribute, mixed $value, \Closure $fail): void {
                                                            $duplicada = FacturaCompra::query()
                                                                ->where('codigo', $value)
                                                                ->whereHas('detalles')
                                                                ->exists();

                                                            if ($duplicada) {
                                                                $fail('Ya existe una factura completa con este código.');
                                                            }
                                                        };
                                                    })
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
                                                    ->native()
                                                    ->helperText('Fecha de emisión de la factura')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => 'Borrador',
                                                        'pagada' => 'Pagada',
                                                        'parcial' => 'Parcial',
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

                                        FileUpload::make('adjuntos')
                                            ->label('Factura emitida por el proveedor')
                                            ->helperText('Adjunte el PDF emitido por el proveedor. Puede añadir imágenes si el comprobante fue escaneado.')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
                                            ->directory('compras/facturas-proveedor')
                                            // ->required(fn ($get): bool => in_array($get('estado'), ['registrada', 'parcial', 'pagada'], true))
                                            ->columnSpanFull(),

                                        Grid::make(4)
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
                                                    ->live()
                                                    ->afterStateUpdated(fn ($state, callable $set) => $set('tasa_cambio', $state === 'BOB' ? 1 : null))
                                                    ->helperText('Moneda de la factura')
                                                    ->prefixIcon('heroicon-o-currency-dollar')
                                                    ->columnSpan(1),

                                                TextInput::make('tasa_cambio')
                                                    ->label('Tipo de cambio a BOB')
                                                    ->numeric()
                                                    ->minValue(0.000001)
                                                    ->step(0.000001)
                                                    ->default(1)
                                                    ->required(fn ($get): bool => ($get('moneda') ?? 'BOB') !== 'BOB')
                                                    ->disabled(fn ($get): bool => ($get('moneda') ?? 'BOB') === 'BOB')
                                                    ->dehydrated()
                                                    ->helperText('Cantidad de bolivianos equivalente a una unidad de la moneda seleccionada.')
                                                    ->prefixIcon('heroicon-o-arrows-right-left')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_vencimiento')
                                                    ->label('Fecha Vencimiento')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(30))
                                                    ->native()
                                                    ->helperText('Fecha de vencimiento de la factura')
                                                    ->prefixIcon('heroicon-o-calendar-days')
                                                    ->columnSpan(1),

                                                Select::make('condicion_pago')
                                                    ->label('Condicion de pago')
                                                    ->options(['contado' => 'Contado', 'parcial' => 'Pago parcial', 'credito' => 'Credito'])
                                                    ->default('contado')->required()->live()
                                                    ->helperText('Contado registra el total. Pago parcial registra el abono y mantiene el saldo pendiente.')
                                                    ->prefixIcon('heroicon-o-credit-card')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Totales')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(['default' => 1, 'sm' => 2, 'xl' => 6])
                                            ->schema([
                                                Placeholder::make('subtotal')
                                                    ->label('Subtotal neto')
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
                                                        $total = self::calcularTotales($get, $record)['total'];
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

                                Section::make('Pago y respaldo')
                                    ->description(fn ($get) => $get('condicion_pago') === 'contado'
                                        ? 'Al guardar se registrará un pago único por el total de la factura.'
                                        : 'Indique el importe abonado hoy y adjunte su respaldo. El saldo podrá completarse después desde Pagos y respaldos.')
                                    ->icon('heroicon-o-paper-clip')
                                    ->visible(fn ($get) => in_array($get('condicion_pago'), ['contado', 'parcial'], true))
                                    ->schema([
                                        TextInput::make('pago_monto')->label('Monto abonado ahora')->numeric()->minValue(0.01)
                                            ->visible(fn ($get) => $get('condicion_pago') === 'parcial')
                                            ->required(fn ($get) => $get('condicion_pago') === 'parcial')
                                            ->helperText('Debe ser menor que el total de la factura; el saldo quedará pendiente.'),
                                        DatePicker::make('pago_fecha')->label('Fecha de pago')->default(today())->required(fn ($get) => in_array($get('condicion_pago'), ['contado', 'parcial'], true)),
                                        Select::make('pago_tipo')->label('Método de pago')->options([
                                            'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque',
                                            'deposito' => 'Depósito', 'nota_credito' => 'Nota de crédito', 'otros' => 'Otro',
                                        ])->default('efectivo')->required(fn ($get) => in_array($get('condicion_pago'), ['contado', 'parcial'], true)),
                                        TextInput::make('pago_referencia')->label('Referencia del comprobante')->maxLength(100),
                                        FileUpload::make('pago_respaldos')->label('Respaldos del pago')->multiple()
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                                            ->directory('compras/pagos-proveedor')
                                            // ->required(fn ($get) => in_array($get('estado'), ['pagada', 'parcial'], true))
                                            ->helperText('Adjunte imágenes o PDF del pago. Es obligatorio para conservar la trazabilidad.'),
                                    ])->columns(2),

                                Textarea::make('observaciones')
                                    ->label('Observaciones')
                                    ->rows(3)
                                    ->placeholder('Observaciones adicionales...')
                                    ->helperText('Información adicional sobre la factura')
                                    ->columnSpanFull(),
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
                                Section::make('Detalle de Productos')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Productos incluidos en la factura')
                                    ->schema([
                                        CalculoRepeater::make('detalles')->calculo('compra')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                Grid::make(['default' => 1, 'lg' => 16])
                                                    ->schema([
                                                        Select::make('articulo_id')
                                                            ->label('Artículo')
                                                            ->allowHtml()
                                                            ->options(fn (): array => ArticuloSelectOptions::sinStock())
                                                            ->getSearchResultsUsing(fn (string $search): array => ArticuloSelectOptions::sinStock($search))
                                                            ->getOptionLabelUsing(fn ($value): ?string => ArticuloSelectOptions::labelSinStock($value))
                                                            ->required()
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Busque por código, modelo, nombre o marca')
                                                            ->prefixIcon('heroicon-o-cube')
                                                            ->helperText('La lista muestra foto, código, modelo, nombre comercial y marca.')
                                                            ->columnSpan(6)
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                if ($state && ($articulo = Articulo::find($state))) {
                                                                    $set('codigo_articulo', $articulo->codigo);
                                                                    $set('descripcion_articulo', $articulo->descripcion ?? $articulo->nombre_comercial ?? '');
                                                                    $set('unidad_medida', $articulo->unidadMedida?->abreviatura ?? 'UND');
                                                                }
                                                                self::recalcularLineaCompra($set, $get);
                                                            }),

                                                        TextInput::make('cantidad')
                                                            ->label('Cant.')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->maxValue(999999)
                                                            ->step(0.01)
                                                            ->default(1)
                                                            ->prefixIcon('heroicon-o-numbered-list')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                self::recalcularLineaCompra($set, $get);
                                                            })
                                                            ->columnSpan(4),

                                                        TextInput::make('precio_unitario')
                                                            ->label('Precio unit.')
                                                            ->numeric()
                                                            ->type('text')
                                                            ->required()
                                                            ->minValue(0)
                                                            ->maxValue(999999.99)
                                                            ->step(0.01)
                                                            ->inputMode('decimal')
                                                            ->default(0)
                                                            ->prefix(fn ($get) => self::getSimboloMoneda($get('../../moneda') ?? 'BOB'))
                                                            ->helperText('Precio por unidad')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                self::recalcularLineaCompra($set, $get);
                                                            })
                                                            ->columnSpan(4),

                                                        ImporteVenta::make('subtotal_linea')
                                                            ->label('Subtotal neto')
                                                            ->content(fn ($get) => self::formatearMonto($get('subtotal') ?? 0, $get('../../moneda') ?? 'BOB'))
                                                            ->extraAttributes(['class' => 'font-bold'])
                                                            ->columnSpan(2),
                                                    ]),

                                                Grid::make(['default' => 1, 'lg' => 16])
                                                    ->schema([
                                                        TextInput::make('descuento_porcentaje')
                                                            ->label('Descuento %')
                                                            ->numeric()
                                                            ->type('text')
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->step(0.01)
                                                            ->inputMode('decimal')
                                                            ->default(0)
                                                            ->suffix('%')
                                                            ->prefixIcon('heroicon-o-percent-badge')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $set('_descuento_tipo', 'porcentaje');
                                                                self::recalcularLineaCompra($set, $get);
                                                            })
                                                            ->columnSpan(3),

                                                        TextInput::make('descuento')
                                                            ->label('Descuento')
                                                            ->numeric()
                                                            ->type('text')
                                                            ->minValue(0)
                                                            ->maxValue(fn ($get) => round(floatval($get('cantidad') ?? 0) * floatval($get('precio_unitario') ?? 0), 2))
                                                            ->step(0.01)
                                                            ->inputMode('decimal')
                                                            ->default(0)
                                                            ->prefix(fn ($get) => self::getSimboloMoneda($get('../../moneda') ?? 'BOB'))
                                                            ->prefixIcon('heroicon-o-gift')
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $set('_descuento_tipo', 'importe');
                                                                self::recalcularLineaCompra($set, $get);
                                                            })
                                                            ->columnSpan(3),

                                                        Toggle::make('aplicar_iva')
                                                            ->label('IVA 13%')
                                                            ->default(false)
                                                            ->helperText('Incluye crédito fiscal en esta línea.')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                self::recalcularLineaCompra($set, $get);
                                                            })
                                                            ->columnSpan(4),

                                                        ImporteVenta::make('impuesto_linea')
                                                            ->label('IVA')
                                                            ->content(fn ($get) => self::formatearMonto($get('impuesto') ?? 0, $get('../../moneda') ?? 'BOB'))
                                                            ->columnSpan(4),

                                                        ImporteVenta::make('total_con_iva')
                                                            ->label('Total')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';

                                                                return new HtmlString('<span class="text-lg font-bold text-success-600 dark:text-success-400">'.self::formatearMonto($get('total') ?? 0, $moneda).'</span>');
                                                            })
                                                            ->extraAttributes(['class' => 'flex items-center'])
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

                                                $data = CalculoDetalle::calcular($data, 'compra');

                                                $data['codigo_articulo'] = $articulo ? $articulo->codigo : 'SIN_CODIGO';
                                                $data['descripcion_articulo'] = $articulo ? ($articulo->descripcion ?? $articulo->nombre_comercial ?? 'Sin descripción') : '';
                                                $data['unidad_medida'] = $articulo ? ($articulo->unidadMedida?->abreviatura ?? 'UND') : 'UND';
                                                // Es un campo visual del repetidor; esta tabla persiste el importe de descuento.
                                                unset($data['descuento_porcentaje'], $data['_descuento_tipo']);

                                                return $data;
                                            }),
                                    ]),
                            ]),

                        Tab::make('Pagos')
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
            ])
            ->disabled(fn (?FacturaCompra $record) => $record && ((float) $record->monto_pagado > 0 || in_array($record->estado, ['pagada', 'anulada'], true)));
    }

    private static function calcularTotales($get, $record = null): array
    {
        // El formulario es la fuente actual; un arreglo vacío significa que se eliminaron las filas.
        $detalles = $get('detalles');
        if ($detalles === null) {
            $detalles = $record?->detalles()->get() ?? [];
        }

        return CalculoDetalle::totales($detalles);
    }

    private static function recalcularTotales(callable $set, callable $get): void
    {
        $totales = self::calcularTotales($get);
        $set('subtotal', $totales['subtotal']);
        $set('descuento', $totales['descuento']);
        $set('impuesto', $totales['impuesto']);
        $set('total', $totales['total']);
    }

    /** Mantiene la misma regla de cálculo que Facturas de Venta para cada línea. */
    private static function recalcularLineaCompra(callable $set, callable $get): void
    {
        $linea = CalculoDetalle::calcular([
            'cantidad' => $get('cantidad') ?? 0,
            'precio_unitario' => $get('precio_unitario') ?? 0,
            'descuento' => $get('descuento') ?? 0,
            'descuento_porcentaje' => $get('descuento_porcentaje') ?? 0,
            '_descuento_tipo' => $get('_descuento_tipo') ?? 'importe',
            'aplicar_iva' => $get('aplicar_iva') ?? false,
        ], 'compra');

        foreach (['descuento', 'subtotal', 'impuesto', 'total'] as $campo) {
            $set($campo, $linea[$campo]);
        }

        $base = round((float) ($linea['cantidad'] ?? 0) * (float) ($linea['precio_unitario'] ?? 0), 6);
        $set('descuento_porcentaje', $base > 0 ? round(((float) $linea['descuento'] / $base) * 100, 6) : 0);
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
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Action::make('asociar_recepcion')
                        ->label('Asociar recepción')
                        ->icon('heroicon-o-link')
                        ->color('gray')
                        ->modalHeading('Asociar recepción física')
                        ->modalDescription('Seleccione la recepción que confirma la entrega física. El stock continuará sin cambios hasta que esa recepción sea procesada en Almacén.')
                        ->schema([
                            Select::make('recepcion_id')
                                ->label('Recepción confirmada')
                                ->options(fn ($record) => Recepcion::query()
                                    ->where('orden_compra_id', $record->orden_compra_id)
                                    ->whereIn('estado', ['parcial', 'completada'])
                                    ->orderBy('codigo')
                                    ->pluck('codigo', 'id'))
                                ->required()
                                ->searchable()
                                ->helperText('Solo se muestran recepciones de la orden de compra de esta factura.'),
                        ])
                        ->action(fn (array $data, $record) => $record->update(['recepcion_id' => $data['recepcion_id']]))
                        ->visible(fn ($record): bool => ! $record->recepcion_id
                            && (bool) $record->orden_compra_id
                            && in_array($record->estado, ['registrada', 'parcial', 'pagada'], true)),

                    Action::make('contabilizar')
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
                        ->visible(fn ($record): bool => in_array($record->estado, ['registrada', 'parcial', 'pagada'], true)
                            && $record->recepcion?->estado === 'completada'
                            && $record->recepcion?->inventario_procesado_at
                            && ! AsientoContable::where('documento_tipo', 'compra')->where('documento_id', $record->id)->exists()),

                    ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Action::make('registrar_pago')
                        ->label('Registrar Pago')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->schema([
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
                                ->native(),

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
                        ->visible(fn () => false),

                    Action::make('anular')
                        ->label('Anular factura')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->schema([
                            Textarea::make('motivo')->label('Motivo de la anulación')->required()->maxLength(2000),
                        ])
                        ->modalHeading('Anular Factura')
                        ->modalSubheading('¿Estás seguro de que deseas anular esta factura?')
                        ->action(function (array $data, $record) {
                            $record->anularDocumento($data['motivo']);
                            Notification::make()
                                ->title('Factura anulada')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->estado !== 'anulada'),

                    DeleteAction::make()
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
        return [PagosProveedorRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFacturaCompras::route('/'),
            'create' => CreateFacturaCompra::route('/create'),
            'edit' => EditFacturaCompra::route('/{record}/edit'),
        ];
    }
}
