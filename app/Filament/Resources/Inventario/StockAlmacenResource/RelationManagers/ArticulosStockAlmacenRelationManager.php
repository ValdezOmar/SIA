<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\RelationManagers;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
            ->columns([
                ImageColumn::make('articulo.foto_catalogo')
                    ->label('')
                    ->square()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->articulo?->codigo ?? 'ART').'&color=1D4ED8&background=DBEAFE'),
                TextColumn::make('articulo.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('articulo.nombre_comercial')
                    ->label('Artículo')
                    ->description(fn ($record) => $record->articulo?->descripcion)
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sin nombre comercial')
                    ->limit(45),
                TextColumn::make('cantidad_disponible')
                    ->label('Disponible físicamente')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($state) => (float) $state <= 0 ? 'danger' : 'success'),
                TextColumn::make('cantidad_comprometida')
                    ->label('Comprometido')
                    ->numeric(2)
                    ->sortable()
                    ->color('warning'),
                TextColumn::make('stock_neto')
                    ->label('Disponible para usar')
                    ->getStateUsing(fn ($record) => (float) $record->cantidad_disponible - (float) $record->cantidad_comprometida)
                    ->numeric(2)
                    ->color(fn ($state) => (float) $state <= 0 ? 'danger' : 'success'),
                TextColumn::make('cantidad_minima')
                    ->label('Mínimo')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estado_stock')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function ($record): string {
                        $disponible = (float) $record->cantidad_disponible;
                        if ($disponible <= 0) {
                            return 'Sin stock';
                        }
                        if ($disponible <= (float) $record->cantidad_minima) {
                            return 'Bajo mínimo';
                        }

                        return 'Normal';
                    })
                    ->color(fn (string $state) => match ($state) {
                        'Sin stock' => 'danger',
                        'Bajo mínimo' => 'warning',
                        default => 'success',
                    }),
                TextColumn::make('ultima_entrada')
                    ->label('Última entrada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Sin registros')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('con_stock')
                    ->label('Disponibilidad')
                    ->trueLabel('Con stock')
                    ->falseLabel('Sin stock')
                    ->queries(
                        true: fn ($query) => $query->where('cantidad_disponible', '>', 0),
                        false: fn ($query) => $query->where('cantidad_disponible', '<=', 0),
                    ),
                Tables\Filters\Filter::make('bajo_minimo')
                    ->label('Bajo mínimo')
                    ->query(fn ($query) => $query->whereRaw('cantidad_disponible <= cantidad_minima')),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->emptyStateHeading('No hay stock registrado')
            ->emptyStateDescription('Cuando se confirme una entrada en Kardex, el artículo aparecerá aquí.')
            ->emptyStateIcon('heroicon-o-cube')
            ->defaultSort('cantidad_disponible', 'desc');
    }
}
