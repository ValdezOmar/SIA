<?php

namespace App\Filament\Resources\Ventas\ClienteResource\RelationManagers;

use App\Models\Inventario\Articulo;
use App\Models\Ventas\Pedido;
use App\Models\Ventas\Cliente;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;

class PedidosRelationManager extends RelationManager
{
    protected static string $relationship = 'pedidos';

    protected static ?string $title = 'Pedidos';

    protected static ?string $modelLabel = 'Pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    // ========== MÉTODOS DE CÁLCULO (igual que en PedidoResource) ==========
    private static function recalcularLinea(callable $set, callable $get): void
    {
        $cantidad = floatval($get('cantidad') ?? 1);
        $precioUnitario = floatval($get('precio_unitario') ?? 0);
        $precioOriginal = floatval($get('precio_original') ?? $precioUnitario);
        $descuento = floatval($get('descuento') ?? 0);

        $subtotal = ($cantidad * $precioUnitario) - $descuento;
        $aplicarIVA = $get('aplicar_iva') ?? false;
        $tasaIVA = 13;

        if ($aplicarIVA) {
            $impuesto = $subtotal * ($tasaIVA / 100);
            $total = $subtotal + $impuesto;
        } else {
            $impuesto = 0;
            $total = $subtotal;
        }

        $set('precio_original', $precioOriginal);
        $set('subtotal', $subtotal);
        $set('impuesto', $impuesto);
        $set('total', $total);
    }

    private static function calcularImpuestoYTotal(callable $set, callable $get): void
    {
        $subtotal = floatval($get('subtotal') ?? 0);
        $aplicarIVA = $get('aplicar_iva') ?? false;
        $tasaIVA = 13;

        if ($aplicarIVA) {
            $impuesto = $subtotal * ($tasaIVA / 100);
            $total = $subtotal + $impuesto;
        } else {
            $impuesto = 0;
            $total = $subtotal;
        }

        $set('impuesto', $impuesto);
        $set('total', $total);
    }

