<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\Almacen;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class ExistenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'existencias';

    protected static ?string $title = 'Existencias por Almacén';

    protected static ?string $modelLabel = 'Existencia';

    protected static ?string $pluralModelLabel = 'Existencias';

    private static function formatearMonto($monto): string
    {
        return 'Bs '.number_format($monto ?? 0, 2);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Almacén y stock')
                    ->description('Consulta las unidades actuales y comprometidas en este almacén.')
                    ->icon('heroicon-o-building-storefront')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Select::make('almacen_id')
                            ->label('Almacén')
                            ->options(
                                fn () => Almacen::where('activo', true)
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
                            })
                            ->disabledOn('edit')
                            ->helperText('Al editar, el almacén se conserva para mantener la trazabilidad de la existencia.')
                            ->columnSpanFull(),

                        TextInput::make('cantidad_disponible')
                            ->label('Stock Actual')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(1.00)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefixIcon('heroicon-o-cube')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Solo lectura. Aumenta o disminuye al registrar entradas y salidas en Kardex.')
                            ->columnSpanFull(),

                        TextInput::make('cantidad_comprometida')
                            ->label('Stock Comprometido')
                            ->numeric()
                            ->minValue(0)
                            ->step(1.00)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefixIcon('heroicon-o-clock')
                            ->disabled()
                            ->dehydrated()
                            ->helperText('Solo lectura. Refleja unidades reservadas en documentos operativos; se libera al completar o cancelar la operación.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Límites de reposición')
                    ->description('Configura referencias para detectar faltantes o exceso de inventario.')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('cantidad_minima')
                            ->label('Stock Mínimo')
                            ->numeric()
                            ->minValue(0)
                            ->step(1.00)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefixIcon('heroicon-o-arrow-down')
                            ->hint('Editable')
                            ->helperText('Señala cuándo reponer. Se considera bajo mínimo cuando el stock disponible es igual o menor.')
                            ->columnSpanFull(),

                        TextInput::make('cantidad_maxima')
                            ->label('Stock Máximo')
                            ->numeric()
                            ->minValue(0)
                            ->step(1.00)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefixIcon('heroicon-o-arrow-up')
                            ->hint('Editable')
                            ->helperText('Referencia para identificar exceso. Usa 0 si no deseas controlar un máximo.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Valorización del inventario')
                    ->description('Valores informativos calculados por los movimientos de Kardex.')
                    ->icon('heroicon-o-banknotes')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('costo_promedio')
                            ->label('Costo Promedio')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.000001)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefix('Bs')
                            ->disabled()
                            ->helperText('Solo lectura. Costo unitario promedio del stock registrado en este almacén.')
                            ->columnSpanFull(),

                        TextInput::make('costo_acumulado')
                            ->label('Costo Acumulado')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.000001)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefix('Bs')
                            ->disabled()
                            ->helperText('Solo lectura. Valor acumulado del inventario disponible.')
                            ->columnSpanFull(),

                        TextInput::make('ultimo_costo')
                            ->label('Último Costo')
                            ->numeric()
                            ->minValue(0)
                            ->step(0.000001)
                            ->default(0)
                            ->placeholder('0.00')
                            ->prefix('Bs')
                            ->disabled()
                            ->helperText('Solo lectura. Costo unitario de la entrada más reciente.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Últimos movimientos')
                    ->description('Fechas informativas de los últimos movimientos registrados.')
                    ->icon('heroicon-o-clock')
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('ultima_entrada')
                            ->label('Última entrada')
                            ->content(fn ($record) => $record?->ultima_entrada?->format('d/m/Y H:i') ?? 'Sin entradas registradas')
                            ->columnSpanFull(),

                        Placeholder::make('ultima_salida')
                            ->label('Última salida')
                            ->content(fn ($record) => $record?->ultima_salida?->format('d/m/Y H:i') ?? 'Sin salidas registradas')
                            ->columnSpanFull(),
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
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
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
                    ->getStateUsing(fn ($record) => $record->stock_disponible)
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
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
                        if ($record->cantidad_disponible <= 0) {
                            return 'Sin Stock';
                        }
                        if ($record->cantidad_disponible <= $record->cantidad_minima) {
                            return 'Bajo Mínimo';
                        }
                        if ($record->cantidad_maxima > 0 && $record->cantidad_disponible >= $record->cantidad_maxima) {
                            return 'Excedido';
                        }

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
                    ->query(fn ($query) => $query->whereRaw('cantidad_disponible <= cantidad_minima')),

                Filter::make('sin_stock')
                    ->label('Sin stock')
                    ->query(fn ($query) => $query->where('cantidad_disponible', '<=', 0)),

                Filter::make('excedido')
                    ->label('Stock excedido')
                    ->query(fn ($query) => $query->whereRaw('cantidad_maxima > 0 AND cantidad_disponible >= cantidad_maxima')),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->label('Configurar')
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->mutateDataUsing(function (array $data): array {
                            $data['articulo_id'] = $this->getOwnerRecord()->id;

                            return $data;
                        }),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar existencia...')
            ->emptyStateHeading('Sin existencias registradas')
            ->emptyStateDescription('Agrega existencias para este artículo en diferentes almacenes.')
            ->emptyStateIcon('heroicon-o-cube')
            ->poll('60s');
    }
}
