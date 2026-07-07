<?php

namespace App\Filament\Resources\Ventas;

use App\Filament\Resources\Ventas\CotizacionResource\Pages;
use App\Models\Ventas\Cotizacion;
use App\Models\Ventas\Cliente;
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

class CotizacionResource extends Resource
{
    protected static ?string $model = Cotizacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?string $navigationLabel = 'Cotizaciones';

    protected static ?string $modelLabel = 'Cotización';

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    protected static ?int $navigationSort = 2;

    // ========== MÉTODOS DE CÁLCULO ==========
    /**
     * Recalcular todos los valores de una línea
     */
    private static function recalcularLinea(callable $set, callable $get): void
    {
        $cantidad = floatval($get('cantidad') ?? 1);
        $precioUnitario = floatval($get('precio_unitario') ?? 0);
        $precioOriginal = floatval($get('precio_original') ?? $precioUnitario);
        $descuento = floatval($get('descuento') ?? 0);

        // 1. Calcular subtotal (cantidad * precio - descuentos)
        $subtotal = ($cantidad * $precioUnitario) - $descuento;

        // 2. Calcular impuesto y total
        $aplicarIVA = $get('aplicar_iva') ?? false;
        $tasaIVA = 13;

        if ($aplicarIVA) {
            $impuesto = $subtotal * ($tasaIVA / 100);
            $total = $subtotal + $impuesto;
        } else {
            $impuesto = 0;
            $total = $subtotal;
        }

        // ✅ Actualizar TODOS los campos (incluidos los ocultos)
        $set('precio_original', $precioOriginal);
        $set('subtotal', $subtotal);
        $set('impuesto', $impuesto);
        $set('total', $total);
    }   

    /**
     * Calcular impuesto y total de una línea
     */
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

