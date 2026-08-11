<?php

namespace App\Filament\Resources\Compras\OrdenCompraResource\RelationManagers;

use App\Models\Compras\FacturaCompra;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class FacturasCompraRelationManager extends RelationManager
{
    protected static string $relationship = 'facturas';

    protected static ?string $title = '🧾 Facturas de Compra';

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas de Compra';

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
        return self::getSimboloMoneda($moneda) . ' ' . number_format($monto ?? 0, 2);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos de la Factura')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->disabled()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->default(fn() => FacturaCompra::generarCodigo())
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->columnSpan(1),

                                TextInput::make('numero_factura')
                                    ->label('N° Factura Proveedor')
                                    ->maxLength(50)
                                    ->placeholder('Número de factura del proveedor')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_emision')
                                    ->label('Fecha Emisión')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->columnSpan(1),

                                Select::make('estado')
                                    ->label('Estado')
                                    ->disabled()
                                    ->dehydrated()
                                    ->options([
                                        'borrador' => '📝 Borrador',
                                        'registrada' => '📤 Registrada',
                                        'pagada' => '✅ Pagada',
                                        'parcial' => '💰 Parcial',
                                        'anulada' => '❌ Anulada',
                                    ])
                                    ->default('borrador')
                                    ->required()
                                    ->searchable()
                                    ->prefixIcon('heroicon-o-tag')
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
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_vencimiento')
                                    ->label('Fecha Vencimiento')
                                    ->displayFormat('d/m/Y')
                                    ->default(now()->addDays(30))
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->columnSpan(1),

                                TextInput::make('condicion_pago')
                                    ->label('Condición de Pago')
                                    ->maxLength(100)
                                    ->placeholder('Crédito 30 días')
                                    ->prefixIcon('heroicon-o-credit-card')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(1)
                                    ->default(0)
                                    ->prefix(fn($get) => self::getSimboloMoneda($get('moneda') ?? 'BOB'))
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $subtotal = floatval($state);
                                        $impuesto = $subtotal * 0.13;
                                        $total = $subtotal + $impuesto;
                                        $set('impuesto', $impuesto);
                                        $set('total', $total);
                                        $set('saldo', $total);
                                    })
                                    ->columnSpan(1),

                                TextInput::make('impuesto')
                                    ->label('Impuesto (13%)')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(1)
                                    ->default(0)
                                    ->prefix(fn($get) => self::getSimboloMoneda($get('moneda') ?? 'BOB'))
                                    ->disabled()
                                    ->columnSpan(1),

                                TextInput::make('total')
                                    ->label('Total')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(1)
                                    ->default(0)
                                    ->prefix(fn($get) => self::getSimboloMoneda($get('moneda') ?? 'BOB'))
                                    ->disabled()
                                    ->columnSpan(1),
                            ]),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->placeholder('Observaciones...')
                            ->columnSpanFull(),
                    ]),
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
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('numero_factura')
                    ->label('N° Factura')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),

                DatePicker::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'borrador' => '📝 Borrador',
                        'registrada' => '📤 Registrada',
                        'pagada' => '✅ Pagada',
                        'parcial' => '💰 Parcial',
                        'anulada' => '❌ Anulada',
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
                    ->formatStateUsing(fn($state, $record) => self::formatearMonto($state, $record->moneda ?? 'BOB'))
                    ->sortable()
                    ->toggleable()
                    ->weight('bold'),

                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->formatStateUsing(fn($state, $record) => self::formatearMonto($state, $record->moneda ?? 'BOB'))
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nueva Factura')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Crear Factura de Compra')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['orden_compra_id'] = $livewire->getOwnerRecord()->id;
                        $data['proveedor_id'] = $livewire->getOwnerRecord()->proveedor_id;
                        $data['creado_por'] = Auth::id();
                        $data['empresa_id'] = Auth::user()?->empresa_id ?? 1;
                        $data['monto_pagado'] = 0;
                        $data['saldo'] = $data['total'] ?? 0;
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Factura creada exitosamente')
                            ->body('La factura ' . $record->codigo . ' ha sido creada.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),
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
            ->poll('60s');
    }
}