<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\Almacen;
use App\Models\Inventario\Ubicacion;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class ExistenciasRelationManager extends RelationManager
{
    protected static string $relationship = 'existencias';

    protected static ?string $title = 'Existencias por Almacén';

    protected static ?string $modelLabel = 'Existencia';

    protected static ?string $pluralModelLabel = 'Existencias';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Control de Existencias')
                    ->icon('heroicon-o-cube')
                    ->description('Gestiona el stock de este artículo en diferentes almacenes')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('almacen_id')
                                    ->label('Almacén')
                                    ->options(fn () => Almacen::where('activo', true)
                                        ->pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un almacén')
                                    ->helperText('Almacén donde se encuentra el stock')
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Resetear ubicaciones cuando cambia el almacén
                                        $set('ubicaciones', []);
                                    })
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                        return $rule->where('articulo_id', $get('articulo_id') ?? request()->route('record'));
                                    }),

                                TextInput::make('cantidad_disponible')
                                    ->label('Cantidad Disponible')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->helperText('Cantidad disponible en el almacén'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('cantidad_comprometida')
                                    ->label('Cantidad Comprometida')
                                    ->disabled()
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->helperText('Cantidad reservada para pedidos'),

                                TextInput::make('cantidad_pedida')
                                    ->label('Cantidad Pedida')
                                    ->numeric()
                                    ->disabled()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->helperText('Cantidad pedida a proveedores'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('cantidad_minima')
                                    ->label('Stock Mínimo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->helperText('Cantidad mínima de stock para alerta'),

                                TextInput::make('cantidad_maxima')
                                    ->label('Stock Máximo')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->nullable()
                                    ->placeholder('Sin límite')
                                    ->helperText('Cantidad máxima de stock permitida'),
                            ]),

                        // ✅ NUEVO: Gestión de ubicaciones dentro de la existencia
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
                                                        $almacenId = $livewire->getOwnerRecord()?->almacen_id ?? $get('../../almacen_id');
                                                        
                                                        if (!$almacenId) {
                                                            return [];
                                                        }
                                                        
                                                        return Ubicacion::where('almacen_id', $almacenId)
                                                            ->where('activo', true)
                                                            ->get()
                                                            ->mapWithKeys(fn ($item) => [
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
                                                    ->step(0.01)
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
                                    ->visible(fn ($get) => $get('almacen_id') !== null),
                            ]),

                        // Mostrar información del almacén seleccionado
                        Forms\Components\Placeholder::make('almacen_info')
                            ->label('')
                            ->content(function ($get) {
                                $almacenId = $get('almacen_id');
                                if (!$almacenId) {
                                    return 'Seleccione un almacén para ver su información.';
                                }

                                $almacen = Almacen::find($almacenId);
                                if (!$almacen) {
                                    return 'Almacén no encontrado.';
                                }

                                $ubicaciones = $almacen->ubicaciones()->count();

                                return "🏪 {$almacen->nombre}\n" .
                                       "📋 Código: {$almacen->codigo}\n" .
                                       "📍 Ubicaciones disponibles: {$ubicaciones}\n" .
                                       ($almacen->direccion ? "📌 Dirección: {$almacen->direccion}" : '');
                            })
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

                TextColumn::make('almacen.sucursal.nombre')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->visible(fn () => Schema::hasTable('conf_sucursales')),

                TextColumn::make('cantidad_disponible')
                    ->label('Disponible')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('cantidad_comprometida')
                    ->label('Comprometida')
                    ->numeric(2)
                    ->sortable()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('stock_total')
                    ->label('Stock Total')
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($record) => $record->stock_total <= 0 ? 'danger' : 'success')
                    ->toggleable()
                    ->getStateUsing(fn ($record) => $record->stock_total),

                // ✅ NUEVO: Columna para mostrar ubicaciones
                TextColumn::make('ubicaciones_resumen')
                    ->label('Ubicaciones')
                    ->getStateUsing(function ($record) {
                        $ubicaciones = $record->ubicaciones()->with('ubicacion')->get();
                        
                        if ($ubicaciones->isEmpty()) {
                            return 'Sin ubicaciones';
                        }
                        
                        return $ubicaciones->map(function ($item) {
                            return $item->ubicacion->codigo . ' (' . number_format($item->cantidad, 2) . ')';
                        })->implode(', ');
                    })
                    ->toggleable()
                    ->limit(30),

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
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('Sin límite'),

                TextColumn::make('estado_stock')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->cantidad_disponible <= 0) {
                            return 'Sin Stock';
                        }
                        if ($record->esta_bajo_minimo) {
                            return 'Bajo Mínimo';
                        }
                        return 'Normal';
                    })
                    ->color(fn ($state) => match($state) {
                        'Sin Stock' => 'danger',
                        'Bajo Mínimo' => 'warning',
                        'Normal' => 'success',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->options(fn () => Almacen::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('cantidad_disponible')
                    ->label('Con Stock')
                    ->boolean()
                    ->trueLabel('Con stock')
                    ->falseLabel('Sin stock')
                    ->queries(
                        true: fn ($query) => $query->where('cantidad_disponible', '>', 0),
                        false: fn ($query) => $query->where('cantidad_disponible', '=', 0),
                    ),

                Tables\Filters\Filter::make('bajo_minimo')
                    ->label('Stock bajo mínimo')
                    ->query(fn ($query) => $query->whereRaw('cantidad_disponible <= cantidad_minima')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Existencia')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Existencia al Artículo')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['articulo_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        $almacen = Almacen::find($record->almacen_id);
                        \Filament\Notifications\Notification::make()
                            ->title('Existencia agregada exitosamente')
                            ->body("Se ha registrado stock en el almacén {$almacen->nombre}")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('5xl')
                        ->mutateFormDataUsing(function (array $data): array {
                            $data['articulo_id'] = $this->getOwnerRecord()->id;
                            return $data;
                        })
                        ->after(function ($record) {
                            $almacen = Almacen::find($record->almacen_id);
                            \Filament\Notifications\Notification::make()
                                ->title('Existencia actualizada')
                                ->body("El stock en {$almacen->nombre} ha sido actualizado")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('ajustar_stock')
                        ->label('Ajustar Stock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            TextInput::make('nueva_cantidad')
                                ->label('Nueva Cantidad Disponible')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->placeholder('0.00'),
                            TextInput::make('motivo')
                                ->label('Motivo del Ajuste')
                                ->maxLength(255)
                                ->placeholder('Ej: Ajuste por inventario físico'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update([
                                'cantidad_disponible' => $data['nueva_cantidad']
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Stock ajustado exitosamente')
                                ->body("Nuevo stock: " . number_format($data['nueva_cantidad'], 2))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            $almacen = Almacen::find($record->almacen_id);
                            \Filament\Notifications\Notification::make()
                                ->title('Existencia eliminada')
                                ->body("El stock en {$almacen->nombre} ha sido eliminado")
                                ->warning()
                                ->send();
                        }),
                ])
                ->tooltip('Acciones')
                ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionados')
                        ->icon('heroicon-o-trash')
                        ->color('danger'),

                    Tables\Actions\BulkAction::make('ajustar_stock_bulk')
                        ->label('Ajustar Stock')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            TextInput::make('nueva_cantidad')
                                ->label('Nueva Cantidad Disponible')
                                ->numeric()
                                ->required()
                                ->minValue(0)
                                ->step(0.01)
                                ->placeholder('0.00'),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                $record->update([
                                    'cantidad_disponible' => $data['nueva_cantidad']
                                ]);
                            }
                            
                            \Filament\Notifications\Notification::make()
                                ->title('Stock ajustado exitosamente')
                                ->body('Se ajustaron ' . $records->count() . ' registros de stock')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Ajuste Masivo de Stock')
                        ->modalSubheading('¿Deseas ajustar el stock de los registros seleccionados?'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar existencias...')
            ->emptyStateHeading('Sin existencias registradas')
            ->emptyStateDescription('Agrega stock para este artículo en diferentes almacenes')
            ->emptyStateIcon('heroicon-o-cube')
            ->poll('60s');
    }
}