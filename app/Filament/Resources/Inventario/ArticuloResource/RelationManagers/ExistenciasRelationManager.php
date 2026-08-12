<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Existencia;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ExistenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'existencias';

    protected static ?string $title = '📊 Existencias por Almacén';

    protected static ?string $modelLabel = 'Existencia';

    protected static ?string $pluralModelLabel = 'Existencias';

    private static function formatearMonto($monto): string
    {
        return 'Bs ' . number_format($monto ?? 0, 2);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Gestión de Existencias')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('almacen_id')
                                    ->label('Almacén')
                                    ->options(
                                        fn() => Almacen::where('activo', true)
                                            ->pluck('nombre', 'id')
                                            ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un almacén')
                                    ->prefixIcon('heroicon-o-building-storefront')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                        return $rule->where('articulo_id', $get('articulo_id') ?? request()->route('record'));
                                    }),

                                TextInput::make('cantidad_disponible')
                                    ->label('Stock Actual')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(1.00)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefixIcon('heroicon-o-cube'),

                                TextInput::make('cantidad_comprometida')
                                    ->label('Stock Comprometido')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1.00)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefixIcon('heroicon-o-clock'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('cantidad_minima')
                                    ->label('Stock Mínimo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1.00)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefixIcon('heroicon-o-arrow-down'),

                                TextInput::make('cantidad_maxima')
                                    ->label('Stock Máximo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1.00)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefixIcon('heroicon-o-arrow-up'),

                                TextInput::make('costo_promedio')
                                    ->label('Costo Promedio')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.000001)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefix('$')
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('costo_acumulado')
                                    ->label('Costo Acumulado')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.000001)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefix('$')
                                    ->disabled(),

                                TextInput::make('ultimo_costo')
                                    ->label('Último Costo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.000001)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefix('$')
                                    ->disabled(),
                            ]),

                        Section::make('Información Adicional')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Placeholder::make('ultima_entrada')
                                            ->label('Última Entrada')
                                            ->content(fn($record) => $record?->ultima_entrada?->format('d/m/Y H:i') ?? 'N/A'),

                                        Placeholder::make('ultima_salida')
                                            ->label('Última Salida')
                                            ->content(fn($record) => $record?->ultima_salida?->format('d/m/Y H:i') ?? 'N/A'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('almacen')
            ->columns([
                TextColumn::make('almacen.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('almacen.nombre')
                    ->label('Almacén')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('cantidad_disponible')
                    ->label('Stock Actual')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success')
                    ->toggleable()
                    ->weight('bold'),

                TextColumn::make('cantidad_comprometida')
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
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cantidad_maxima')
                    ->label('Máximo')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('costo_promedio')
                    ->label('Costo Prom.')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->prefix('$'),

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

                TextColumn::make('ultima_entrada')
                    ->label('Última Entrada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ultima_salida')
                    ->label('Última Salida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('bajo_minimo')
                    ->label('Stock bajo mínimo')
                    ->query(fn($query) => $query->whereRaw('cantidad_disponible <= cantidad_minima')),

                Filter::make('sin_stock')
                    ->label('Sin stock')
                    ->query(fn($query) => $query->where('cantidad_disponible', '<=', 0)),

                Filter::make('excedido')
                    ->label('Stock excedido')
                    ->query(fn($query) => $query->whereRaw('cantidad_maxima > 0 AND cantidad_disponible >= cantidad_maxima')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Existencia')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Existencia')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data, $livewire): array {
                        $data['articulo_id'] = $livewire->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Existencia agregada exitosamente')
                            ->body('Se ha registrado la existencia en el almacén.')
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
                            $data['articulo_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        }),

                    Tables\Actions\Action::make('ajustar_stock')
                        ->label('Ajustar Stock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            TextInput::make('nuevo_stock')
                                ->label('Nuevo Stock Actual')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(1.00),
                            TextInput::make('motivo')
                                ->label('Motivo del Ajuste')
                                ->maxLength(255)
                                ->placeholder('Ej: Ajuste por inventario físico'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['cantidad_disponible' => $data['nuevo_stock']]);
                            Notification::make()
                                ->title('Stock ajustado exitosamente')
                                ->body("Nuevo stock: " . number_format($data['nuevo_stock'], 2))
                                ->success()
                                ->send();
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
            ->searchPlaceholder('Buscar existencia...')
            ->emptyStateHeading('Sin existencias registradas')
            ->emptyStateDescription('Agrega existencias para este artículo en diferentes almacenes.')
            ->emptyStateIcon('heroicon-o-cube')
            ->poll('60s');
    }
}