    private static function calcularTotales($get, $record = null): array
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        if ($record && $record->exists) {
            if ($record->subtotal > 0 || $record->total > 0 || $record->impuesto > 0) {
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
                } elseif (is_object($detalle)) {
                    $subtotal += floatval($detalle->subtotal ?? 0);
                    $descuento += floatval($detalle->descuento ?? 0);
                    $impuesto += floatval($detalle->impuesto ?? 0);
                    $total += floatval($detalle->total ?? 0);
                }
            }
        }

        return compact('subtotal', 'descuento', 'impuesto', 'total');
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Pedido')
                    ->tabs([

                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos del Pedido')
                                    ->icon('heroicon-o-document-text')
                                    ->description('Información principal del pedido')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->disabled()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('PED-000001')
                                                    ->helperText('Código único del pedido')
                                                    ->default(fn() => Pedido::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_pedido')
                                                    ->label('Fecha Pedido')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de creación del pedido')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_entrega_estimada')
                                                    ->label('Fecha Entrega Estimada')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(7))
                                                    ->native(false)
                                                    ->helperText('Fecha estimada de entrega')
                                                    ->prefixIcon('heroicon-o-truck')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'reservado' => '📌 Reservado',
                                                        'pendiente' => '⏳ Pendiente',
                                                        'parcial' => '📦 Parcial',
                                                        'despachado' => '🚚 Despachado',
                                                        'entregado' => '✅ Entregado',
                                                        'cancelado' => '❌ Cancelado',
                                                    ])
                                                    ->default('reservado')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual del pedido')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Placeholder::make('cliente_info')
                                                    ->label('Cliente')
                                                    ->content(function ($livewire) {
                                                        $cliente = $livewire->getOwnerRecord();
                                                        return $cliente ? $cliente->nombre : 'N/A';
                                                    })
                                                    ->columnSpan(2),

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

                                                Select::make('prioridad')
                                                    ->label('Prioridad')
                                                    ->options([
                                                        'baja' => '🟢 Baja',
                                                        'normal' => '🟡 Normal',
                                                        'alta' => '🟠 Alta',
                                                        'urgente' => '🔴 Urgente',
                                                    ])
                                                    ->default('normal')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Prioridad del pedido')
                                                    ->prefixIcon('heroicon-o-flag')
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
                                                    ->helperText('Moneda del pedido')
                                                    ->prefixIcon('heroicon-o-currency-dollar')
                                                    ->live()
                                                    ->columnSpan(1),

                                                TextInput::make('tasa_cambio')
                                                    ->label('Tasa Cambio')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->step(1)
                                                    ->helperText('Tasa de cambio aplicada')
                                                    ->prefixIcon('heroicon-o-arrow-path')
                                                    ->formatStateUsing(fn($state) => self::formatearNumero($state, 6))
                                                    ->visible(fn($get) => $get('moneda') !== 'BOB')
                                                    ->columnSpan(1),

                                                TextInput::make('condicion_pago')
                                                    ->label('Condición Pago')
                                                    ->maxLength(100)
                                                    ->placeholder('Crédito 30 días')
                                                    ->helperText('Condiciones de pago')
                                                    ->prefixIcon('heroicon-o-credit-card')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Envío')
                                    ->icon('heroicon-o-truck')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Textarea::make('direccion_envio')
                                                    ->label('Dirección de Envío')
                                                    ->rows(2)
                                                    ->placeholder('Dirección completa de envío')
                                                    ->helperText('Dirección donde se entregará el pedido')
                                                    ->prefixIcon('heroicon-o-map-pin')
                                                    ->columnSpan(2),

                                                Grid::make(1)
                                                    ->schema([
                                                        Select::make('metodo_envio')
                                                            ->label('Método de Envío')
                                                            ->options([
                                                                'recojo_tienda' => '🏪 Recojo en Tienda',
                                                                'delivery' => '🚚 Delivery',
                                                                'courier' => '📦 Courier',
                                                                'transporte' => '🚛 Transporte',
                                                            ])
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione método')
                                                            ->prefixIcon('heroicon-o-truck'),
                                                    ])
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('costo_envio')
                                                    ->label('Costo de Envío')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(1)
                                                    ->default(0)
                                                    ->prefix(fn($get) => self::getSimboloMoneda($get('moneda') ?? 'BOB'))
                                                    ->helperText('Costo del envío')
                                                    ->prefixIcon('heroicon-o-calculator')
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        $totales = self::calcularTotales($get, null);
                                                        $set('total', $totales['total'] + floatval($state));
                                                    })
                                                    ->columnSpan(1),

                                                TextInput::make('total_items')
                                                    ->label('Total Items')
                                                    ->disabled()
                                                    ->placeholder('0')
                                                    ->prefixIcon('heroicon-o-shopping-bag')
                                                    ->formatStateUsing(fn($record) => $record?->detalles()->count() ?? 0)
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Totales')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(5)
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

                                                Placeholder::make('costo_envio')
                                                    ->label('Costo Envío')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $costoEnvio = floatval($get('costo_envio') ?? $record?->costo_envio ?? 0);
                                                        return self::formatearMonto($costoEnvio, $moneda);
                                                    }),

                                                Placeholder::make('total')
                                                    ->label('Total')
                                                    ->content(function ($get, $record) {
                                                        $moneda = $get('moneda') ?? 'BOB';
                                                        $totales = self::calcularTotales($get, $record);
                                                        $costoEnvio = floatval($get('costo_envio') ?? $record?->costo_envio ?? 0);
                                                        $total = $totales['total'] + $costoEnvio;
                                                        return self::formatearMontoHtml(
                                                            $total,
                                                            $moneda,
                                                            'font-bold text-lg text-primary-600 dark:text-primary-400'
                                                        );
                                                    })
                                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: PRODUCTOS (igual que en PedidoResource) ==========
                        Tabs\Tab::make('Productos')
                            ->icon('heroicon-o-shopping-bag')
                            ->badge(function ($record) {
                                if (!$record) return 0;
                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle de Productos')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Artículos incluidos en el pedido')
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
                                                            ->step(1)
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
                                                            ->step(1)
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
                                                            ->step(1)
                                                            ->default(0)
                                                            ->suffix('%')
                                                            ->prefixIcon('heroicon-o-percent-badge')
                                                            ->live()
                                                            ->formatStateUsing(fn($state) => $state !== null ? (int) $state : 0)
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($get('cantidad') ?? 1);
                                                                $precio = floatval($get('precio_unitario') ?? 0);
                                                                $subtotalBase = $cantidad * $precio;
                                                                $descuento = $subtotalBase * ($state / 100);
                                                                $subtotal = $subtotalBase - $descuento;

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
                                                                $subtotalBase = $cantidad * $precio;
                                                                $subtotal = $subtotalBase - floatval($state);
                                                                $descuentoPorcentaje = $subtotalBase > 0 ? ($state / $subtotalBase) * 100 : 0;

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
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::calcularImpuestoYTotal($set, $get);
                                                            })
                                                            ->columnSpan(4),

                                                        Placeholder::make('impuesto_linea')
                                                            ->label('Impuesto')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                return self::formatearMonto($get('impuesto') ?? 0, $moneda);
                                                            })
                                                            ->columnSpan(4),

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
                                                $descuento = floatval($data['descuento'] ?? 0);
                                                $subtotal = ($precioUnitario * $cantidad) - $descuento;

                                                $aplicarIVA = isset($data['aplicar_iva']) ? (bool)$data['aplicar_iva'] : false;
                                                if ($aplicarIVA) {
                                                    $impuesto = $subtotal * (13 / 100);
                                                    $total = $subtotal + $impuesto;
                                                } else {
                                                    $impuesto = 0;
                                                    $total = $subtotal;
                                                }

                                                $data['codigo_articulo'] = $articulo ? ($articulo->codigo_alterno ?? $articulo->codigo ?? 'SIN_CODIGO') : '';
                                                $data['descripcion_articulo'] = $articulo ? ($articulo->descripcion ?? $articulo->nombre_comercial ?? 'SIN_DESCRIPCION') : '';
                                                $data['unidad_medida'] = $articulo ? ($articulo->unidadMedida?->abreviatura ?? 'UND') : 'UND';
                                                $data['precio_original'] = floatval($data['precio_original'] ?? $data['precio_unitario'] ?? 0);
                                                $data['subtotal'] = $subtotal;
                                                $data['impuesto'] = $impuesto;
                                                $data['total'] = $total;
                                                $data['tipo_impuesto'] = $data['tipo_impuesto'] ?? 'IVA';
                                                $data['tasa_impuesto'] = floatval($data['tasa_impuesto'] ?? 13);
                                                $data['aplicar_iva'] = $aplicarIVA;
                                                $data['descuento'] = $descuento;
                                                $data['descuento_porcentaje'] = floatval($data['descuento_porcentaje'] ?? 0);
                                                $data['precio_unitario'] = $precioUnitario;
                                                $data['cantidad'] = $cantidad;

                                                return $data;
                                            })
                                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                                $data['subtotal'] = floatval($data['subtotal'] ?? 0);
                                                $data['impuesto'] = floatval($data['impuesto'] ?? 0);
                                                $data['total'] = floatval($data['total'] ?? 0);
                                                $data['precio_original'] = floatval($data['precio_original'] ?? 0);
                                                $data['aplicar_iva'] = isset($data['aplicar_iva']) ? (bool)$data['aplicar_iva'] : false;
                                                return $data;
                                            }),
                                    ]),
                            ]),

                        Tabs\Tab::make('Notas')
                            ->icon('heroicon-o-clipboard-document')
                            ->schema([
                                Section::make('Observaciones e Instrucciones')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->schema([
                                        Textarea::make('observaciones')
                                            ->label('Observaciones Generales')
                                            ->rows(4)
                                            ->placeholder('Notas adicionales sobre el pedido...')
                                            ->helperText('Información relevante para el cliente')
                                            ->columnSpanFull(),

                                        Textarea::make('instrucciones_especiales')
                                            ->label('Instrucciones Especiales')
                                            ->rows(4)
                                            ->placeholder('Instrucciones especiales para el pedido...')
                                            ->helperText('Instrucciones para el equipo de despacho')
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

                                                Placeholder::make('aprobado_por')
                                                    ->label('Aprobado por')
                                                    ->content(fn($record) => $record?->aprobador?->name ?? 'N/A')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('codigo')
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

                TextColumn::make('fecha_pedido')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('fecha_entrega_estimada')
                    ->label('Entrega Estimada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn($state) => $state && $state < now() ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'reservado' => '📌 Reservado',
                        'pendiente' => '⏳ Pendiente',
                        'parcial' => '📦 Parcial',
                        'despachado' => '🚚 Despachado',
                        'entregado' => '✅ Entregado',
                        'cancelado' => '❌ Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'reservado',
                        'info' => 'pendiente',
                        'primary' => 'parcial',
                        'success' => 'despachado',
                        'success' => 'entregado',
                        'danger' => 'cancelado',
                    ])
                    ->toggleable(),

                BadgeColumn::make('prioridad')
                    ->label('Prioridad')
                    ->formatStateUsing(fn($state) => match($state) {
                        'baja' => '🟢 Baja',
                        'normal' => '🟡 Normal',
                        'alta' => '🟠 Alta',
                        'urgente' => '🔴 Urgente',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'baja',
                        'info' => 'normal',
                        'warning' => 'alta',
                        'danger' => 'urgente',
                    ])
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn($record) => $record->moneda ?? 'BOB')
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
                        'reservado' => 'Reservado',
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Parcial',
                        'despachado' => 'Despachado',
                        'entregado' => 'Entregado',
                        'cancelado' => 'Cancelado',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'normal' => 'Normal',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ])
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Pedido')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nuevo Pedido')
                    ->modalWidth('7xl')
                    ->using(function (array $data, $livewire) {
                        $data['cliente_id'] = $livewire->getOwnerRecord()->id;
                        $data['codigo'] = Pedido::generarCodigo();
                        $data['creado_por'] = Auth::id();
                        $data['empresa_id'] = Auth::user()?->empresa_id ?? 1;

                        $pedido = Pedido::create($data);

                        Notification::make()
                            ->title('Pedido creado exitosamente')
                            ->body('El pedido ' . $pedido->codigo . ' ha sido creado.')
                            ->success()
                            ->send();

                        return $pedido;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('cambiar_estado')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Select::make('estado')
                                ->label('Nuevo Estado')
                                ->options([
                                    'reservado' => '📌 Reservado',
                                    'pendiente' => '⏳ Pendiente',
                                    'parcial' => '📦 Parcial',
                                    'despachado' => '🚚 Despachado',
                                    'entregado' => '✅ Entregado',
                                    'cancelado' => '❌ Cancelado',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['estado' => $data['estado']]);
                            Notification::make()
                                ->title('Estado actualizado')
                                ->body('El pedido ahora está en estado: ' . ucfirst($data['estado']))
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
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar pedido...')
            ->emptyStateHeading('No hay pedidos para este cliente')
            ->emptyStateDescription('Crea un nuevo pedido para este cliente.')
            ->emptyStateIcon('heroicon-o-shopping-cart')
            ->poll('60s');
    }
}