    /**
     * Calcular totales de los detalles (desde estado o desde BD)
     */
    private static function calcularTotales($get, $record = null): array
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        // ✅ SIEMPRE intentar obtener del registro primero (cuando existe)
        if ($record && $record->exists) {
            // Intentar obtener de los totales guardados en el registro
            if ($record->subtotal > 0 || $record->total > 0 || $record->impuesto > 0) {
                return [
                    'subtotal' => floatval($record->subtotal ?? 0),
                    'descuento' => floatval($record->descuento ?? 0),
                    'impuesto' => floatval($record->impuesto ?? 0),
                    'total' => floatval($record->total ?? 0),
                ];
            }

            // Si los totales del registro están en 0, calcular desde los detalles
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

        // ✅ Si no hay registro o está vacío, usar el estado del formulario
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

    // ========== MÉTODOS DE MONEDA ==========

    /**
     * Obtener el símbolo de una moneda
     */
    private static function getSimboloMoneda($moneda): string
    {
        return match ($moneda) {
            'BOB' => 'Bs',
            'USD' => '$',
            'EUR' => '€',
            'ARS' => '$',
            'CLP' => '$',
            'PEN' => 'S/',
            'MXN' => '$',
            'COP' => '$',
            'UYU' => '$',
            'PYG' => '₲',
            default => $moneda,
        };
    }

    /**
     * Formatear un monto con la moneda correcta
     */
    private static function formatearMonto($monto, $moneda): string
    {
        $simbolo = self::getSimboloMoneda($moneda);
        return $simbolo . ' ' . number_format($monto ?? 0, 2);
    }

    /**
     * Formatear un monto con HTML para Placeholder
     */
    private static function formatearMontoHtml($monto, $moneda, $clase = ''): HtmlString
    {
        $simbolo = self::getSimboloMoneda($moneda);
        return new HtmlString(
            '<span class="' . $clase . '">' .
                $simbolo . ' ' . number_format($monto ?? 0, 2) .
                '</span>'
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Cotización')
                    ->tabs([

                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Cotización')
                                    ->icon('heroicon-o-document-text')
                                    ->description('Información principal de la cotización')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Ej: COT-000001')
                                                    ->helperText('Código único de la cotización')
                                                    ->default(fn() => Cotizacion::generarCodigo())
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_emision')
                                                    ->label('Fecha de Emisión')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de emisión de la cotización')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_validez')
                                                    ->label('Fecha de Validez')
                                                    ->default(now()->addDays(7))
                                                    ->displayFormat('d/m/Y')
                                                    ->native(false)
                                                    ->helperText(function ($get) {
                                                        $fecha = $get('fecha_validez');
                                                        if (!$fecha) {
                                                            return 'Seleccione una fecha de validez';
                                                        }
                                                        $dias = intval(now()->diffInDays($fecha, false));
                                                        if ($dias < 0) {
                                                            return '⚠️ Fecha de validez expirada';
                                                        } elseif ($dias == 0) {
                                                            return '⏰ La vigencia vence hoy';
                                                        } elseif ($dias <= 3) {
                                                            return "⚠️ Próximo a vencer ({$dias} días restantes)";
                                                        } else {
                                                            return "✅ Vigencia: {$dias} días";
                                                        }
                                                    })
                                                    ->columnSpan(1)
                                                    ->live(),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => '📝 Borrador',
                                                        'enviada' => '📤 Enviada',
                                                        'aprobada' => '✅ Aprobada',
                                                        'rechazada' => '❌ Rechazada',
                                                        'convertida' => '🔄 Convertida',
                                                        'expirada' => '⏰ Expirada',
                                                    ])
                                                    ->default('enviada')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual de la cotización')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Select::make('cliente_id')
                                                    ->label('Cliente')
                                                    ->options(function () {
                                                        return Cliente::where('activo', true)
                                                            ->orderBy('nombre')
                                                            ->pluck('nombre', 'id')
                                                            ->toArray();
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un cliente')
                                                    ->helperText('Cliente al que va dirigida la cotización')
                                                    ->columnSpan(2)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        if ($state) {
                                                            $cliente = Cliente::find($state);
                                                            if ($cliente) {
                                                                $set('condicion_pago', $cliente->condicion_pago);
                                                            }
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
                                                                            ->maxLength(50)
                                                                            ->unique(ignoreRecord: true)
                                                                            ->default(fn() => Cliente::generarCodigo())
                                                                            ->helperText('Código único del cliente')
                                                                            ->columnSpan(1),

                                                                        TextInput::make('nombre')
                                                                            ->label('Nombre / Razón Social')
                                                                            ->required()
                                                                            ->maxLength(255)
                                                                            ->placeholder('Ej: Juan Pérez')
                                                                            ->helperText('Nombre completo o razón social')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('ci/nit')
                                                                            ->label('CI / NIT')
                                                                            ->maxLength(50)
                                                                            ->placeholder('Ej: 123456789')
                                                                            ->helperText('Cédula de identidad o NIT')
                                                                            ->columnSpan(1),

                                                                        Select::make('tipo_cliente')
                                                                            ->label('Tipo de Cliente')
                                                                            ->options([
                                                                                'persona_natural' => '👤 Persona Natural',
                                                                                'empresa' => '🏢 Empresa',
                                                                                'gobierno' => '🏛️ Gobierno',
                                                                                'extranjero' => '🌍 Extranjero',
                                                                            ])
                                                                            ->default('persona_natural')
                                                                            ->helperText('Clasificación del cliente')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('telefono')
                                                                            ->label('Teléfono')
                                                                            ->maxLength(50)
                                                                            ->placeholder('Ej: (591) 2-1234567')
                                                                            ->columnSpan(1),

                                                                        TextInput::make('celular')
                                                                            ->label('Celular')
                                                                            ->maxLength(50)
                                                                            ->placeholder('Ej: (591) 7-1234567')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                TextInput::make('correo')
                                                                    ->label('Correo Electrónico')
                                                                    ->email()
                                                                    ->maxLength(255)
                                                                    ->placeholder('Ej: cliente@email.com')
                                                                    ->columnSpanFull(),
                                                                Textarea::make('direccion')
                                                                    ->label('Dirección')
                                                                    ->rows(2)
                                                                    ->placeholder('Ej: Av. Principal #123, Zona Central')
                                                                    ->columnSpanFull(),
                                                                Grid::make(2)
                                                                    ->schema([
                                                                        TextInput::make('ciudad')
                                                                            ->label('Ciudad')
                                                                            ->maxLength(255)
                                                                            ->placeholder('Ej: Santa Cruz')
                                                                            ->columnSpan(1),

                                                                        TextInput::make('zona')
                                                                            ->label('Zona')
                                                                            ->maxLength(100)
                                                                            ->placeholder('Ej: Equipetrol')
                                                                            ->columnSpan(1),
                                                                    ]),
                                                                TextInput::make('condicion_pago')
                                                                    ->label('Condición de Pago')
                                                                    ->maxLength(100)
                                                                    ->placeholder('Ej: Crédito 30 días')
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

                                                Select::make('vendedor_id')
                                                    ->label('Vendedor')
                                                    ->relationship('vendedor', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(Auth::id())
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->helperText('Vendedor responsable')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_entrega_estimada')
                                                    ->label('Fecha Entrega Estimada')
                                                    ->displayFormat('d/m/Y')
                                                    ->native(false)
                                                    ->helperText('Fecha estimada de entrega')
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
                                                    ->helperText('Moneda de la cotización')
                                                    ->live()
                                                    ->columnSpan(1),

                                                TextInput::make('tasa_cambio')
                                                    ->label('Tasa de Cambio')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->step(0.000001)
                                                    ->helperText('Tasa de cambio aplicada')
                                                    ->visible(fn($get) => $get('moneda') !== 'BOB')
                                                    ->columnSpan(1),

                                                TextInput::make('condicion_pago')
                                                    ->label('Condición de Pago')
                                                    ->maxLength(100)
                                                    ->placeholder('Ej: Crédito 30 días')
                                                    ->helperText('Condiciones de pago acordadas')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                // ========== SECCIÓN DE TOTALES ==========
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
                                    ->description('Artículos incluidos en la cotización')
                                    ->schema([
                                        Repeater::make('detalles')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                // ========== FILA 1: ARTÍCULO, CANTIDAD, PRECIO ==========
                                                Grid::make(12)
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
                                                            ->placeholder('Seleccione un artículo')
                                                            ->columnSpan(4)
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
                                                            ->label('Lista de Precios')
                                                            ->options(function ($get) {
                                                                $articuloId = $get('articulo_id');
                                                                if (!$articuloId) {
                                                                    return [];
                                                                }
                                                                $articulo = Articulo::find($articuloId);
                                                                if (!$articulo) {
                                                                    return [];
                                                                }
                                                                $precios = $articulo->getPreciosConListas();
                                                                if ($precios->isEmpty()) {
                                                                    return [];
                                                                }
                                                                return $precios->mapWithKeys(fn($item, $key) => [
                                                                    $key => $item['nombre'] . ' - ' . number_format($item['precio'], 2) . ' ' . $item['moneda']
                                                                ])->toArray();
                                                            })
                                                            ->visible(fn($get) => $get('articulo_id') !== null)
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione una lista')
                                                            ->helperText('Lista de precios')
                                                            ->columnSpan(2)
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
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $state = intval($state);
                                                                $set('cantidad', $state);
                                                                self::recalcularLinea($set, $get);
                                                            })
                                                            ->formatStateUsing(fn($state) => intval($state))
                                                            ->columnSpan(1),

                                                        TextInput::make('precio_unitario')
                                                            ->label('Precio Unit.')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->maxValue(999999.99)
                                                            ->step(0.01)
                                                            ->default(0)
                                                            ->prefix(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                return self::getSimboloMoneda($moneda);
                                                            })
                                                            ->helperText('Puede modificar el precio manualmente')
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

                                                // ========== FILA 2: DESCUENTOS E IVA ==========
                                                Grid::make(12)
                                                    ->schema([
                                                        TextInput::make('descuento_porcentaje')
                                                            ->label('Descuento %')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->maxValue(100)
                                                            ->step(0.01)
                                                            ->default(0)
                                                            ->suffix('%')
                                                            ->live()
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
                                                            ->columnSpan(2),

                                                        TextInput::make('descuento')
                                                            ->label('Descuento')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(0.01)
                                                            ->default(0)
                                                            ->prefix(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                return self::getSimboloMoneda($moneda);
                                                            })
                                                            ->live()
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
                                                            ->columnSpan(2),

                                                        Toggle::make('aplicar_iva')
                                                            ->label('IVA')
                                                            ->default(false)
                                                            ->helperText('Aplicar impuestos')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::calcularImpuestoYTotal($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('impuesto_linea')
                                                            ->label('Impuesto')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                return self::formatearMonto($get('impuesto') ?? 0, $moneda);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('total_con_iva')
                                                            ->label('Total con IVA')
                                                            ->content(function ($get) {
                                                                $moneda = $get('../../moneda') ?? 'BOB';
                                                                $total = floatval($get('total') ?? 0);
                                                                $aplicaIVA = $get('aplicar_iva') ?? false;
                                                                $label = $aplicaIVA ? 'Total + IVA' : 'Total sin IVA';
                                                                return new HtmlString(
                                                                    '<div>
                                <span class="text-xs text-gray-500">' . $label . '</span>
                                <br>
                                <span class="text-lg font-bold text-success-600 dark:text-success-400">' .
                                                                        self::formatearMonto($total, $moneda) .
                                                                        '</span>
                            </div>'
                                                                );
                                                            })
                                                            ->extraAttributes(['class' => 'flex items-center'])
                                                            ->columnSpan(2),
                                                    ]),

                                                // ========== OBSERVACIONES ==========
                                                TextInput::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->maxLength(255)
                                                    ->placeholder('Notas sobre este producto (opcional)')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(1)
                                            ->collapsible()
                                            ->cloneable()
                                            ->addActionLabel('Agregar Producto')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                                // ✅ Obtener el artículo para extraer sus datos
                                                $articulo = null;
                                                if (isset($data['articulo_id']) && $data['articulo_id']) {
                                                    $articulo = Articulo::find($data['articulo_id']);
                                                }

                                                // ✅ Calcular todos los valores
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

                                                // ✅ Asignar todos los valores
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
                                                // ✅ Al cargar datos existentes, solo asegurar valores
                                                $data['subtotal'] = floatval($data['subtotal'] ?? 0);
                                                $data['impuesto'] = floatval($data['impuesto'] ?? 0);
                                                $data['total'] = floatval($data['total'] ?? 0);
                                                $data['precio_original'] = floatval($data['precio_original'] ?? 0);
                                                $data['aplicar_iva'] = isset($data['aplicar_iva']) ? (bool)$data['aplicar_iva'] : false;
                                                return $data;
                                            }),
                                    ]),
                            ]),

                        // ========== TAB 3: INFORMACIÓN ADICIONAL ==========
                        Tabs\Tab::make('Información Adicional')
                            ->icon('heroicon-o-clipboard-document')
                            ->schema([
                                Section::make('Observaciones y Condiciones')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->schema([
                                        Textarea::make('observaciones')
                                            ->label('Observaciones Generales')
                                            ->rows(4)
                                            ->placeholder('Notas adicionales sobre la cotización...')
                                            ->helperText('Información relevante para el cliente')
                                            ->columnSpanFull(),

                                        Textarea::make('condiciones_especiales')
                                            ->label('Condiciones Especiales')
                                            ->rows(4)
                                            ->placeholder('Condiciones especiales de la cotización...')
                                            ->helperText('Términos y condiciones especiales')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 4: AUDITORÍA ==========
                        Tabs\Tab::make('Auditoría')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Información de Auditoría')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('creado_por')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? 'N/A')
                                                    ->columnSpan(1),

                                                Placeholder::make('created_at')
                                                    ->label('Fecha de creación')
                                                    ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i') ?? 'N/A')
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
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->toggleable()
                    ->width('120px'),

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

                TextColumn::make('fecha_validez')
                    ->label('Validez')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn($state) => $state && $state < now() ? 'danger' : 'success'),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'borrador' => '📝 Borrador',
                        'enviada' => '📤 Enviada',
                        'aprobada' => '✅ Aprobada',
                        'rechazada' => '❌ Rechazada',
                        'convertida' => '🔄 Convertida',
                        'expirada' => '⏰ Expirada',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'borrador',
                        'info' => 'enviada',
                        'success' => 'aprobada',
                        'danger' => 'rechazada',
                        'primary' => 'convertida',
                        'warning' => 'expirada',
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
                        'borrador' => 'Borrador',
                        'enviada' => 'Enviada',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        'convertida' => 'Convertida',
                        'expirada' => 'Expirada',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('vendedor_id')
                    ->label('Vendedor')
                    ->relationship('vendedor', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('fecha_validez')
                    ->label('Vigente')
                    ->nullable()
                    ->trueLabel('Cotizaciones vigentes')
                    ->falseLabel('Cotizaciones expiradas')
                    ->queries(
                        true: fn($query) => $query->where('fecha_validez', '>=', now()->toDateString())
                            ->whereIn('estado', ['enviada', 'aprobada']),
                        false: fn($query) => $query->where(function ($q) {
                            $q->where('fecha_validez', '<', now()->toDateString())
                                ->orWhereIn('estado', ['rechazada', 'expirada']);
                        }),
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
                    
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->codigo = Cliente::generarCodigo();
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            Notification::make()
                                ->title('Cliente duplicado exitosamente')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('convertir_pedido')
                        ->label('Convertir a Pedido')
                        ->icon('heroicon-o-arrow-path')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Convertir a Pedido')
                        ->modalSubheading('¿Deseas convertir esta cotización en un pedido de venta?')
                        ->action(function ($record) {
                            try {
                                $pedido = $record->convertirPedido();
                                Notification::make()
                                    ->title('Cotización convertida a pedido')
                                    ->body('El pedido ' . $pedido->codigo . ' ha sido creado exitosamente.')
                                    ->success()
                                    ->send();

                                return redirect()->route('filament.dashboard.resources.ventas.pedidos.edit', $pedido);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al convertir')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn($record) => $record->estado === 'aprobada'),

                    Tables\Actions\Action::make('enviar')
                        ->label('Enviar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->action(function ($record) {
                            $record->update(['estado' => 'enviada']);
                            Notification::make()
                                ->title('Cotización enviada')
                                ->body('La cotización ha sido enviada al cliente.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'borrador'),

                    Tables\Actions\Action::make('aprobar')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['estado' => 'aprobada']);
                            Notification::make()
                                ->title('Cotización aprobada')
                                ->body('La cotización ha sido aprobada.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'enviada'),

                    Tables\Actions\Action::make('rechazar')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->update(['estado' => 'rechazada']);
                            Notification::make()
                                ->title('Cotización rechazada')
                                ->body('La cotización ha sido rechazada.')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'enviada'),

                    //Tables\Actions\DeleteAction::make(),
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
            ->searchPlaceholder('Buscar cotización...')
            ->emptyStateHeading('No hay cotizaciones registradas')
            ->emptyStateDescription('Crea tu primera cotización para comenzar.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            //            
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCotizaciones::route('/'),
            'create' => Pages\CreateCotizacion::route('/create'),
            'edit' => Pages\EditCotizacion::route('/{record}/edit'),
        ];
    }
}
