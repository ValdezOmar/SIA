<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\RelationManagers;

use App\Support\ArticuloSelectOptions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArticulosStockAlmacenRelationManager extends RelationManager
{
    protected static string $relationship = 'existencias';

    protected static ?string $title = 'Stock por artículo';

    protected static ?string $modelLabel = 'Existencia';

    protected static ?string $pluralModelLabel = 'Existencias';

    public function form(Form $form): Form
    {
        return $form->schema([
            Placeholder::make('informacion')
                ->label('Control de stock')
                ->content('Esta pantalla es sólo de consulta. Registre entradas, salidas o ajustes desde Kardex para conservar el historial y el costo correctos.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Libre para vender = stock físico menos reservado. Los precios corresponden a las listas activas. Consulte la ficha para ver las características completas.')
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'articulo.fabricante', 'articulo.grupoArticulo', 'articulo.unidadMedida',
                'articulo.precios' => fn ($query) => $query->whereHas('listaPrecio', fn (Builder $query) => $query->where('activo', true)),
                'articulo.precios.listaPrecio', 'articulo.codigosBarras',
            ]))
            ->paginated(false)
            ->searchPlaceholder('Código, modelo, nombre, marca o código de barras...')
            ->columns([
                TextColumn::make('articulo_resumen')
                    ->label('Artículo')
                    ->getStateUsing(fn ($record): string => $record->articulo
                        ? ArticuloSelectOptions::format($record->articulo)
                        : 'Artículo no disponible')
                    ->html()
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('articulo', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                            $query->where('codigo', 'like', "%{$search}%")
                                ->orWhere('codigo_alterno', 'like', "%{$search}%")
                                ->orWhere('nombre_comercial', 'like', "%{$search}%")
                                ->orWhere('descripcion', 'like', "%{$search}%")
                                ->orWhereHas('fabricante', fn (Builder $query) => $query->where('nombre', 'like', "%{$search}%"))
                                ->orWhereHas('codigosBarras', fn (Builder $query) => $query->where('codigo_barras', 'like', "%{$search}%"));
                        }));
                    }),
                TextColumn::make('articulo.unidadMedida.abreviatura')
                    ->label('Unidad')
                    ->placeholder('Sin unidad')
                    ->toggleable(),
                TextColumn::make('precios_venta')
                    ->label('Precios de venta')
                    ->getStateUsing(fn ($record): array => $record->articulo?->precios
                        ->map(fn ($precio): string => $precio->listaPrecio->nombre.': '.number_format((float) $precio->precio, 2).' '.$precio->listaPrecio->moneda)
                        ->all() ?? [])
                    ->listWithLineBreaks()
                    ->placeholder('Sin precio configurado')
                    ->wrap(),
                TextColumn::make('cantidad_disponible')
                    ->label('Stock físico')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($state) => (float) $state <= 0 ? 'danger' : 'success'),
                TextColumn::make('cantidad_comprometida')
                    ->label('Reservado')
                    ->numeric(2)
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('stock_neto')
                    ->label('Libre para vender')
                    ->getStateUsing(fn ($record) => (float) $record->cantidad_disponible - (float) $record->cantidad_comprometida)
                    ->numeric(2)
                    ->weight('bold')
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderByRaw('(cantidad_disponible - cantidad_comprometida) '.$direction))
                    ->color(fn ($state) => (float) $state <= 0 ? 'danger' : 'success'),
                TextColumn::make('cantidad_pedida')
                    ->label('Por recibir')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cantidad_minima')
                    ->label('Mínimo')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estado_stock')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        $disponible = (float) $record->cantidad_disponible - (float) $record->cantidad_comprometida;
                        if ($disponible <= 0) {
                            return 'Sin disponibilidad';
                        }
                        if ($disponible <= (float) $record->cantidad_minima) {
                            return 'Bajo mínimo';
                        }

                        return 'Disponible';
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Sin disponibilidad' => 'danger',
                        'Bajo mínimo' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('ultima_entrada')
                    ->label('Última entrada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin registros')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('articulo.grupoArticulo.nombre')
                    ->label('Grupo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Stock actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('con_stock')
                    ->label('Disponibilidad')
                    ->trueLabel('Con unidades libres')
                    ->falseLabel('Sin unidades libres')
                    ->queries(
                        true: fn ($query) => $query->whereRaw('(cantidad_disponible - cantidad_comprometida) > 0'),
                        false: fn ($query) => $query->whereRaw('(cantidad_disponible - cantidad_comprometida) <= 0'),
                    ),
                Tables\Filters\Filter::make('bajo_minimo')
                    ->label('Bajo mínimo')
                    ->query(fn ($query) => $query->whereRaw('(cantidad_disponible - cantidad_comprometida) <= cantidad_minima')),
                Tables\Filters\Filter::make('vendibles')
                    ->label('Solo artículos activos y vendibles')
                    ->query(fn (Builder $query) => $query->whereHas('articulo', fn (Builder $query) => $query->where('activo', true)->where('vendible', true))),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('ficha')
                    ->label('Ver ficha')
                    ->icon('heroicon-o-information-circle')
                    ->slideOver()
                    ->modalHeading('Información del artículo')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->infolist([
                        TextEntry::make('articulo.codigo')->label('Código')->copyable(),
                        TextEntry::make('articulo.nombre_comercial')->label('Nombre'),
                        TextEntry::make('articulo.codigo_alterno')->label('Modelo')->placeholder('Sin modelo'),
                        TextEntry::make('articulo.fabricante.nombre')->label('Marca')->placeholder('Sin marca'),
                        TextEntry::make('articulo.grupoArticulo.nombre')->label('Grupo'),
                        TextEntry::make('articulo.unidadMedida.nombre')->label('Unidad'),
                        TextEntry::make('articulo.activo')->label('Artículo activo')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                        TextEntry::make('articulo.vendible')->label('Habilitado para venta')->formatStateUsing(fn ($state) => $state ? 'Sí' : 'No'),
                        TextEntry::make('articulo.descripcion')->label('Descripción')->placeholder('Sin descripción')->columnSpanFull(),
                        TextEntry::make('articulo.caracteristicas')->label('Características')->placeholder('Sin características')->columnSpanFull(),
                        TextEntry::make('articulo.codigosBarras.codigo_barras')->label('Códigos de barras')->listWithLineBreaks(),
                        TextEntry::make('ultima_entrada')->label('Última entrada')->dateTime('d/m/Y H:i')->placeholder('Sin registros'),
                        TextEntry::make('ultima_salida')->label('Última salida')->dateTime('d/m/Y H:i')->placeholder('Sin registros'),
                    ]),
            ])
            ->bulkActions([])
            ->emptyStateHeading('No hay stock registrado')
            ->emptyStateDescription('Cuando se confirme una entrada en Kardex, el artículo aparecerá aquí.')
            ->emptyStateIcon('heroicon-o-cube')
            ->defaultSort('stock_neto', 'desc');
    }
}
