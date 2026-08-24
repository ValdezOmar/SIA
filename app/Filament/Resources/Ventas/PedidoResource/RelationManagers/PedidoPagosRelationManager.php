<?php

namespace App\Filament\Resources\Ventas\PedidoResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PedidoPagosRelationManager extends RelationManager
{
    protected static string $relationship = 'pagos';

    protected static ?string $title = 'Pagos realizados';

    public function table(Table $table): Table
    {
        return $table
            ->description('Consulta los pagos registrados para la factura vinculada a este pedido. Los pagos se registran desde la factura, no desde aquí.')
            ->columns([
                TextColumn::make('numero')->label('Número')->weight('bold'),
                TextColumn::make('fecha_pago')->label('Fecha')->date('d/m/Y'),
                TextColumn::make('tipo_pago')->label('Método')->badge(),
                TextColumn::make('monto')->label('Monto')->money(fn ($record) => $record->moneda ?? 'BOB'),
                TextColumn::make('referencia')->label('Referencia')->placeholder('Sin referencia'),
                TextColumn::make('estado')->label('Estado')->badge(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No hay pagos registrados')
            ->emptyStateDescription('Los pagos aparecerán aquí después de registrarlos en la factura asociada.');
    }
}
