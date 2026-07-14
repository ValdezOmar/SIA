<?php

namespace App\Filament\Resources\Inventario\StockAlmacenResource\RelationManagers;

use App\Models\Inventario\Articulo;
use App\Models\Inventario\Ubicacion;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;


class ArticulosStockRelationManager extends RelationManager
{
    protected static string $relationship = 'existencias';

    protected static ?string $title = 'Artículos en Stock';

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
                        Placeholder::make('articulo_info')
                            ->label('Artículo')
                            ->content(function ($get) {
                                $articuloId = $get('articulo_id');
                                $articulo = \App\Models\Inventario\Articulo::find($articuloId);
                                if ($articulo) {
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-2">
                                            <span class="text-sm font-medium">' . $articulo->codigo_alterno . '</span>
                                            <span class="text-sm text-gray-500">-</span>
                                            <span class="text-sm">' . ($articulo->nombre_comercial ?? $articulo->descripcion ?? 'Sin descripción') . '</span>
                                        </div>'
                                    );
                                }
                                return 'No hay artículo seleccionado';
                            }),

                        TextInput::make('cantidad_disponible')
                            ->label('Stock Actual')
                            ->disabled()
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(1)
                            ->default(0)
                            ->placeholder('0.00'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('cantidad_comprometida')
                            ->label('Stock Comprometido')
                            ->disabled()
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->default(0)
                            ->placeholder('0.00'),

                        TextInput::make('cantidad_minima')
                            ->label('Stock Mínimo')
                            ->disabled()
                            ->numeric()
                            ->minValue(0)
                            ->step(1)
                            ->default(0)
                            ->placeholder('0.00'),
                    ]),

                Grid::make(2)
                    ->schema([
                        TextInput::make('cantidad_maxima')
                            ->label('Stock Máximo')
                            ->disabled()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->default(0)
                            ->placeholder('0.00'),
                    ]),

                Section::make('Ubicaciones dentro del Almacén')
                    ->icon('heroicon-o-map-pin')
                    ->description('Distribución del stock en ubicaciones específicas')
                    ->schema([
                        Repeater::make('ubicaciones')
                            ->label('')
                            ->relationship('ubicaciones')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('ubicacion_id')
                                            ->label('Ubicación')
                                            ->options(function ($get, $livewire) {
                                                $almacenId = $livewire->getOwnerRecord()?->id;

                                                if (!$almacenId) {
                                                    return [];
                                                }

                                                return Ubicacion::where('almacen_id', $almacenId)
                                                    ->where('activo', true)
                                                    ->get()
                                                    ->mapWithKeys(fn($item) => [
                                                        $item->id => $item->codigo . ' - ' . $item->ubicacion_completa
                                                    ])
                                                    ->toArray();
                                            })
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Seleccione una ubicación')
                                            ->helperText('Ubicación específica donde se encuentra el stock')
                                            ->distinct()
                                            ->columnSpan(1),

                                        TextInput::make('cantidad')
                                            ->label('Cantidad en Ubicación')
                                            ->numeric()
                                            ->required()
                                            ->minValue(0)
                                            ->step(1)
                                            ->default(0)
                                            ->placeholder('0.00')
                                            ->helperText('Cantidad en esta ubicación')
                                            ->columnSpan(1),
                                    ]),
                            ])
                            ->defaultItems(0)
                            ->maxItems(10)
                            ->collapsible()
                            ->cloneable()
                            ->addActionLabel('Agregar Ubicación')
                            ->reorderable()
                            ->columnSpanFull()
                            ->visible(fn($get) => $get('almacen_id') !== null),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('articulo')
            ->columns([
                ImageColumn::make('foto_catalogo')
                    ->label('')
                    ->square()
                    ->size(40)
                    ->defaultImageUrl(function ($record) {
                        return 'https://ui-avatars.com/api/?name=' . urlencode($record->nombre_comercial ?? $record->codigo) . '&color=7F9CF5&background=EBF4FF';
                    })
                    ->toggleable(),

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

                TextColumn::make('articulo.codigo_alterno')
                    ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('✅ Modelo copiado')
                    ->toggleable(isToggledHiddenByDefault: false),


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

                TextColumn::make('cantidad_comprometida')
                    ->label('Comprometido')
                    ->numeric(2)
                    ->sortable()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('stock_disponible')
                    ->label('Disponible')
                    ->disabled()
                    ->numeric(2)
                    ->sortable()
                    ->getStateUsing(fn($record) => $record->stock_disponible)
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('cantidad_minima')
                    ->label('Mínimo')
                    ->disabled()
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cantidad_maxima')
                    ->label('Máximo')
                    ->disabled()
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true),

                BadgeColumn::make('estado_stock')
                    ->label('Estado')
                    ->getStateUsing(function ($record) {
                        if ($record->cantidad_disponible <= 0) return 'Sin Stock';
                        if ($record->cantidad_disponible <= $record->cantidad_minima) return 'Bajo Mínimo';
                        if ($record->cantidad_maxima > 0 && $record->cantidad_disponible >= $record->cantidad_maxima) return 'Excedido';
                        return 'Normal';
                    })
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
                        ->label('Agregar ubicación')
                        ->slideOver()
                        ->modalWidth('4xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            $data['almacen_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        }),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }
}
