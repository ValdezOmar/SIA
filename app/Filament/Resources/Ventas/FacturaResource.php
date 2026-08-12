<?php

namespace App\Filament\Resources\Ventas;

use App\Filament\Resources\Ventas\FacturaResource\Pages;
use App\Filament\Resources\Ventas\FacturaResource\RelationManagers\PagosRelationManager;
use App\Models\Ventas\Factura;
use App\Models\Ventas\Cliente;
use App\Models\Ventas\Pedido;
use App\Models\Inventario\Articulo;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class FacturaResource extends Resource
{
    protected static ?string $model = Factura::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $navigationLabel = 'Facturas';

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas';

    protected static ?int $navigationSort = 4;

    // ========== MÉTODOS DE CÁLCULO ==========
    private static function recalcularLinea(callable $set, callable $get): void
    {
        $cantidad = floatval($get('cantidad') ?? 1);
        $precioUnitario = floatval($get('precio_unitario') ?? 0);
        $descuento = floatval($get('descuento') ?? 0);

        //Calcular subtotal
        $subtotal = round(($cantidad * $precioUnitario) - $descuento, 2);
        $set('subtotal', $subtotal);

        //Calcular impuesto y total basado en el toggle
        self::calcularImpuestoYTotal($set, $get);
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
            if ($detallesBD->isNotEmpty()) {
                foreach ($detallesBD as $detalle) {
                    $subtotal += floatval($detalle->subtotal ?? 0);
                    $descuento += floatval($detalle->descuento ?? 0);
                    $impuesto += floatval($detalle->impuesto ?? 0);
                    $total += floatval($detalle->total ?? 0);
                }
                return compact('subtotal', 'descuento', 'impuesto', 'total');
            }
        }

        $detalles = $get('detalles') ?? [];
        if (is_array($detalles) && !empty($detalles)) {
            foreach ($detalles as $detalle) {
                if (is_array($detalle)) {
                    $subtotal += floatval($detalle['subtotal'] ?? 0);
                    $descuento += floatval($detalle['descuento'] ?? 0);
                    $impuesto += floatval($detalle['impuesto'] ?? 0);
                    $total += floatval($detalle['total'] ?? 0);
                }
            }
        }

        return compact('subtotal', 'descuento', 'impuesto', 'total');
    }

    private static function getSimboloMoneda($moneda): string
    {
        return match ($moneda) {
            'BOB' => 'Bs',
            'USD' => '$',
            'EUR' => '€',
            default => $moneda,
        };
    }

    private static function formatearMonto($monto, $moneda): string
    {
        $simbolo = self::getSimboloMoneda($moneda);
        return $simbolo . ' ' . number_format($monto ?? 0, 2);
    }

    private static function formatearMontoHtml($monto, $moneda, $clase = ''): HtmlString
    {
        $simbolo = self::getSimboloMoneda($moneda);
        return new HtmlString(
            '<span class="' . $clase . '">' .
                $simbolo . ' ' . number_format($monto ?? 0, 2) .
                '</span>'
        );
    }

    private static function calcularImpuestoYTotal(callable $set, callable $get): void
    {
        $subtotal = floatval($get('subtotal') ?? 0);
        $aplicarIVA = $get('aplicar_iva') ?? false;
        $tasaIVA = 13;

        if ($aplicarIVA) {
            $impuesto = round($subtotal * ($tasaIVA / 100), 2);
            $total = round($subtotal + $impuesto, 2);
        } else {
            $impuesto = 0;
            $total = $subtotal;
        }

        $set('impuesto', $impuesto);
        $set('total', $total);
    }

    private static function formatearNumero($valor, $decimales = 2): string
    {
        if ($valor === null || $valor === '') return '0';

        $valor = floatval($valor);

        if ($valor == 0 || $valor == intval($valor)) {
            return (string) intval($valor);
        }

        return number_format($valor, $decimales, '.', '');
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
                                    ->description('Información principal de la factura')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('numero')
                                                    ->label('Número')
                                                    ->required()
                                                    ->disabled()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('FAC-000001')
                                                    ->helperText('Número único de la factura')
                                                    ->default(fn() => Factura::generarNumero())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                TextInput::make('serie')
                                                    ->label('Serie')
                                                    ->maxLength(20)
                                                    ->placeholder('F001')
                                                    ->helperText('Serie de la factura')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_emision')
                                                    ->label('Fecha Emisión')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de emisión')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => '📝 Borrador',
                                                        'emitida' => '📤 Emitida',
                                                        'pagada' => '✅ Pagada',
                                                        'parcial' => '💰 Parcial',
                                                        'vencida' => '⏰ Vencida',
                                                        'anulada' => '❌ Anulada',
                                                    ])
                                                    ->default('emitida')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Select::make('cliente_id')
                                                    ->label('Cliente')
                                                    ->options(
                                                        fn() => Cliente::where('activo', true)
                                                            ->orderBy('nombre')
                                                            ->pluck('nombre', 'id')
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un cliente')
                                                    ->helperText('Cliente destino')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->columnSpan(2)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $cliente = Cliente::find($state);
                                                            if ($cliente) $set('condicion_pago', $cliente->condicion_pago);
                                                        }
                                                    })
                                                    ->createOptionForm([
                                                        Section::make('Nuevo Cliente')
                                                            ->icon('heroicon-o-user-plus')
                                                            ->description('Complete los datos del nuevo cliente')
                                                            ->schema([
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('codigo')
                                                                            ->label('Código')
                                                                            ->required()
                                                                            ->disabled()
                                                                            ->maxLength(50)
                                                                            ->unique(ignoreRecord: true)
                                                                            ->default(fn() => Cliente::generarCodigo())
                                                                            ->prefixIcon('heroicon-o-hashtag')
                                                                            ->columnSpan(1),
                                                                        TextInput::make('nombre')
                                                                            ->label('Nombre / Razón Social')
                                                                            ->required()
                                                                            ->maxLength(255)
                                                                            ->placeholder('Ej: Juan Pérez')
                                                                            ->prefixIcon('heroicon-o-user')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('ci/nit')
                                                                            ->label('CI / NIT')
                                                                            ->maxLength(50)
                                                                            ->placeholder('Ej: 123456789')
                                                                            ->prefixIcon('heroicon-o-identification')
                                                                            ->columnSpan(1),
                                                                        Select::make('tipo_cliente')
                                                                            ->label('Tipo')
                                                                            ->options([
                                                                                'persona_natural' => '👤 Natural',
                                                                                'empresa' => '🏢 Empresa',
                                                                                'gobierno' => '🏛️ Gobierno',
                                                                                'extranjero' => '🌍 Extranjero',
                                                                            ])
                                                                            ->default('persona_natural')
                                                                            ->prefixIcon('heroicon-o-tag')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('telefono')
                                                                            ->label('Teléfono')
                                                                            ->maxLength(50)
                                                                            ->placeholder('Ej: (591) 2-1234567')
                                                                            ->prefixIcon('heroicon-o-phone')
                                                                            ->columnSpan(1),
                                                                        TextInput::make('celular')
                                                                            ->label('Celular')
                                                                            ->maxLength(50)
                                                                            ->placeholder('Ej: (591) 7-1234567')
                                                                            ->prefixIcon('heroicon-o-device-phone-mobile')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                TextInput::make('correo')
                                                                    ->label('Correo')
                                                                    ->email()
                                                                    ->maxLength(255)
                                                                    ->placeholder('cliente@email.com')
                                                                    ->prefixIcon('heroicon-o-envelope')
                                                                    ->columnSpanFull(),
                                                                Textarea::make('direccion')
                                                                    ->label('Dirección')
                                                                    ->rows(2)
                                                                    ->placeholder('Av. Principal #123')
                                                                    ->columnSpanFull(),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        Select::make('ciudad')
                                                                            ->label('Departamento')
                                                                            ->options([
                                                                                'Beni' => 'Beni',
                                                                                'Chuquisaca' => 'Chuquisaca',
                                                                                'Cochabamba' => 'Cochabamba',
                                                                                'La Paz' => 'La Paz',
                                                                                'Oruro' => 'Oruro',
                                                                                'Pando' => 'Pando',
                                                                                'Potosí' => 'Potosí',
                                                                                'Santa Cruz' => 'Santa Cruz',
                                                                                'Tarija' => 'Tarija',
                                                                            ])
                                                                            ->searchable()
                                                                            ->preload()
                                                                            ->placeholder('Seleccione')
                                                                            ->prefixIcon('heroicon-o-map-pin')
                                                                            ->columnSpan(1),
                                                                        TextInput::make('zona')
                                                                            ->label('Zona')
                                                                            ->maxLength(100)
                                                                            ->placeholder('Equipetrol')
                                                                            ->prefixIcon('heroicon-o-building-library')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                TextInput::make('condicion_pago')
                                                                    ->label('Condición de Pago')
                                                                    ->maxLength(100)
                                                                    ->placeholder('Crédito 30 días')
                                                                    ->prefixIcon('heroicon-o-credit-card')
                                                                    ->columnSpanFull(),
                                                                Toggle::make('activo')
                                                                    ->label('Cliente Activo')
                                                                    ->default(true)
                                                                    ->columnSpanFull(),
                                                            ]),
                                                    ])
                                                    ->createOptionUsing(function (array $data): int {
                                                        $data['codigo'] = $data['codigo'] ?? Cliente::generarCodigo();
                                                        $data['activo'] = $data['activo'] ?? true;
                                                        $data['categoria'] = 'regular';
                                                        $data['creado_por'] = Auth::id();
                                                        $data['empresa_id'] = Auth::user()?->empresa_id ?? 1;
                                                        $cliente = Cliente::create($data);
                                                        return $cliente->id;
                                                    }),

                                                Select::make('pedido_id')
                                                    ->label('Pedido Asociado')
                                                    ->options(
                                                        fn() => Pedido::where('estado', '!=', 'cancelado')
                                                            ->orderBy('codigo')
                                                            ->pluck('codigo', 'id')
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un pedido')
                                                    ->helperText('Pedido asociado a esta factura')
                                                    ->prefixIcon('heroicon-o-shopping-cart')
                                                    ->columnSpan(1)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            $pedido = Pedido::with(['detalles.articulo'])->find($state);
                                                            if ($pedido) {
                                                                // Datos del pedido
                                                                $set('cliente_id', $pedido->cliente_id);
                                                                $set('condicion_pago', $pedido->condicion_pago);
                                                                $set('moneda', $pedido->moneda);
                                                                $set('tasa_cambio', $pedido->tasa_cambio);
                                                                $set('numero_pedido', $pedido->codigo);

                                                                // Cargar los detalles del pedido en el repeater
                                                                $detalles = [];
                                                                foreach ($pedido->detalles as $detalle) {
                                                                    $detalles[] = [
                                                                        'articulo_id' => $detalle->articulo_id,
                                                                        'codigo_articulo' => $detalle->codigo_articulo,
                                                                        'descripcion_articulo' => $detalle->descripcion_articulo,
                                                                        'unidad_medida' => $detalle->unidad_medida,
                                                                        'cantidad' => $detalle->cantidad,
                                                                        'precio_unitario' => $detalle->precio_unitario,
                                                                        'descuento' => $detalle->descuento,
                                                                        'descuento_porcentaje' => $detalle->descuento_porcentaje,
                                                                        'subtotal' => $detalle->subtotal,
                                                                        'impuesto' => $detalle->impuesto,
                                                                        'total' => $detalle->total,
                                                                        'observaciones' => $detalle->observaciones,
                                                                    ];
                                                                }
                                                                $set('detalles', $detalles);

                                                                // Recalcular totales
                                                                $totales = self::calcularTotales($get, null);
                                                                $set('subtotal', $totales['subtotal']);
                                                                $set('descuento', $totales['descuento']);
                                                                $set('impuesto', $totales['impuesto']);
                                                                $set('total', $totales['total']);
                                                            }
                                                        }
                                                    }),

                                                Select::make('vendedor_id')
                                                    ->label('Vendedor')
                                                    ->relationship('vendedor', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(Auth::id())
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->helperText('Vendedor responsable')
                                                    ->prefixIcon('heroicon-o-user-group')
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
                                                    ->live()
                                                    ->columnSpan(1),

                                                TextInput::make('tasa_cambio')
                                                    ->label('Tasa Cambio')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->step(0.000001)
                                                    ->helperText('Tasa de cambio aplicada')
                                                    ->prefixIcon('heroicon-o-arrow-path')
                                                    ->visible(fn($get) => $get('moneda') !== 'BOB')
                                                    ->columnSpan(1),

                                                Select::make('condicion_pago')
                                                    ->label('Condición Pago')
                                                    ->options([
                                                        'contado' => '💵 Contado',
                                                        'qr' => '📱 QR',
                                                        'qr_efectivo' => '📱 QR / Efectivo',
                                                        'transferencia' => '🏦 Transferencia',
                                                        'cheque' => '📄 Cheque',
                                                        'deposito' => '🏛️ Depósito',
                                                        'tarjeta' => '💳 Tarjeta',
                                                        'nota_credito' => '📝 Nota de Crédito',
                                                        'credito_15' => '📆 Crédito 15 días',
                                                        'credito_30' => '📆 Crédito 30 días',
                                                        'credito_45' => '📆 Crédito 45 días',
                                                        'credito_60' => '📆 Crédito 60 días',
                                                        'otros' => '📌 Otros',
                                                    ])
                                                    ->required()
                                                    ->default('contado')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione condición de pago')
                                                    ->helperText('Condiciones de pago')
                                                    ->prefixIcon('heroicon-o-credit-card')
                                                    ->columnSpan(1)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        // Si quieres que al seleccionar una condición de pago se actualice automáticamente la fecha de vencimiento
                                                        if ($state === 'contado') {
                                                            $set('fecha_vencimiento', now());
                                                        } elseif (str_starts_with($state, 'credito_')) {
                                                            $dias = intval(str_replace('credito_', '', $state));
                                                            $set('fecha_vencimiento', now()->addDays($dias));
                                                        }
                                                    }),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                DatePicker::make('fecha_vencimiento')
                                                    ->label('Fecha Vencimiento')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(30))
                                                    ->native(false)
                                                    ->helperText('Fecha de vencimiento de la factura')
                                                    ->prefixIcon('heroicon-o-calendar-days')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_pago')
                                                    ->label('Fecha Pago')
                                                    ->displayFormat('d/m/Y')
                                                    ->native(false)
                                                    ->helperText('Fecha en que se realizó el pago')
                                                    ->prefixIcon('heroicon-o-check-circle')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                // ========== SECCIÓN DE TOTALES ==========
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
                                                    })
                                                    ->extraAttributes(['class' => 'font-bold text-lg']),

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
                                                            'font-bold text-lg ' . $color
                                                        );
                                                    })
                                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: PRODUCTOS ==========
                        Tabs\Tab::make('Productos')
                            ->icon('heroicon-o-shopping-bag')
                            ->badge(function ($record) {
                                if (!$record) return 0;
                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle de Productos')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Artículos incluidos en la factura')
                                    ->schema([
                                        Repeater::make('detalles')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                Grid::make(16)
                                                    ->schema([
                                                        Select::make('articulo_id')
                                                            ->label('Artículo')
                                                            ->options(
                                                                fn() => Articulo::where('activo', true)
                                                                    ->where('vendible', true)
                                                                    ->orderBy('codigo')
                                                                    ->get()
                                                                    ->mapWithKeys(fn($item) => [
                                                                        $item->id => $item->codigo . ' - ' . ($item->descripcion ?? $item->nombre_comercial ?? 'Sin descripción')
                                                                    ])
                                                                    ->toArray()
                                                            )
                                                            ->required()
                                                            ->searchable(['descripcion', 'codigo'])
                                                            ->preload()
                                                            ->placeholder('Buscar artículo...')
                                                            ->prefixIcon('heroicon-o-cube')
                                                            ->columnSpan(6)
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                if ($state) {
                                                                    $articulo = Articulo::find($state);
                                                                    if ($articulo) {
                                                                        $precios = $articulo->getPreciosConListas();
                                                                        if ($precios->isNotEmpty()) {
                                                                            $primeraListaId = $precios->keys()->first();
                                                                            $precio = floatval($precios->first()['precio']);
                                                                            $set('lista_precio', $primeraListaId);
                                                                            $set('precio_unitario', $precio);
                                                                        } else {
                                                                            $set('precio_unitario', floatval($articulo->precio_base ?? 0));
                                                                        }
                                                                        self::recalcularLinea($set, $get);
                                                                    }
                                                                }
                                                            }),

                                                        Select::make('lista_precio')
                                                            ->label('Lista Precios')
                                                            ->options(function ($get) {
                                                                $articuloId = $get('articulo_id');
                                                                if (!$articuloId) return [];
                                                                $articulo = Articulo::find($articuloId);
                                                                if (!$articulo) return [];
                                                                $precios = $articulo->getPreciosConListas();
                                                                if ($precios->isEmpty()) return [];
                                                                return $precios->mapWithKeys(fn($item, $key) => [
                                                                    $key => $item['nombre'] . ' - ' . number_format($item['precio'], 2) . ' ' . $item['moneda']
                                                                ])->toArray();
                                                            })
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione lista')
                                                            ->helperText('Lista de precios')
                                                            ->columnSpan(4)
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                $articuloId = $get('articulo_id');
                                                                if ($articuloId && $state) {
                                                                    $articulo = Articulo::find($articuloId);
                                                                    if ($articulo) {
                                                                        $precio = floatval($articulo->getPrecioByLista($state));
                                                                        $set('precio_unitario', $precio);
                                                                        self::recalcularLinea($set, $get);
                                                                    }
                                                                }
                                                            }),

                                                        TextInput::make('cantidad')
                                                            ->label('Cant.')
                                                            ->integer()
                                                            ->required()
                                                            ->minValue(1)
                                                            ->maxValue(999999)
                                                            ->step(1.00)
                                                            ->default(1)
                                                            ->formatStateUsing(fn($state) => (int) $state)
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $state = intval($state);
                                                                $set('cantidad', $state);
                                                                self::recalcularLinea($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('precio_unitario')
                                                            ->label('Precio Unit.')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->maxValue(999999.99)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->prefix(fn($get) => self::getSimboloMoneda($get('../../moneda') ?? 'BOB'))
                                                            ->helperText('Precio por unidad')
                                                            ->formatStateUsing(fn($state) => self::formatearNumero($state, 2))
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::recalcularLinea($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('subtotal_linea')
                                                            ->label('Subtotal')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                return self::formatearMonto($get('subtotal') ?? 0, $moneda);
                                                            })
                                                            ->extraAttributes(['class' => 'font-bold'])
                                                            ->columnSpan(2),
                                                    ]),

                                                Grid::make(16)
                                                    ->schema([
                                                        TextInput::make('descuento_porcentaje')
                                                            ->label('Descuento %')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->step(0.01)
                                                            ->default(0)
                                                            ->suffix('%')
                                                            ->prefixIcon('heroicon-o-percent-badge')
                                                            ->live()
                                                            ->formatStateUsing(fn($state) => $state !== null ? round(floatval($state), 2) : 0)
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($get('cantidad') ?? 1);
                                                                $precio = floatval($get('precio_unitario') ?? 0);
                                                                $subtotalBase = round($cantidad * $precio, 2);
                                                                $descuento = round($subtotalBase * (floatval($state) / 100), 2);
                                                                $subtotal = round($subtotalBase - $descuento, 2);

                                                                $set('descuento', $descuento);
                                                                $set('subtotal', $subtotal);
                                                                self::calcularImpuestoYTotal($set, $get);
                                                            })
                                                            ->columnSpan(3),

                                                        TextInput::make('descuento')
                                                            ->label('Descuento')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(0.01)
                                                            ->default(0)
                                                            ->prefix(fn($get) => self::getSimboloMoneda($get('../../moneda') ?? 'BOB'))
                                                            ->prefixIcon('heroicon-o-gift')
                                                            ->live()
                                                            ->formatStateUsing(fn($state) => number_format($state ?? 0, 2, '.', ''))
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($get('cantidad') ?? 1);
                                                                $precio = floatval($get('precio_unitario') ?? 0);
                                                                $subtotalBase = round($cantidad * $precio, 2);
                                                                $descuento = round(floatval($state), 2);
                                                                $subtotal = round($subtotalBase - $descuento, 2);
                                                                // ✅ Redondear a 2 decimales el porcentaje
                                                                $descuentoPorcentaje = $subtotalBase > 0 ? round(($descuento / $subtotalBase) * 100, 2) : 0;

                                                                $set('subtotal', $subtotal);
                                                                $set('descuento_porcentaje', $descuentoPorcentaje);
                                                                self::calcularImpuestoYTotal($set, $get);
                                                            })
                                                            ->columnSpan(3),

                                                        Toggle::make('aplicar_iva')
                                                            ->label('IVA 13%')
                                                            ->default(false)
                                                            ->helperText('Aplicar impuesto')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                //Recalcular el subtotal base (sin IVA)
                                                                $cantidad = floatval($get('cantidad') ?? 1);
                                                                $precioUnitario = floatval($get('precio_unitario') ?? 0);
                                                                $descuento = floatval($get('descuento') ?? 0);
                                                                $subtotal = round(($cantidad * $precioUnitario) - $descuento, 2);

                                                                //Establecer subtotal
                                                                $set('subtotal', $subtotal);

                                                                //Calcular impuesto y total según el estado del toggle
                                                                $aplicarIVA = $state;
                                                                $tasaIVA = 13;

                                                                if ($aplicarIVA) {
                                                                    $impuesto = round($subtotal * ($tasaIVA / 100), 2);
                                                                    $total = round($subtotal + $impuesto, 2);
                                                                } else {
                                                                    $impuesto = 0;
                                                                    $total = $subtotal;
                                                                }

                                                                $set('impuesto', $impuesto);
                                                                $set('total', $total);
                                                            })
                                                            ->columnSpan(4),

                                                        Placeholder::make('impuesto_linea')
                                                            ->label('Impuesto')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                //Usar el valor actual del estado
                                                                $impuesto = floatval($get('impuesto') ?? 0);
                                                                return self::formatearMonto($impuesto, $moneda);
                                                            })
                                                            ->columnSpan(4),

                                                        Placeholder::make('total_con_iva')
                                                            ->label('Total')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                //Usar el valor actual del estado
                                                                $total = floatval($get('total') ?? 0);
                                                                return new HtmlString(
                                                                    '<span class="text-lg font-bold text-success-600 dark:text-success-400">' .
                                                                        self::formatearMonto($total, $moneda) .
                                                                        '</span>'
                                                                );
                                                            })
                                                            ->extraAttributes(['class' => 'flex items-center'])
                                                            ->columnSpan(2),

                                                        Placeholder::make('total_con_iva')
                                                            ->label('Total')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                $total = floatval($get('total') ?? 0);
                                                                return new HtmlString(
                                                                    '<span class="text-lg font-bold text-success-600 dark:text-success-400">' .
                                                                        self::formatearMonto($total, $moneda) .
                                                                        '</span>'
                                                                );
                                                            })
                                                            ->extraAttributes(['class' => 'flex items-center'])
                                                            ->columnSpan(2),
                                                    ]),

                                                TextInput::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->maxLength(255)
                                                    ->placeholder('Notas sobre este producto (opcional)')
                                                    ->prefixIcon('heroicon-o-clipboard-document')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(1)
                                            ->collapsible()
                                            ->cloneable()
                                            ->addActionLabel('➕ Agregar Producto')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                                $articulo = null;
                                                if (isset($data['articulo_id']) && $data['articulo_id']) {
                                                    $articulo = Articulo::find($data['articulo_id']);
                                                }

                                                $precioUnitario = floatval($data['precio_unitario'] ?? 0);
                                                $cantidad = floatval($data['cantidad'] ?? 1);
                                                $descuento = round(floatval($data['descuento'] ?? 0), 2);
                                                $subtotal = round(($precioUnitario * $cantidad) - $descuento, 2);

                                                $aplicarIVA = isset($data['aplicar_iva']) ? (bool)$data['aplicar_iva'] : false;
                                                if ($aplicarIVA) {
                                                    $impuesto = round($subtotal * (13 / 100), 2);
                                                    $total = round($subtotal + $impuesto, 2);
                                                } else {
                                                    $impuesto = 0;
                                                    $total = $subtotal;
                                                }

                                                return [
                                                    // Relaciones
                                                    'articulo_id' => $data['articulo_id'],
                                                    'lista_precio' => $data['lista_precio'] ?? null,

                                                    // Datos del artículo
                                                    'codigo_articulo' => $articulo ? ($articulo->codigo_alterno ?? $articulo->codigo ?? 'SIN_CODIGO') : 'SIN_CODIGO',
                                                    'descripcion_articulo' => $articulo ? ($articulo->descripcion ?? $articulo->nombre_comercial ?? 'SIN_DESCRIPCION') : 'SIN_DESCRIPCION',
                                                    'unidad_medida' => $articulo ? ($articulo->unidadMedida?->abreviatura ?? 'UND') : 'UND',

                                                    // Cantidades y precios
                                                    'cantidad' => $cantidad,
                                                    'precio_unitario' => $precioUnitario,
                                                    'precio_original' => $precioUnitario,

                                                    // Descuentos
                                                    'descuento' => $descuento,
                                                    'descuento_porcentaje' => round(floatval($data['descuento_porcentaje'] ?? 0), 2),

                                                    // Totales
                                                    'subtotal' => $subtotal,
                                                    'impuesto' => $impuesto,
                                                    'total' => $total,

                                                    // Impuestos
                                                    'tipo_impuesto' => $data['tipo_impuesto'] ?? 'IVA',
                                                    'tasa_impuesto' => round(floatval($data['tasa_impuesto'] ?? 13), 2),
                                                    'aplicar_iva' => $aplicarIVA,

                                                    // Observaciones
                                                    'observaciones' => $data['observaciones'] ?? null,

                                                    // Series y lotes
                                                    'series' => $data['series'] ?? null,
                                                    'lotes' => $data['lotes'] ?? null,
                                                ];
                                            })
                                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                                $data['subtotal'] = round(floatval($data['subtotal'] ?? 0), 2);
                                                $data['impuesto'] = round(floatval($data['impuesto'] ?? 0), 2);
                                                $data['total'] = round(floatval($data['total'] ?? 0), 2);
                                                $data['precio_original'] = round(floatval($data['precio_original'] ?? 0), 2);
                                                $data['descuento'] = round(floatval($data['descuento'] ?? 0), 2);
                                                $data['descuento_porcentaje'] = round(floatval($data['descuento_porcentaje'] ?? 0), 2);
                                                $data['aplicar_iva'] = isset($data['aplicar_iva']) ? (bool)$data['aplicar_iva'] : false;
                                                return $data;
                                            })
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
                                                if (!$record) {
                                                    return '<div class="text-sm text-gray-500">Guardar la factura para gestionar pagos.</div>';
                                                }

                                                $totalPagos = $record->pagos()->count();
                                                $totalMonto = $record->pagos()->sum('monto');

                                                return new HtmlString(
                                                    '<div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div class="text-center">
                                                                <p class="text-sm text-gray-600 dark:text-gray-400">Total Pagos</p>
                                                                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">' . $totalPagos . '</p>
                                                            </div>
                                                            <div class="text-center">
                                                                <p class="text-sm text-gray-600 dark:text-gray-400">Monto Pagado</p>
                                                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">' . self::formatearMonto($totalMonto, $record->moneda ?? 'BOB') . '</p>
                                                            </div>
                                                        </div>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 text-center mt-2">Gestiona los pagos en la pestaña "Pagos" en relaciones.</p>
                                                    </div>'
                                                );
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),


                        Tabs\Tab::make('Notas')
                            ->icon('heroicon-o-clipboard-document')
                            ->schema([
                                Section::make('Observaciones')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->schema([
                                        Textarea::make('observaciones')
                                            ->label('Observaciones Generales')
                                            ->rows(4)
                                            ->placeholder('Notas adicionales sobre la factura...')
                                            ->helperText('Información relevante')
                                            ->columnSpanFull(),

                                        TextInput::make('tipo_documento')
                                            ->label('Tipo de Documento')
                                            ->maxLength(50)
                                            ->default('FACTURA')
                                            ->helperText('Tipo de documento emitido')
                                            ->prefixIcon('heroicon-o-document-text')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Auditoría')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Información de Auditoría')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('creado_por')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? 'N/A')
                                                    ->columnSpan(1),

                                                Placeholder::make('created_at')
                                                    ->label('Fecha creación')
                                                    ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i') ?? 'N/A')
                                                    ->columnSpan(1),

                                                Placeholder::make('cobrador_id')
                                                    ->label('Cobrador')
                                                    ->content(fn($record) => $record?->cobrador?->name ?? 'N/A')
                                                    ->columnSpan(1),
                                            ]),
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
                TextColumn::make('numero')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Número copiado')
                    ->toggleable()
                    ->width('120px')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'borrador' => '📝 Borrador',
                        'emitida' => '📤 Emitida',
                        'pagada' => '✅ Pagada',
                        'parcial' => '💰 Parcial',
                        'vencida' => '⏰ Vencida',
                        'anulada' => '❌ Anulada',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'borrador',
                        'info' => 'emitida',
                        'success' => 'pagada',
                        'warning' => 'parcial',
                        'danger' => 'vencida',
                        'danger' => 'anulada',
                    ])
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(function ($state, $record) {
                        $moneda = $record->moneda ?? 'BOB';
                        $simbolo = match ($moneda) {
                            'BOB' => 'Bs',
                            'USD' => '$',
                            'EUR' => '€',
                            default => $moneda,
                        };
                        return $simbolo . ' ' . number_format($state ?? 0, 2);
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->formatStateUsing(function ($state, $record) {
                        $moneda = $record->moneda ?? 'BOB';
                        $simbolo = match ($moneda) {
                            'BOB' => 'Bs',
                            'USD' => '$',
                            'EUR' => '€',
                            default => $moneda,
                        };
                        $saldo = ($record->total ?? 0) - ($record->monto_pagado ?? 0);
                        return $simbolo . ' ' . number_format($saldo, 2);
                    })
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('vendedor.name')
                    ->label('Vendedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
                        'emitida' => 'Emitida',
                        'pagada' => 'Pagada',
                        'parcial' => 'Parcial',
                        'vencida' => 'Vencida',
                        'anulada' => 'Anulada',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('estado')
                    ->label('Pagada')
                    ->nullable()
                    ->trueLabel('Facturas pagadas')
                    ->falseLabel('Facturas pendientes')
                    ->queries(
                        true: fn($query) => $query->where('estado', 'pagada'),
                        false: fn($query) => $query->whereIn('estado', ['emitida', 'parcial', 'vencida']),
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

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
                                ->maxValue(fn($record) => ($record->total ?? 0) - ($record->monto_pagado ?? 0))
                                ->prefix(fn($get, $record) => self::getSimboloMoneda($record->moneda ?? 'BOB'))
                                ->helperText(fn($record) => 'Saldo pendiente: ' . self::formatearMonto(($record->total ?? 0) - ($record->monto_pagado ?? 0), $record->moneda ?? 'BOB')),

                            DatePicker::make('fecha_pago')
                                ->label('Fecha Pago')
                                ->displayFormat('d/m/Y')
                                ->required()
                                ->default(now())
                                ->native(false),

                            Select::make('tipo_pago')
                                ->label('Tipo de Pago')
                                ->options([
                                    'efectivo' => '💵 Efectivo',
                                    'transferencia' => '🏦 Transferencia',
                                    'cheque' => '📄 Cheque',
                                    'tarjeta' => '💳 Tarjeta',
                                    'deposito' => '🏛️ Depósito',
                                    'nota_credito' => '📝 Nota de Crédito',
                                    'otros' => '📌 Otros',
                                ])
                                ->required()
                                ->searchable()
                                ->prefixIcon('heroicon-o-credit-card'),

                            TextInput::make('referencia')
                                ->label('Referencia')
                                ->maxLength(100)
                                ->placeholder('Número de referencia'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->registrarPago($data);
                            Notification::make()
                                ->title('Pago registrado exitosamente')
                                ->body('Se ha registrado el pago de ' . $data['monto'] . ' ' . $record->moneda)
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => !in_array($record->estado, ['pagada', 'anulada'])),

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
                        ->visible(fn($record) => !in_array($record->estado, ['pagada', 'anulada'])),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => $record->estado === 'borrador'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make()
                //         ->visible(fn($records) => $records->every(fn($record) => $record->estado === 'borrador')),
                // ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar factura por número, cliente...')
            ->emptyStateHeading('No hay facturas registradas')
            ->emptyStateDescription('Crea tu primera factura para comenzar.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            PagosRelationManager::class,
            //NotasCreditoRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFacturas::route('/'),
            'create' => Pages\CreateFactura::route('/create'),
            'edit' => Pages\EditFactura::route('/{record}/edit'),
        ];
    }
}
