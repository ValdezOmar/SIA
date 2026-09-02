<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Support\ArticuloSelectOptions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KardexPorAlmacenRelationManager extends RelationManager
{
    protected static string $relationship = 'kardex';

    protected static ?string $title = 'Movimientos Kardex';

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos Kardex';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros de Búsqueda')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('articulo_id')
                                    ->label('Artículo')
                                    ->options(
                                        fn () => \App\Models\Inventario\Articulo::where('activo', true)
                                            ->orderBy('codigo')
                                            ->get()
                                            ->mapWithKeys(fn ($item) => [
                                                $item->id => $item->codigo.' - '.($item->nombre_comercial ?? $item->descripcion ?? 'Sin descripción'),
                                            ])
                                            ->toArray()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un artículo'),

                                Select::make('tipo_movimiento')
                                    ->label('Tipo de Movimiento')
                                    ->options([
                                        'compra' => 'Compra',
                                        'venta' => 'Venta',
                                        'transferencia_entrada' => 'Transferencia Entrada',
                                        'transferencia_salida' => 'Transferencia Salida',
                                        'ajuste_incremento' => 'Ajuste (+)',
                                        'ajuste_decremento' => 'Ajuste (-)',
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un tipo'),

                                DatePicker::make('fecha_movimiento')
                                    ->label('Fecha Movimiento')
                                    ->displayFormat('d/m/Y')
                                    ->native(false),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('articulo_resumen')
                    ->label('Artículo')
                    ->getStateUsing(fn ($record): string => $record->articulo
                        ? ArticuloSelectOptions::format($record->articulo)
                        : 'Artículo no disponible')
                    ->html(),

                BadgeColumn::make('tipo_movimiento')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'transferencia_entrada' => 'Transf. Ent.',
                        'transferencia_salida' => 'Transf. Sal.',
                        'ajuste_incremento' => 'Ajuste (+)',
                        'ajuste_decremento' => 'Ajuste (-)',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'compra',
                        'danger' => 'venta',
                        'info' => 'transferencia_entrada',
                        'warning' => 'transferencia_salida',
                        'success' => 'ajuste_incremento',
                        'danger' => 'ajuste_decremento',
                    ]),

                BadgeColumn::make('direccion')
                    ->label('Dir.')
                    ->formatStateUsing(fn ($state) => $state === 'entrada' ? 'Entrada' : 'Salida')
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'salida',
                    ])
                    ->width('60px'),

                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($record) => $record->direccion === 'entrada' ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('costo_unitario')
                    ->label('Costo Unit.')
                    ->numeric(2)
                    ->sortable()
                    ->prefix('$'),

                TextColumn::make('costo_total')
                    ->label('Costo Total')
                    ->numeric(2)
                    ->sortable()
                    ->prefix('$'),

                TextColumn::make('cantidad_anterior')
                    ->label('Saldo Ant.')
                    ->numeric(2)
                    ->sortable(),

                TextColumn::make('cantidad_posterior')
                    ->label('Saldo Post.')
                    ->numeric(2)
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('fecha_movimiento')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pendiente' => 'Pendiente',
                        'confirmado' => 'Confirmado',
                        'cancelado' => 'Cancelado',
                        'anulado' => 'Anulado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'confirmado',
                        'danger' => 'cancelado',
                        'danger' => 'anulado',
                    ]),
            ])
            ->filters([
                SelectFilter::make('tipo_movimiento')
                    ->label('Tipo')
                    ->options([
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'transferencia_entrada' => 'Transferencia Entrada',
                        'transferencia_salida' => 'Transferencia Salida',
                        'ajuste_incremento' => 'Ajuste (+)',
                        'ajuste_decremento' => 'Ajuste (-)',
                    ])
                    ->searchable()
                    ->preload(),

                Filter::make('fecha_movimiento')
                    ->label('Rango de Fechas')
                    ->form([
                        DatePicker::make('fecha_desde')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('fecha_hasta')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['fecha_desde'], fn ($q, $fecha) => $q->whereDate('fecha_movimiento', '>=', $fecha))
                            ->when($data['fecha_hasta'], fn ($q, $fecha) => $q->whereDate('fecha_movimiento', '<=', $fecha));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->modalWidth('5xl'),
            ])
            ->defaultSort('fecha_movimiento', 'desc')
            ->poll('60s');
    }
}
