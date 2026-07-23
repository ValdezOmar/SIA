<?php

namespace App\Filament\Resources\Ventas\ClienteResource\RelationManagers;

use App\Models\Ventas\Factura;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
        return $simbolo . ' ' . number_format($monto ?? 0, 2);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                    ->default(fn() => Factura::generarNumero())
                                    ->prefixIcon('heroicon-o-hashtag')
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

                        Grid::make(2)
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
                                            '<span class="font-bold text-' . $color . '-600">' .
                                                self::formatearMonto($saldo, $moneda) .
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
                    // ->color(fn($state, $record) => {
                    //     if ($record?->estado === 'pagada') return 'success';
                    //     if ($state && $state < now()) return 'danger';
                    //     return 'warning';
                    // }),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
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
                    ->formatStateUsing(fn($state, $record) => self::formatearMonto($state, $record->moneda ?? 'BOB'))
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
                        true: fn($query) => $query->where('estado', 'pagada'),
                        false: fn($query) => $query->whereIn('estado', ['emitida', 'parcial', 'vencida']),
                    ),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Factura')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nueva Factura')
                    ->modalWidth('5xl')
                    ->using(function (array $data, $livewire) {
                        $data['cliente_id'] = $livewire->getOwnerRecord()->id;
                        $data['numero'] = Factura::generarNumero();
                        $data['creado_por'] = Auth::id();
                        $data['empresa_id'] = Auth::user()?->empresa_id ?? 1;
                        $data['monto_pagado'] = 0;
                        $data['saldo'] = $data['total'] ?? 0;

                        $factura = Factura::create($data);

                        Notification::make()
                            ->title('Factura creada exitosamente')
                            ->body('La factura ' . $factura->numero . ' ha sido creada.')
                            ->success()
                            ->send();

                        return $factura;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

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
                                ->prefix(fn($get, $record) => self::getSimboloMoneda($record->moneda ?? 'BOB')),

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
                                ->searchable(),
                        ])
                        ->action(function (array $data, $record) {
                            $record->registrarPago($data);
                            Notification::make()
                                ->title('Pago registrado exitosamente')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => !in_array($record->estado, ['pagada', 'anulada'])),
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