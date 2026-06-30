<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\Almacen;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LotesRelationManager extends RelationManager
{
    protected static string $relationship = 'lotes';

    protected static ?string $title = 'Lotes';

    protected static ?string $modelLabel = 'Lote';

    protected static ?string $pluralModelLabel = 'Lotes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Lote')
                    ->icon('heroicon-o-beaker')
                    ->description('Gestiona los lotes de este artículo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('numero_lote')
                                    ->label('Número de Lote')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: LOTE-2024-001')
                                    ->helperText('Número de lote único')
                                    ->columnSpan(1),

                                DatePicker::make('fecha_fabricacion')
                                    ->label('Fecha de Fabricación')
                                    ->native(false)
                                    ->helperText('Fecha de fabricación del lote')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('fecha_vencimiento')
                                    ->label('Fecha de Vencimiento')
                                    ->native(false)
                                    ->helperText('Fecha de vencimiento del lote')
                                    ->columnSpan(1),

                                TextInput::make('observaciones')
                                    ->label('Observaciones')
                                    ->maxLength(255)
                                    ->placeholder('Notas sobre este lote...')
                                    ->helperText('Observaciones adicionales del lote')
                                    ->columnSpan(1),
                            ]),

                        // Stock por almacén
                        Section::make('Stock por Almacén')
                            ->icon('heroicon-o-cube')
                            ->description('Distribución del lote en diferentes almacenes')
                            ->schema([
                                Repeater::make('stocks')
                                    ->label('')
                                    ->relationship('stocks')
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
                                                    ->helperText('Almacén donde se encuentra el lote')
                                                    ->columnSpan(1),

                                                TextInput::make('cantidad')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->step(0.01)
                                                    ->default(0)
                                                    ->placeholder('0.00')
                                                    ->helperText('Cantidad en este almacén')
                                                    ->columnSpan(1),
                                            ]),
                                    ])
                                    ->defaultItems(0)
                                    ->maxItems(10)
                                    ->collapsible()
                                    ->cloneable()
                                    ->addActionLabel('Agregar Stock por Almacén')
                                    ->reorderable()
                                    ->columnSpanFull(),
                            ]),

                        // Información del artículo
                        Forms\Components\Placeholder::make('articulo_info')
                            ->label('')
                            ->content(function ($livewire) {
                                $articulo = $livewire->getOwnerRecord();
                                if (!$articulo) {
                                    return 'No hay artículo asociado.';
                                }
                                
                                return "📦 Artículo: {$articulo->codigo} - {$articulo->descripcion}\n" .
                                       "📋 Unidad: " . ($articulo->unidadMedida->nombre ?? 'N/A');
                            })
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_lote')
            ->columns([
                TextColumn::make('numero_lote')
                    ->label('Número de Lote')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Lote copiado')
                    ->toggleable(),

                TextColumn::make('fecha_fabricacion')
                    ->label('Fabricación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->color(fn ($state) => $state && $state < now() ? 'danger' : 'success')
                    ->placeholder('-'),

                TextColumn::make('dias_restantes')
                    ->label('Días Restantes')
                    ->getStateUsing(function ($record) {
                        if (!$record->fecha_vencimiento) {
                            return '-';
                        }
                        
                        $dias = now()->diffInDays($record->fecha_vencimiento, false);
                        if ($dias < 0) {
                            return '<span class="text-red-600 font-bold">Vencido</span>';
                        }
                        if ($dias <= 30) {
                            return '<span class="text-orange-500 font-bold">' . $dias . ' días</span>';
                        }
                        return '<span class="text-green-600">' . $dias . ' días</span>';
                    })
                    ->html()
                    ->toggleable(),

                TextColumn::make('stock_total')
                    ->label('Stock Total')
                    ->getStateUsing(function ($record) {
                        return $record->stocks()->sum('cantidad');
                    })
                    ->numeric(2)
                    ->sortable()
                    ->color(fn ($state) => $state <= 0 ? 'danger' : 'success')
                    ->toggleable(),

                TextColumn::make('stocks_count')
                    ->label('Almacenes')
                    ->counts('stocks')
                    ->badge()
                    ->color('info')
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
                Tables\Filters\Filter::make('fecha_vencimiento')
                    ->label('Vencimiento')
                    ->form([
                        DatePicker::make('vencimiento_hasta')
                            ->label('Vencimiento hasta')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['vencimiento_hasta'],
                            fn ($query, $fecha) => $query->where('fecha_vencimiento', '<=', $fecha)
                        );
                    }),

                Tables\Filters\Filter::make('lotes_vencidos')
                    ->label('Lotes Vencidos')
                    ->query(fn ($query) => $query->where('fecha_vencimiento', '<', now())->whereNotNull('fecha_vencimiento')),

                Tables\Filters\Filter::make('lotes_proximos_vencer')
                    ->label('Próximos a Vencer (30 días)')
                    ->query(function ($query) {
                        $fechaLimite = now()->addDays(30);
                        return $query->where('fecha_vencimiento', '>=', now())
                            ->where('fecha_vencimiento', '<=', $fechaLimite);
                    }),

                Tables\Filters\SelectFilter::make('almacen_id')
                    ->label('Almacén con Stock')
                    ->options(fn () => Almacen::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload()
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['value'],
                            fn ($query, $almacenId) => $query->whereHas('stocks', function ($q) use ($almacenId) {
                                $q->where('almacen_id', $almacenId)
                                    ->where('cantidad', '>', 0);
                            })
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Lote')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Lote al Artículo')
                    ->modalWidth('5xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['articulo_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Lote agregado exitosamente')
                            ->body('El lote ' . $record->numero_lote . ' ha sido registrado.')
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
                            Notification::make()
                                ->title('Lote actualizado')
                                ->body('El lote ' . $record->numero_lote . ' ha sido actualizado.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            Notification::make()
                                ->title('Lote eliminado')
                                ->body('El lote ' . $record->numero_lote . ' ha sido eliminado.')
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
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar lote...')
            ->emptyStateHeading('Sin lotes registrados')
            ->emptyStateDescription('Agrega lotes para este artículo')
            ->emptyStateIcon('heroicon-o-beaker')
            ->poll('60s');
    }
}