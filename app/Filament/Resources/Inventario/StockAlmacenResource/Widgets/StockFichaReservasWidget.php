<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\Widgets;

use App\Models\Inventario\Existencia;
use App\Models\Inventario\MovimientoInventario;
use App\Models\Ventas\Pedido;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class StockFichaReservasWidget extends TableWidget
{
    public ?Existencia $record = null;

    protected int|string|array $columnSpan = 'full';

    protected function getTableHeading(): string
    {
        return 'Reservas comerciales activas';
    }

    protected function getTableDescription(): ?string
    {
        return 'Clientes con unidades comprometidas en este almacén. Revise pagos y fechas antes de confirmar una nueva venta.';
    }

    protected function getTableQuery(): Builder
    {
        if (! $this->record) {
            return Pedido::query()->whereRaw('1 = 0');
        }

        $reservas = MovimientoInventario::query()
            ->select('documento_id')
            ->where('articulo_id', $this->record->articulo_id)
            ->where('almacen_id', $this->record->almacen_id)
            ->where('estado', 'confirmado')
            ->where('documento_tipo', 'pedido_reserva');

        return Pedido::query()
            ->with(['cliente:id,nombre,razon_social,celular', 'pagos' => fn ($query) => $query->where('estado', 'confirmado')])
            ->whereIn('id', $reservas)
            ->whereIn('estado', ['reservado', 'pendiente', 'parcial']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('cliente.nombre')->label('Cliente')->getStateUsing(fn (Pedido $record) => $record->cliente?->razon_social ?: $record->cliente?->nombre ?: 'Cliente no identificado')->description(fn (Pedido $record) => $record->cliente?->celular ?: 'Sin celular')->searchable(),
                TextColumn::make('codigo')->label('Pedido')->weight('bold')->description(fn (Pedido $record) => 'Entrega: '.($record->fecha_entrega_estimada?->format('d/m/Y') ?: 'Sin fecha')),
                TextColumn::make('unidades_reservadas')->label('Unidades')->getStateUsing(fn (Pedido $record) => MovimientoInventario::where('documento_tipo', 'pedido_reserva')->where('documento_id', $record->id)->where('articulo_id', $this->record?->articulo_id)->where('almacen_id', $this->record?->almacen_id)->where('estado', 'confirmado')->sum('cantidad'))->numeric(2)->color('warning')->weight('bold'),
                TextColumn::make('pagado')->label('Pagado')->getStateUsing(fn (Pedido $record) => $record->pagos->sum('monto'))->money('BOB')->color('success')->description(fn (Pedido $record) => $record->pagos->min('fecha_pago')?->format('Último pago: d/m/Y') ?: 'Sin pago confirmado'),
                BadgeColumn::make('estado')->label('Estado')->formatStateUsing(fn (Pedido $record) => $record->estado_label)->color(fn (Pedido $record) => $record->estado_color),
            ])
            ->emptyStateHeading('No hay reservas comerciales activas')
            ->emptyStateDescription('Las unidades libres pueden ofrecerse a nuevos clientes.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}