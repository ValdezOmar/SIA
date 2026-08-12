<?php

namespace App\Filament\Resources\Compras;

use App\Filament\Resources\Compras\PagoProveedorResource\Pages;
use App\Models\Compras\PagoProveedor;
use App\Models\Compras\Proveedor;
use App\Models\Compras\FacturaCompra;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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

class PagoProveedorResource extends Resource
{
    protected static ?string $model = PagoProveedor::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Pagos a Proveedores';

    protected static ?string $modelLabel = 'Pago a Proveedor';

    protected static ?string $pluralModelLabel = 'Pagos a Proveedores';

    protected static ?int $navigationSort = 5;

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos del Pago')
                    ->icon('heroicon-o-credit-card')
                    ->description('Registrar pago a proveedor')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->disabled()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('PAG-000001')
                                    ->helperText('Código único del pago')
                                    ->default(fn() => PagoProveedor::generarCodigo())
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->columnSpan(1),

                                Select::make('factura_id')
                                    ->label('Factura')
                                    ->options(
                                        fn() => FacturaCompra::whereIn('estado', ['registrada', 'parcial'])
                                            ->orderBy('codigo')
                                            ->get()
                                            ->mapWithKeys(fn($item) => [
                                                $item->id => $item->codigo . ' - ' . $item->proveedor->nombre . ' (Saldo: ' . self::formatearMonto($item->saldo, $item->moneda ?? 'BOB') . ')'
                                            ])
                                            ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione una factura')
                                    ->helperText('Factura a la que se aplica el pago')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->reactive()
                                    ->columnSpan(2),

                                Select::make('proveedor_id')
                                    ->label('Proveedor')
                                    ->options(
                                        fn() => Proveedor::where('activo', true)
                                            ->orderBy('nombre')
                                            ->pluck('nombre', 'id')
                                            ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un proveedor')
                                    ->helperText('Proveedor del pago')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->disabled()
                                    ->dehydrated()
                                    ->columnSpan(1),
                            ]),

                        Grid::make(4)
                            ->schema([
                                DatePicker::make('fecha_pago')
                                    ->label('Fecha Pago')
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->default(now())
                                    ->native(false)
                                    ->helperText('Fecha del pago')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->columnSpan(1),

                                TextInput::make('monto')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(1.00)
                                    ->placeholder('0.00')
                                    ->prefix(fn($get) => self::getSimboloMoneda($get('moneda') ?? 'BOB'))
                                    ->helperText('Monto del pago')
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
                                    ->reactive()
                                    ->columnSpan(1),

                                Select::make('tipo_pago')
                                    ->label('Tipo de Pago')
                                    ->options([
                                        'efectivo' => '💵 Efectivo',
                                        'transferencia' => '🏦 Transferencia',
                                        'cheque' => '📄 Cheque',
                                        'deposito' => '🏛️ Depósito',
                                        'nota_credito' => '📝 Nota de Crédito',
                                        'otros' => '📌 Otros',
                                    ])
                                    ->required()
                                    ->searchable()
                                    ->helperText('Método de pago')
                                    ->prefixIcon('heroicon-o-credit-card')
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
                    ->weight('bold'),

                TextColumn::make('factura.codigo')
                    ->label('Factura')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),                

                BadgeColumn::make('tipo_pago')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => match($state) {
                        'efectivo' => '💵 Efectivo',
                        'transferencia' => '🏦 Transferencia',
                        'cheque' => '📄 Cheque',
                        'deposito' => '🏛️ Depósito',
                        'nota_credito' => '📝 Nota Crédito',
                        'otros' => '📌 Otros',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'efectivo',
                        'info' => 'transferencia',
                        'warning' => 'cheque',
                        'gray' => 'deposito',
                        'gray' => 'nota_credito',
                        'gray' => 'otros',
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
                        'deposito' => 'Depósito',
                        'nota_credito' => 'Nota de Crédito',
                        'otros' => 'Otros',
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

                SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload(),
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
                        ->action(function ($record) {
                            $record->update(['estado' => 'confirmado']);
                            $record->factura->actualizarSaldo();
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
            ->emptyStateDescription('Registra un pago a proveedor.')
            ->emptyStateIcon('heroicon-o-credit-card')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPagoProveedors::route('/'),
            'create' => Pages\CreatePagoProveedor::route('/create'),
            'edit' => Pages\EditPagoProveedor::route('/{record}/edit'),
        ];
    }
}