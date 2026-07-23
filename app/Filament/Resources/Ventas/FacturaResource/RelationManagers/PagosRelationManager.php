<?php

namespace App\Filament\Resources\Ventas\FacturaResource\RelationManagers;

use App\Models\Ventas\Pago;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
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

class PagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Pagos';

    protected static ?string $modelLabel = 'Pago';

    protected static ?string $pluralModelLabel = 'Pagos';

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
                Section::make('Datos del Pago')
                    ->icon('heroicon-o-credit-card')
                    ->description('Registrar pago para la factura')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('numero')
                                    ->label('Número')
                                    ->required()
                                    ->disabled()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('PAG-000001')
                                    ->helperText('Número único del pago')
                                    ->default(fn() => Pago::generarNumero())
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_pago')
                                    ->label('Fecha Pago')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->helperText('Fecha del pago')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->columnSpan(1),

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
                                    ->helperText('Método de pago utilizado')
                                    ->prefixIcon('heroicon-o-credit-card')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('monto')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(0.01)
                                    ->placeholder('0.00')
                                    ->prefix(fn($get) => self::getSimboloMoneda($get('moneda') ?? 'BOB'))
                                    ->helperText('Monto del pago')
                                    ->live()
                                    ->columnSpan(1),

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
                                    ->helperText('Moneda del pago')
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
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('referencia')
                                    ->label('Referencia')
                                    ->maxLength(100)
                                    ->placeholder('Número de referencia')
                                    ->helperText('Referencia del pago')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->columnSpan(1),

                                TextInput::make('banco')
                                    ->label('Banco')
                                    ->maxLength(100)
                                    ->placeholder('Nombre del banco')
                                    ->helperText('Banco utilizado')
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->visible(fn($get) => in_array($get('tipo_pago'), ['transferencia', 'cheque', 'deposito']))
                                    ->columnSpan(1),

                                TextInput::make('numero_cheque')
                                    ->label('Número de Cheque')
                                    ->maxLength(50)
                                    ->placeholder('CHQ-001')
                                    ->helperText('Número del cheque')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->visible(fn($get) => $get('tipo_pago') === 'cheque')
                                    ->columnSpan(1),
                            ]),

                        DatePicker::make('fecha_cheque')
                            ->label('Fecha Cheque')
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->helperText('Fecha del cheque')
                            ->prefixIcon('heroicon-o-calendar')
                            ->visible(fn($get) => $get('tipo_pago') === 'cheque')
                            ->columnSpan(1),

                        Select::make('estado')
                            ->label('Estado')
                            ->disabled()
                            ->dehydrated()
                            ->options([
                                'pendiente' => '⏳ Pendiente',
                                'confirmado' => '✅ Confirmado',
                                'rechazado' => '❌ Rechazado',
                                'anulado' => '🚫 Anulado',
                            ])
                            ->default('confirmado')
                            ->required()
                            ->searchable()
                            ->helperText('Estado del pago')
                            ->prefixIcon('heroicon-o-tag')
                            ->columnSpan(1),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->placeholder('Observaciones del pago...')
                            ->helperText('Información adicional')
                            ->columnSpanFull(),
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
                    ->weight('bold'),

                DatePicker::make('fecha_pago')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('tipo_pago')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => match($state) {
                        'efectivo' => '💵 Efectivo',
                        'transferencia' => '🏦 Transferencia',
                        'cheque' => '📄 Cheque',
                        'tarjeta' => '💳 Tarjeta',
                        'deposito' => '🏛️ Depósito',
                        'nota_credito' => '📝 Nota Crédito',
                        'otros' => '📌 Otros',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'efectivo',
                        'info' => 'transferencia',
                        'warning' => 'cheque',
                        'primary' => 'tarjeta',
                        'gray' => 'deposito',
                        'gray' => 'nota_credito',
                    ])
                    ->toggleable(),

                TextColumn::make('monto')
                    ->label('Monto')
                    ->formatStateUsing(fn($state, $record) => self::formatearMonto($state, $record->moneda ?? 'BOB'))
                    ->sortable()
                    ->toggleable()
                    ->weight('bold')
                    ->color('success'),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'pendiente' => '⏳ Pendiente',
                        'confirmado' => '✅ Confirmado',
                        'rechazado' => '❌ Rechazado',
                        'anulado' => '🚫 Anulado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'confirmado',
                        'danger' => 'rechazado',
                        'gray' => 'anulado',
                    ])
                    ->toggleable(),

                TextColumn::make('referencia')
                    ->label('Referencia')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('tipo_pago')
                    ->label('Tipo')
                    ->options([
                        'efectivo' => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque' => 'Cheque',
                        'tarjeta' => 'Tarjeta',
                        'deposito' => 'Depósito',
                        'nota_credito' => 'Nota de Crédito',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmado' => 'Confirmado',
                        'rechazado' => 'Rechazado',
                        'anulado' => 'Anulado',
                    ])
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Pago')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Registrar Pago')
                    ->modalWidth('3xl')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['factura_id'] = $livewire->getOwnerRecord()->id;
                        $data['cliente_id'] = $livewire->getOwnerRecord()->cliente_id;
                        $data['creado_por'] = Auth::id();
                        $data['empresa_id'] = Auth::user()?->empresa_id ?? 1;

                        if (empty($data['numero'])) {
                            $data['numero'] = Pago::generarNumero();
                        }

                        return $data;
                    })
                    ->after(function ($record, $livewire) {
                        $factura = $livewire->getOwnerRecord();
                        $factura->actualizarSaldo();

                        Notification::make()
                            ->title('Pago registrado exitosamente')
                            ->body('El pago ' . $record->numero . ' ha sido registrado.')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('3xl'),

                    Tables\Actions\Action::make('confirmar')
                        ->label('Confirmar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record, $livewire) {
                            $record->update(['estado' => 'confirmado']);
                            $factura = $livewire->getOwnerRecord();
                            $factura->actualizarSaldo();

                            Notification::make()
                                ->title('Pago confirmado')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'pendiente'),

                    Tables\Actions\Action::make('rechazar')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['estado' => 'rechazado']);
                            Notification::make()
                                ->title('Pago rechazado')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'pendiente'),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => in_array($record->estado, ['pendiente', 'rechazado'])),
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
            ->searchPlaceholder('Buscar pago...')
            ->emptyStateHeading('No hay pagos registrados')
            ->emptyStateDescription('Registra un pago para esta factura.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->poll('60s');
    }
}