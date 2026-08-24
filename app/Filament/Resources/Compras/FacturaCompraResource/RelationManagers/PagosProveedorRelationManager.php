<?php

namespace App\Filament\Resources\Compras\FacturaCompraResource\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PagosProveedorRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Pagos y respaldos';

    public function form(Form $form): Form
    {
        return $form->schema([
            DatePicker::make('fecha_pago')->label('Fecha de pago')->default(today())->required(),
            Select::make('tipo_pago')->label('Método de pago')->options([
                'efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque' => 'Cheque',
                'deposito' => 'Depósito', 'nota_credito' => 'Nota de crédito', 'otros' => 'Otro',
            ])->required(),
            TextInput::make('monto')->label('Monto')->numeric()->minValue(0.01)->required()
                ->maxValue(fn () => (float) $this->getOwnerRecord()->saldo)
                ->helperText('No puede superar el saldo pendiente de la factura.'),
            TextInput::make('referencia')->label('Referencia bancaria o comprobante')->maxLength(100),
            TextInput::make('banco')->label('Banco')->maxLength(100),
            TextInput::make('numero_cheque')->label('Número de cheque')->maxLength(50)
                ->visible(fn ($get) => $get('tipo_pago') === 'cheque'),
            DatePicker::make('fecha_cheque')->label('Fecha de cheque')
                ->visible(fn ($get) => $get('tipo_pago') === 'cheque'),
            FileUpload::make('respaldos')->label('Respaldos del pago')->multiple()
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                ->directory('compras/pagos-proveedor')
                ->helperText('Adjunte imágenes o PDF del comprobante. Este requisito protege la trazabilidad.'),
            Textarea::make('observaciones')->label('Observaciones')->rows(2),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->weight('bold'),
                TextColumn::make('fecha_pago')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('tipo_pago')->label('Método')->badge(),
                TextColumn::make('monto')->label('Monto')->money(fn () => $this->getOwnerRecord()->moneda ?? 'BOB'),
                TextColumn::make('referencia')->label('Referencia')->placeholder('Sin referencia'),
                TextColumn::make('respaldos')->label('Respaldos')->formatStateUsing(fn ($state) => count($state ?? []).' archivo(s)'),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make('registrarPago')
                    ->label('Registrar pago')
                    ->visible(fn () => ! in_array($this->getOwnerRecord()->estado, ['pagada', 'anulada'], true))
                    ->using(fn (array $data) => $this->getOwnerRecord()->registrarPago($data)),
            ])
            ->actions([
                Tables\Actions\Action::make('anular')
                    ->label('Anular pago')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular pago')
                    ->modalDescription('Se revertirá el asiento contable asociado y se recalculará el saldo de la factura.')
                    ->form([
                        Textarea::make('motivo')->label('Motivo de la anulación')->required()->maxLength(2000),
                    ])
                    ->action(fn (array $data, $record) => $record->anular($data['motivo']))
                    ->visible(fn ($record): bool => $record->estado === 'confirmado' && $this->getOwnerRecord()->estado !== 'anulada'),
            ])
            ->bulkActions([])
            ->emptyStateHeading('Aún no hay pagos')
            ->emptyStateDescription('Registre el pago y adjunte su respaldo para actualizar el saldo.');
    }
}
