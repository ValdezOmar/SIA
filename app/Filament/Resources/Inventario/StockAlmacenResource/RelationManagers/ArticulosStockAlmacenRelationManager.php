<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\RelationManagers;

use App\Models\Inventario\Articulo;
use App\Models\Inventario\Existencia;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ArticulosStockRelationManager extends RelationManager
{
    // ✅ Usar la relación correcta
    protected static string $relationship = 'existencias';

    protected static ?string $title = '📦 Artículos en Stock';

    protected static ?string $modelLabel = 'Artículo';

    protected static ?string $pluralModelLabel = 'Artículos en Stock';

    public static function canViewForRecord($record, $pageClass): bool
    {
        return $record !== null;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('articulo_id')
                            ->label('Artículo')
                            ->options(
                                fn() => Articulo::where('activo', true)
                                    ->orderBy('codigo')
                                    ->get()
                                    ->mapWithKeys(fn($item) => [
                                        $item->id => $item->codigo . ' - ' . ($item->nombre_comercial ?? $item->descripcion ?? 'Sin descripción')
                                    ])
                                    ->toArray()
                            )
                            ->required()
                            ->searchable(['codigo', 'descripcion', 'nombre_comercial'])
                            ->preload()
                            ->placeholder('Seleccione un artículo')
                            ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                return $rule->where('almacen_id', $this->getOwnerRecord()->id);
                            }),

                        TextInput::make('cantidad_disponible')
                            ->label('Stock Actual')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->placeholder('0.00'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('cantidad_reservada')
                            ->label('Stock Comprometido')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->placeholder('0.00'),

                        TextInput::make('cantidad_minima')
                            ->label('Stock Mínimo')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->placeholder('0.00'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('cantidad_maxima')
                            ->label('Stock Máximo')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->placeholder('0.00'),

                        TextInput::make('costo_promedio')
                            ->label('Costo Promedio')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->prefix('$')
                            ->placeholder('0.00'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('articulo')
            ->columns([
                TextColumn::make('articulo.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('articulo.nombre_comercial')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),

                TextColumn::make('articulo.descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cantidad_disponible')
                    ->label('Stock Actual')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('cantidad_reservada')
                    ->label('Comprometido')
                    ->numeric(2)
                    ->sortable()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('stock_disponible')
                    ->label('Disponible')
                    ->numeric(2)
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->stock_disponible)
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('cantidad_minima')
                    ->label('Mínimo')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cantidad_maxima')
                    ->label('Máximo')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('costo_promedio')
                    ->label('Costo Prom.')
                    ->money('BOB')
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('estado_stock')
                    ->label('Estado')
                    ->getStateUsing(fn($record) => $record->estado_stock)
                    ->colors([
                        'success' => 'Normal',
                        'warning' => 'Bajo Mínimo',
                        'danger' => 'Sin Stock',
                        'info' => 'Excedido',
                    ])
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('cantidad_disponible')
                    ->label('Con Stock')
                    ->boolean()
                    ->trueLabel('Con stock')
                    ->falseLabel('Sin stock')
                    ->queries(
                        true: fn($query) => $query->where('cantidad_disponible', '>', 0),
                        false: fn($query) => $query->where('cantidad_disponible', '=', 0),
                    ),

                Tables\Filters\Filter::make('bajo_minimo')
                    ->label('Stock bajo mínimo')
                    ->query(fn($query) => $query->whereRaw('cantidad_disponible <= cantidad_minima')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Artículo')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['almacen_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Artículo agregado al almacén')
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            $data['almacen_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        }),

                    Tables\Actions\DeleteAction::make(),
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