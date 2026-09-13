<?php

namespace App\Filament\Resources\Ventas\ClienteResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Actions\CreateAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\Action;
use App\Models\Ventas\Factura;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;

class FacturasRelationManager extends RelationManager
{
    protected static string $relationship = 'facturas';

    protected static ?string $title = 'Facturas';

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas';

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

        return $simbolo.' '.number_format($monto ?? 0, 2);
    }

    private function facturaBloqueada($factura): bool
    {
        $estado = $factura?->estado ?? null;

        return in_array($estado, ['pagada', 'pagado', 'anulada'], true);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Factura')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('numero')
                                    ->label('Número')
                                    ->required()
                                    ->disabled()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('FAC-000001')
                                    ->helperText('Número único de la factura')
                                    ->default(fn () => Factura::generarNumero())
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_emision')
                                    ->label('Fecha Emisión')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->default(now())
                                    ->native()
                                    ->helperText('Fecha de emisión')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->columnSpan(1),

                                Select::make('estado')
                                    ->label('Estado')
                                    ->disabled()
                                    ->dehydrated()
                                    ->options([
                                        'borrador' => 'Borrador',
                                        'emitida' => 'Emitida',
                                        'pagada' => '✅ Pagada',
                                        'parcial' => 'Parcial',
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

                        Grid::make(2)
                            ->schema([
                                Select::make('moneda')
                                    ->label('Moneda')
                                    ->options([
                                        'BOB' => 'Bolivianos',
                                        'USD' => 'Dólares',
                                        'EUR' => 'Euros',
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
                                    ->native()
                                    ->helperText('Fecha de vencimiento')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('total')
                                    ->label('Total')
                                    ->content(function ($get, $record) {
                                        $moneda = $get('moneda') ?? 'BOB';

                                        return self::formatearMonto($record?->total ?? 0, $moneda);
                                    }),

                                Placeholder::make('monto_pagado')
                                    ->label('Pagado')
                                    ->content(function ($get, $record) {
                                        $moneda = $get('moneda') ?? 'BOB';

                                        return self::formatearMonto($record?->monto_pagado ?? 0, $moneda);
                                    }),

                                Placeholder::make('saldo')
                                    ->label('Saldo')
                                    ->content(function ($get, $record) {
                                        $moneda = $get('moneda') ?? 'BOB';
                                        $saldo = ($record?->total ?? 0) - ($record?->monto_pagado ?? 0);
                                        $color = $saldo <= 0 ? 'success' : 'danger';

                                        return new HtmlString(
                                            '<span class="font-bold text-'.$color.'-600">'.
                                                self::formatearMonto($saldo, $moneda).
                                                '</span>'
                                        );
                                    }),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero')
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
                    ->toggleable(isToggledHiddenByDefault: true),
                // ->color(fn($state, $record) => {
                //     if ($record?->estado === 'pagada') return 'success';
                //     if ($state && $state < now()) return 'danger';
                //     return 'warning';
                // }),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'borrador' => 'Borrador',
                        'emitida' => 'Emitida',
                        'pagada' => '✅ Pagada',
                        'parcial' => 'Parcial',
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
                    ->formatStateUsing(fn ($state, $record) => self::formatearMonto($state, $record->moneda ?? 'BOB'))
                    ->sortable()
                    ->toggleable()
                    ->weight('bold'),

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
                        'emitida' => 'Emitida',
                        'pagada' => 'Pagada',
                        'parcial' => 'Parcial',
                        'vencida' => 'Vencida',
                        'anulada' => 'Anulada',
                    ])
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('estado')
                    ->label('Pagada')
                    ->nullable()
                    ->trueLabel('Facturas pagadas')
                    ->falseLabel('Facturas pendientes')
                    ->queries(
                        true: fn ($query) => $query->where('estado', 'pagada'),
                        false: fn ($query) => $query->whereIn('estado', ['emitida', 'parcial', 'vencida']),
                    ),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva Factura')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nueva Factura')
                    ->modalWidth('5xl')
                    ->using(function (array $data, $livewire) {
                        $data['cliente_id'] = $livewire->getOwnerRecord()->id;
                        $data['numero'] = Factura::generarNumero();
                        $data['creado_por'] = Auth::id();
                        $data['empresa_id'] = $livewire->getOwnerRecord()->empresa_id;
                        $data['monto_pagado'] = 0;
                        $data['saldo'] = $data['total'] ?? 0;

                        $factura = Factura::create($data);

                        Notification::make()
                            ->title('Factura creada exitosamente')
                            ->body('La factura '.$factura->numero.' ha sido creada.')
                            ->success()
                            ->send();

                        return $factura;
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make('ver_factura')
                        ->label('Ver factura')
                        ->icon('heroicon-o-eye')
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->modalHeading(fn (Factura $record): string => 'Factura '.$record->numero)
                        ->modalDescription('Cabecera comercial, ítems facturados y cobros registrados.')
                        ->schema([
                            Section::make('Documento y resumen de cobro')
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    TextEntry::make('numero')->label('N.º factura')->copyable()->weight('bold'),
                                    TextEntry::make('serie')->label('Serie')->placeholder('Sin serie'),
                                    TextEntry::make('fecha_emision')->label('Emitida')->dateTime('d/m/Y H:i'),
                                    TextEntry::make('fecha_vencimiento')->label('Vencimiento')->date('d/m/Y')->placeholder('Sin vencimiento'),
                                    TextEntry::make('condicion_pago')->label('Condición')->badge(),
                                    TextEntry::make('estado')->label('Estado')->badge()->formatStateUsing(fn ($state) => match ($state) {
                                        'borrador' => 'Borrador', 'emitida' => 'Emitida', 'pagada' => 'Pagada', 'parcial' => 'Parcial', 'vencida' => 'Vencida', 'anulada' => 'Anulada', default => $state,
                                    })->color(fn ($state) => match ($state) {
                                        'pagada' => 'success', 'parcial' => 'warning', 'vencida', 'anulada' => 'danger', 'emitida' => 'info', default => 'gray',
                                    }),
                                    TextEntry::make('total')->label('Total facturado')->getStateUsing(fn (Factura $record) => self::formatearMonto($record->total, $record->moneda))->weight('bold')->color('primary'),
                                    TextEntry::make('monto_pagado')->label('Cobrado')->getStateUsing(fn (Factura $record) => self::formatearMonto($record->monto_pagado, $record->moneda))->color('success'),
                                    TextEntry::make('saldo')->label('Saldo pendiente')->getStateUsing(fn (Factura $record) => self::formatearMonto(max(0, (float) $record->saldo), $record->moneda))->weight('bold')->color(fn (Factura $record) => (float) $record->saldo <= 0 ? 'success' : 'danger'),
                                    TextEntry::make('moneda')->label('Moneda')->badge(),
                                    TextEntry::make('tasa_cambio')->label('Tipo de cambio')->numeric(decimalPlaces: 2),
                                    TextEntry::make('numero_pedido')->label('Pedido vinculado')->placeholder('Venta directa'),
                                ])->columns(4),
                            Section::make('Ítems facturados')
                                ->icon('heroicon-o-shopping-cart')
                                ->description(fn (Factura $record): string => $record->detalles()->count().' línea(s) de venta.')
                                ->schema([
                                    RepeatableEntry::make('detalles')
                                        ->label('')
                                        ->contained(false)
                                        ->extraAttributes(['class' => 'sia-factura-items-striped'])
                                        ->schema([
                                            TextEntry::make('codigo_articulo')->label('Código')->weight('bold')->copyable(),
                                            TextEntry::make('descripcion_articulo')->label('Artículo')->columnSpan(3)->weight('medium'),
                                            TextEntry::make('cantidad')->label('Cantidad')->numeric(decimalPlaces: 2)->suffix(fn ($record) => ' '.($record->unidad_medida ?: 'UND')),
                                            TextEntry::make('total')->label('Total línea')->getStateUsing(fn ($record) => self::formatearMonto($record->total, $record->factura?->moneda ?? 'BOB'))->weight('bold')->color('primary'),
                                            TextEntry::make('precio_unitario')->label('Precio unitario')->getStateUsing(fn ($record) => self::formatearMonto($record->precio_unitario, $record->factura?->moneda ?? 'BOB'))->columnSpan(2),
                                            TextEntry::make('descuento')->label('Descuento')->getStateUsing(fn ($record) => self::formatearMonto($record->descuento, $record->factura?->moneda ?? 'BOB'))->color('warning'),
                                            TextEntry::make('impuesto')->label('Impuesto')->getStateUsing(fn ($record) => self::formatearMonto($record->impuesto, $record->factura?->moneda ?? 'BOB')),
                                            TextEntry::make('series')->label('Series / lote')->getStateUsing(fn ($record) => filled($record->series) ? collect($record->series)->flatten()->implode(', ') : 'Sin serie')->columnSpan(2),
                                        ])->columns(6),
                                ]),
                            Section::make('Cobros registrados')
                                ->icon('heroicon-o-credit-card')
                                ->schema([
                                    RepeatableEntry::make('pagos')
                                        ->label('')
                                        ->contained(false)
                                        ->schema([
                                            TextEntry::make('numero')->label('Recibo')->copyable()->weight('bold'),
                                            TextEntry::make('fecha_pago')->label('Fecha')->date('d/m/Y'),
                                            TextEntry::make('tipo_pago')->label('Método')->badge(),
                                            TextEntry::make('monto')->label('Importe')->getStateUsing(fn ($record) => self::formatearMonto($record->monto, $record->moneda))->weight('bold')->color('success'),
                                            TextEntry::make('estado')->label('Estado')->badge()->color(fn ($state) => $state === 'confirmado' ? 'success' : ($state === 'pendiente' ? 'warning' : 'danger')),
                                            TextEntry::make('referencia')->label('Referencia')->placeholder('Sin referencia')->columnSpan(2),
                                            TextEntry::make('banco')->label('Banco')->placeholder('No aplica'),
                                        ])->columns(7),
                                ])
                                ->visible(fn (Factura $record): bool => $record->pagos()->exists()),
                            Section::make('Contexto y observaciones')
                                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                                ->schema([
                                    TextEntry::make('vendedor.name')->label('Vendedor')->placeholder('No asignado'),
                                    TextEntry::make('cobrador.name')->label('Cobrador')->placeholder('No asignado'),
                                    TextEntry::make('empresa.nombre_comercial')->label('Empresa')->placeholder('No asignada'),
                                    TextEntry::make('sucursal.nombre')->label('Sucursal')->placeholder('No asignada'),
                                    TextEntry::make('observaciones')->label('Observaciones')->placeholder('Sin observaciones registradas.')->columnSpanFull(),
                                ])->columns(4),
                        ]),

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
                                    'qr' => 'QR',
                                    'transferencia' => 'Transferencia',
                                    'cheque' => 'Cheque',
                                    'tarjeta' => 'Tarjeta',
                                    'deposito' => 'Depósito',
                                    'nota_credito' => 'Nota de Crédito',
                                    'otros' => 'Otros',
                                ])
                                ->required()
                                ->searchable(),

                            TextInput::make('referencia')
                                ->label('Referencia')
                                ->maxLength(100)
                                ->visible(fn ($get): bool => $get('tipo_pago') !== 'efectivo'),

                            TextInput::make('banco')
                                ->label('Banco')
                                ->maxLength(100)
                                ->visible(fn ($get): bool => in_array($get('tipo_pago'), ['qr', 'transferencia', 'cheque', 'tarjeta', 'deposito'], true)),

                            TextInput::make('numero_cheque')
                                ->label('Número de cheque')
                                ->maxLength(50)
                                ->required(fn ($get): bool => $get('tipo_pago') === 'cheque')
                                ->visible(fn ($get): bool => $get('tipo_pago') === 'cheque'),
                        ])
                        ->action(function (array $data, $record) {
                            if ($this->facturaBloqueada($record)) {
                                throw ValidationException::withMessages([
                                    'estado' => 'No se puede registrar un pago porque la factura ya está pagada o anulada.',
                                ]);
                            }

                            $record->registrarPago($data);
                            Notification::make()
                                ->title('Pago registrado exitosamente')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => ! $this->facturaBloqueada($record)),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make()
            //             ->visible(fn($records) => $records->every(fn($record) => $record->estado === 'borrador')),
            //     ]),
            // ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar factura...')
            ->emptyStateHeading('No hay facturas para este cliente')
            ->emptyStateDescription('Crea una nueva factura para este cliente.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }
}
