<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class KardexPorAlmacenRelationManager extends RelationManager
{
    protected static string $relationship = 'kardex';

    protected static ?string $title = 'Historial de Kardex por almacén';

    protected static ?string $modelLabel = 'Movimiento';

    protected static ?string $pluralModelLabel = 'Movimientos Kardex';

    private static function tipoLabel(?string $tipo): string
    {
        return match ($tipo) {
            'compra' => 'Compra',
            'venta' => 'Venta',
            'transferencia_entrada' => 'Transferencia recibida',
            'transferencia_salida' => 'Transferencia enviada',
            'ajuste_incremento' => 'Ajuste positivo',
            'ajuste_decremento' => 'Ajuste negativo',
            'ajuste_fisico' => 'Ajuste físico',
            'devolucion_compra' => 'Devolución a proveedor',
            'devolucion_venta' => 'Devolución de cliente',
            'produccion_entrada' => 'Producción terminada',
            'produccion_salida' => 'Consumo de producción',
            'inventario_inicial' => 'Inventario inicial',
            'merma' => 'Merma',
            'despacho' => 'Despacho',
            'consignacion' => 'Consignación',
            default => filled($tipo) ? str($tipo)->headline()->toString() : 'Sin tipo',
        };
    }

    private static function tipoColor(?string $tipo): string
    {
        return match ($tipo) {
            'compra', 'transferencia_entrada', 'ajuste_incremento', 'devolucion_venta',
            'produccion_entrada', 'inventario_inicial', 'consignacion' => 'success',
            'venta', 'transferencia_salida', 'ajuste_decremento', 'devolucion_compra',
            'produccion_salida', 'merma', 'despacho' => 'danger',
            default => 'gray',
        };
    }

    private static function monto(float|int|null $monto): string
    {
        return 'Bs '.number_format((float) $monto, 2);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen del movimiento')
                ->description('Valores registrados y saldo resultante en el almacén afectado.')
                ->schema([
                    Grid::make(4)->schema([
                        Placeholder::make('fecha_movimiento')->label('Fecha y hora')->content(fn ($record) => $record?->fecha_movimiento?->format('d/m/Y H:i:s') ?? '—'),
                        Placeholder::make('almacen.nombre')->label('Almacén')->content(fn ($record) => $record?->almacen?->nombre ?? '—'),
                        Placeholder::make('tipo_movimiento')->label('Movimiento')->content(fn ($record) => self::tipoLabel($record?->tipo_movimiento)),
                        Placeholder::make('estado')->label('Estado')->content(fn ($record) => $record?->estado ? str($record->estado)->headline()->toString() : '—'),
                    ]),
                    Grid::make(4)->schema([
                        Placeholder::make('cantidad')->label('Cantidad')->content(fn ($record) => number_format((float) ($record?->cantidad ?? 0), 2)),
                        Placeholder::make('cantidad_anterior')->label('Saldo anterior')->content(fn ($record) => number_format((float) ($record?->cantidad_anterior ?? 0), 2)),
                        Placeholder::make('cantidad_posterior')->label('Saldo posterior')->content(fn ($record) => number_format((float) ($record?->cantidad_posterior ?? 0), 2)),
                        Placeholder::make('costo_total')->label('Valor del movimiento')->content(fn ($record) => self::monto($record?->costo_total)),
                    ]),
                ]),
            Section::make('Origen y trazabilidad')
                ->schema([
                    Grid::make(3)->schema([
                        Placeholder::make('documento_codigo')->label('Documento')->content(fn ($record) => $record?->documento_codigo ?: 'Movimiento manual'),
                        Placeholder::make('documento_origen')->label('Origen')->content(fn ($record) => ($record?->documento_tipo ?? 'manual').' #'.($record?->documento_id ?? 0)),
                        Placeholder::make('usuario.name')->label('Registrado por')->content(fn ($record) => $record?->usuario?->name ?? 'Sistema'),
                    ]),
                    Placeholder::make('detalle_trazabilidad')
                        ->label('Series, lotes y capas FIFO')
                        ->content(function ($record): HtmlString {
                            $series = collect($record?->series ?? [])->filter()->implode(', ') ?: 'Sin series';
                            $lotes = collect($record?->lotes ?? [])->map(fn ($lote) => is_array($lote) ? json_encode($lote) : $lote)->filter()->implode(', ') ?: 'Sin lotes';
                            $capas = count($record?->capas_fifo_consumidas ?? []);

                            return new HtmlString('<div class="text-sm"><strong>Series:</strong> '.e($series).'<br><strong>Lotes:</strong> '.e($lotes).'<br><strong>Capas FIFO consumidas:</strong> '.$capas.'</div>');
                        }),
                ]),
            Section::make('Motivo y observaciones')
                ->schema([
                    Placeholder::make('motivo')->label('Motivo')->content(fn ($record) => $record?->motivo ?: 'Sin motivo adicional'),
                    Placeholder::make('observaciones')->label('Observaciones')->content(fn ($record) => $record?->observaciones ?: 'Sin observaciones'),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('fecha_movimiento')->label('Fecha')->dateTime('d/m/Y H:i')->sortable()->description(fn ($record) => $record->fecha_contable?->format('Contable: d/m/Y') ?? null),
                TextColumn::make('almacen.nombre')->label('Almacén')->badge()->color('info')->searchable()->sortable(),
                BadgeColumn::make('tipo_movimiento')->label('Operación')->formatStateUsing(fn ($state) => self::tipoLabel($state))->color(fn ($state) => self::tipoColor($state))->searchable(),
                BadgeColumn::make('direccion')->label('Efecto')->formatStateUsing(fn ($state) => $state === 'entrada' ? 'Entrada' : 'Salida')->color(fn ($state) => $state === 'entrada' ? 'success' : 'danger'),
                TextColumn::make('cantidad')->label('Unidades')->numeric(2)->sortable()->weight('bold')->color(fn ($record) => $record->direccion === 'entrada' ? 'success' : 'danger')->prefix(fn ($record) => $record->direccion === 'entrada' ? '+ ' : '− '),
                TextColumn::make('saldo_resultante')->label('Saldo')->getStateUsing(fn ($record) => number_format((float) $record->cantidad_anterior, 2).' → '.number_format((float) $record->cantidad_posterior, 2))->description(fn ($record) => 'Anterior: '.number_format((float) $record->cantidad_anterior, 2))->weight('medium'),
                TextColumn::make('valoracion')->label('Valoración')->getStateUsing(fn ($record) => self::monto($record->costo_total))->description(fn ($record) => 'Unit.: '.self::monto($record->costo_unitario).' · Prom.: '.self::monto($record->costo_promedio))->weight('medium'),
                TextColumn::make('documento_codigo')->label('Documento')->placeholder('Manual')->searchable()->description(fn ($record) => ($record->documento_tipo ?? 'manual').' #'.($record->documento_id ?? 0))->toggleable(),
                BadgeColumn::make('estado')->label('Estado')->formatStateUsing(fn ($state) => str($state)->headline()->toString())->color(fn ($state) => match ($state) {'confirmado' => 'success', 'pendiente' => 'warning', default => 'danger'}),
            ])
            ->filters([
                SelectFilter::make('almacen_id')->label('Almacén')->relationship('almacen', 'nombre')->searchable()->preload(),
                SelectFilter::make('tipo_movimiento')->label('Operación')->options(collect(['compra','venta','transferencia_entrada','transferencia_salida','ajuste_incremento','ajuste_decremento','ajuste_fisico','devolucion_compra','devolucion_venta','produccion_entrada','produccion_salida','inventario_inicial','merma','despacho','consignacion'])->mapWithKeys(fn ($tipo) => [$tipo => self::tipoLabel($tipo)])->all()),
                SelectFilter::make('direccion')->label('Efecto')->options(['entrada' => 'Entrada', 'salida' => 'Salida']),
                SelectFilter::make('estado')->label('Estado')->options(['confirmado' => 'Confirmado', 'pendiente' => 'Pendiente', 'cancelado' => 'Cancelado', 'anulado' => 'Anulado']),
                Filter::make('fecha_movimiento')->label('Periodo')->schema([DatePicker::make('desde')->label('Desde'), DatePicker::make('hasta')->label('Hasta')])->query(fn ($query, array $data) => $query->when($data['desde'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_movimiento', '>=', $fecha))->when($data['hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_movimiento', '<=', $fecha))),
            ])
            ->recordActions([ViewAction::make()->slideOver()->modalWidth('6xl')])
            ->defaultSort('fecha_movimiento', 'desc')
            ->searchPlaceholder('Buscar documento o movimiento...')
            ->emptyStateHeading('Este artículo aún no tiene movimientos')
            ->emptyStateDescription('Las entradas, salidas y ajustes aparecerán aquí, separados por almacén.')
            ->emptyStateIcon('heroicon-o-arrows-right-left')
            ->poll('60s');
    }
}