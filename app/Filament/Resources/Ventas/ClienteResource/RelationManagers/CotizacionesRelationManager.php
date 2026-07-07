<?php

namespace App\Filament\Resources\Ventas\ClienteResource\RelationManagers;

use App\Models\Ventas\Cotizacion;
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
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class CotizacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'cotizaciones';

    protected static ?string $title = 'Cotizaciones';

    protected static ?string $modelLabel = 'Cotización';

    protected static ?string $pluralModelLabel = 'Cotizaciones';

    public function form(Form $form): Form
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
                                                    ->disabled()
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
                                                    ->options([
                                                        'borrador' => '📝 Borrador',
                                                        'enviada' => '📤 Enviada',
                                                        'aprobada' => '✅ Aprobada',
                                                        'rechazada' => '❌ Rechazada',
                                                        'convertida' => '🔄 Convertida',
                                                        'expirada' => '⏰ Expirada',
                                                    ])
                                                    ->default('borrador')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual de la cotización')
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
                                                    ->options(
                                                        fn() => User::where('activo', true)
                                                            ->pluck('name', 'id')
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(auth()->id())
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
                    ->width('120px'),

                TextColumn::make('fecha_emision')
                    ->label('Fecha Emisión')
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
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Cotización')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nueva Cotización')
                    ->modalWidth('7xl')
                    ->using(function (array $data, $livewire) {
                        $data['cliente_id'] = $livewire->getOwnerRecord()->id;
                        $data['codigo'] = Cotizacion::generarCodigo();
                        $data['creado_por'] = auth()->id();
                        $data['empresa_id'] = auth()->user()?->empresa_id ?? 1;

                        // Crear la cotización
                        $cotizacion = Cotizacion::create($data);

                        Notification::make()
                            ->title('Cotización creada exitosamente')
                            ->body('La cotización ' . $cotizacion->codigo . ' ha sido creada.')
                            ->success()
                            ->send();

                        return $cotizacion;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    // Tables\Actions\EditAction::make()
                    //     ->slideOver()
                    //     ->modalWidth('7xl')
                    //     ->after(function ($record) {
                    //         Notification::make()
                    //             ->title('Cotización actualizada')
                    //             ->body('La cotización ' . $record->codigo . ' ha sido actualizada.')
                    //             ->success()
                    //             ->send();
                    //     }),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

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

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            Notification::make()
                                ->title('Cotización eliminada')
                                ->body('La cotización ' . $record->codigo . ' ha sido eliminada.')
                                ->warning()
                                ->send();
                        }),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar cotización...')
            ->emptyStateHeading('No hay cotizaciones para este cliente')
            ->emptyStateDescription('Crea una nueva cotización para este cliente.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }

    /**
     * Calcular totales de los detalles
     */
    private static function calcularTotales($get, $record = null): array
    {
        $subtotal = 0;
        $descuento = 0;
        $impuesto = 0;
        $total = 0;

        // Si tenemos un registro, obtener totales desde la BD
        if ($record && $record->exists) {
            if ($record->subtotal > 0 || $record->total > 0) {
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

        // Si no hay registro, usar el estado del formulario
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
}
