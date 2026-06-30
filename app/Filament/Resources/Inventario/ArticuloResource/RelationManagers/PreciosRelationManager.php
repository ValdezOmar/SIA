<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\ListaPrecio;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PreciosRelationManager extends RelationManager
{
    protected static string $relationship = 'precios';

    protected static ?string $title = 'Precios por Lista';

    protected static ?string $modelLabel = 'Precio';

    protected static ?string $pluralModelLabel = 'Precios';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración del Precio')
                    ->icon('heroicon-o-tag')
                    ->description('Asigna un precio a este artículo en una lista específica')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('lista_precio_id')
                                    ->label('Lista de Precios')
                                    ->options(fn () => ListaPrecio::where('activo', true)
                                        ->pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione una lista')
                                    ->helperText('Lista de precios donde se aplicará este precio')
                                    ->unique(ignoreRecord: true, modifyRuleUsing: function ($rule, $get) {
                                        return $rule->where('articulo_id', $get('articulo_id') ?? request()->route('record'));
                                    }),

                                TextInput::make('precio')
                                    ->label('Precio')
                                    ->numeric()
                                    ->required()
                                    ->prefix('$')
                                    ->step(0.01)
                                    ->minValue(0)
                                    ->placeholder('0.00')
                                    ->helperText('Precio de venta para esta lista'),
                            ]),
                    ]),

                Section::make('Información de la Lista')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Mostrar información de la lista seleccionada
                                Forms\Components\Placeholder::make('lista_info')
                                    ->label('')
                                    ->content(function ($get) {
                                        $listaId = $get('lista_precio_id');
                                        if (!$listaId) {
                                            return 'Seleccione una lista para ver su información';
                                        }
                                        
                                        $lista = ListaPrecio::find($listaId);
                                        if (!$lista) {
                                            return 'Lista no encontrada';
                                        }

                                        return "📋 {$lista->nombre}\n" .
                                               "🏷️ Código: {$lista->codigo}\n" .
                                               "💵 Moneda: " . ($lista->moneda ?? 'BOB');
                                    })
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('listaPrecio.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('listaPrecio.nombre')
                    ->label('Lista de Precios')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('listaPrecio.moneda')
                    ->label('Moneda')
                    ->badge()
                    ->color(fn ($state) => $state === 'BOB' ? 'success' : 'warning')
                    ->formatStateUsing(fn ($state) => $state === 'BOB' ? '🇧🇴 BOB' : '🇺🇸 USD')
                    ->toggleable(),

                TextColumn::make('precio')
                    ->label('Precio')
                    ->money(fn ($record) => $record->listaPrecio?->moneda ?? 'BOB')
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Precio copiado')
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
                Tables\Filters\SelectFilter::make('lista_precio_id')
                    ->label('Filtrar por Lista')
                    ->options(fn () => ListaPrecio::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('precio_mayor_que')
                    ->label('Precio mayor que')
                    ->form([
                        TextInput::make('precio_minimo')
                            ->label('Precio mínimo')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['precio_minimo'],
                            fn (Builder $query, $precio): Builder => $query->where('precio', '>=', $precio)
                        );
                    }),

                Tables\Filters\Filter::make('precio_menor_que')
                    ->label('Precio menor que')
                    ->form([
                        TextInput::make('precio_maximo')
                            ->label('Precio máximo')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['precio_maximo'],
                            fn (Builder $query, $precio): Builder => $query->where('precio', '<=', $precio)
                        );
                    }),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nuevo Precio')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Precio')
                    ->modalWidth('4xl')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Agregar automáticamente el articulo_id desde el registro padre
                        $data['articulo_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->after(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Precio agregado exitosamente')
                            ->body("El precio {$record->precio} ha sido asignado a {$record->listaPrecio->nombre}")
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
                        })
                        ->after(function ($record) {
                            \Filament\Notifications\Notification::make()
                                ->title('Precio actualizado')
                                ->body("El precio ha sido actualizado a {$record->precio}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar a otra lista')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->form([
                            Select::make('lista_precio_id')
                                ->label('Lista de Precios destino')
                                ->options(fn () => ListaPrecio::where('activo', true)
                                    ->where('id', '!=', fn ($query) => $query->select('lista_precio_id')->where('id', $this->getRecord()->id))
                                    ->pluck('nombre', 'id')
                                    ->toArray()
                                )
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Selecciona la lista donde quieres duplicar este precio'),
                        ])
                        ->action(function (array $data, $record) {
                            // Verificar si ya existe el precio en la lista destino
                            $exists = \App\Models\Inventario\PrecioArticulo::where('articulo_id', $record->articulo_id)
                                ->where('lista_precio_id', $data['lista_precio_id'])
                                ->exists();

                            if ($exists) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Error')
                                    ->body('Este artículo ya tiene un precio en la lista seleccionada')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $newRecord = $record->replicate();
                            $newRecord->lista_precio_id = $data['lista_precio_id'];
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            $lista = \App\Models\Inventario\ListaPrecio::find($data['lista_precio_id']);
                            \Filament\Notifications\Notification::make()
                                ->title('Precio duplicado exitosamente')
                                ->body("El precio ha sido duplicado a la lista {$lista->nombre}")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Duplicar Precio')
                        ->modalSubheading('Selecciona la lista destino para duplicar este precio'),

                    Tables\Actions\DeleteAction::make()
                        ->before(function ($record) {
                            \Filament\Notifications\Notification::make()
                                ->title('Precio eliminado')
                                ->body("El precio de {$record->listaPrecio->nombre} ha sido eliminado")
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
                        ->color('danger')
                        ->modalHeading('Eliminar Precios')
                        ->modalSubheading('¿Estás seguro de eliminar los precios seleccionados?'),

                    Tables\Actions\BulkAction::make('update_price')
                        ->label('Actualizar Precios')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('success')
                        ->form([
                            TextInput::make('incremento')
                                ->label('Incremento (%)')
                                ->numeric()
                                ->suffix('%')
                                ->placeholder('0')
                                ->helperText('Porcentaje a incrementar (ej: 10 para +10%, -5 para -5%)'),
                        ])
                        ->action(function (array $data, $records) {
                            $porcentaje = $data['incremento'] ?? 0;
                            $factor = 1 + ($porcentaje / 100);
                            
                            foreach ($records as $record) {
                                $record->update([
                                    'precio' => round($record->precio * $factor, 6)
                                ]);
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Precios actualizados')
                                ->body("Se aplicó un " . ($porcentaje >= 0 ? 'incremento' : 'decremento') . " del {$porcentaje}%")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Actualizar Precios')
                        ->modalSubheading('¿Deseas actualizar los precios seleccionados?'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar precios...')
            ->emptyStateHeading('Sin precios registrados')
            ->emptyStateDescription('Agrega precios para este artículo')
            ->emptyStateIcon('heroicon-o-tag')
            ->poll('60s');
    }
}