<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\ListaPrecio;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
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
                                    ->options(
                                        fn() => ListaPrecio::where('activo', true)
                                            ->pluck('nombre', 'id')
                                            ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione una lista')
                                    ->helperText('Lista de precios donde se aplicará este precio')
                                    ->default(function ($set) {
                                        $primera = ListaPrecio::where('activo', true)->first();
                                        if ($primera) {
                                            $articuloId = request()->route('record');
                                            if ($articuloId) {
                                                $existe = \App\Models\Inventario\PrecioArticulo::where('articulo_id', $articuloId)
                                                    ->where('lista_precio_id', $primera->id)
                                                    ->exists();
                                                $set('precio_disabled', $existe);
                                                if ($existe) {
                                                    $set('precio', null);
                                                }
                                            }

                                            return $primera->id;
                                        }

                                        return null;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        $articuloId = request()->route('record');
                                        if ($articuloId && $state) {
                                            $existe = \App\Models\Inventario\PrecioArticulo::where('articulo_id', $articuloId)
                                                ->where('lista_precio_id', $state)
                                                ->exists();
                                            $set('precio_disabled', $existe);
                                            if ($existe) {
                                                $set('precio', null);
                                            }
                                        }
                                    }),

                                TextInput::make('precio'),

                                Placeholder::make('precio_existente')
                                    ->label('La lista de precio ya tiene un precio asignado')
                                    ->hidden(function ($get) {

                                        $lista = $get('lista_precio_id');

                                        if (! $lista) {
                                            return true;
                                        }

                                        return ! \App\Models\Inventario\PrecioArticulo::query()
                                            ->where('articulo_id', $this->getOwnerRecord()->id)
                                            ->where('lista_precio_id', $lista)
                                            ->exists();
                                    }),
                            ]),
                    ]),

                Section::make('Información de la Lista')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('lista_info')
                                    ->label('')
                                    ->content(function ($get) {
                                        $listaId = $get('lista_precio_id');
                                        if (! $listaId) {
                                            return 'Seleccione una lista para ver su información';
                                        }

                                        $lista = ListaPrecio::find($listaId);
                                        if (! $lista) {
                                            return 'Lista no encontrada';
                                        }

                                        $articuloId = request()->route('record');
                                        $tienePrecio = false;
                                        if ($articuloId && $listaId) {
                                            $tienePrecio = \App\Models\Inventario\PrecioArticulo::where('articulo_id', $articuloId)
                                                ->where('lista_precio_id', $listaId)
                                                ->exists();
                                        }

                                        $html = "{$lista->nombre}\n" .
                                            "Código: {$lista->codigo}\n" .
                                            'Moneda: ' . ($lista->moneda ?? 'BOB');

                                        if ($tienePrecio) {
                                            $html .= "\n\nEste artículo ya tiene un precio en esta lista";
                                        }

                                        return $html;
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
                    ->color(fn($state) => $state === 'BOB' ? 'success' : 'warning')
                    ->formatStateUsing(fn($state) => $state === 'BOB' ? 'BOB' : 'USD')
                    ->toggleable(),

                TextColumn::make('precio')
                    ->label('Precio')
                    ->money(fn($record) => $record->listaPrecio?->moneda ?? 'BOB')
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
                    ->options(
                        fn() => ListaPrecio::where('activo', true)
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
                            fn(Builder $query, $precio): Builder => $query->where('precio', '>=', $precio)
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
                            fn(Builder $query, $precio): Builder => $query->where('precio', '<=', $precio)
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
                        $data['articulo_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    })
                    ->before(function (array $data) {
                        $articuloId = $this->getOwnerRecord()->id;
                        $listaPrecioId = $data['lista_precio_id'] ?? null;

                        if (! $listaPrecioId) {
                            throw new \Exception('Debes seleccionar una lista de precios.');
                        }

                        $exists = \App\Models\Inventario\PrecioArticulo::where('articulo_id', $articuloId)
                            ->where('lista_precio_id', $listaPrecioId)
                            ->exists();

                        if ($exists) {
                            $listaNombre = \App\Models\Inventario\ListaPrecio::find($listaPrecioId)?->nombre ?? 'seleccionada';
                            throw new \Exception("El artículo ya tiene un precio en la lista \"{$listaNombre}\".");
                        }
                    })
                    ->failureNotificationTitle('No se puede asignar el precio')
                    ->failureNotification(function (\Exception $e) {
                        return \Filament\Notifications\Notification::make()
                            ->title('No se puede asignar el precio')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('ok')
                                    ->label('Entendido')
                                    ->button()
                                    ->color('danger')
                                    ->close(),
                            ])
                            ->send();
                    })
                    ->after(function ($record) {
                        \Filament\Notifications\Notification::make()
                            ->title('Precio agregado exitosamente')
                            ->body("El precio de {$record->precio} ha sido asignado a {$record->listaPrecio->nombre}")
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
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar precios...')
            ->emptyStateHeading('Sin precios registrados')
            ->emptyStateDescription('Agrega precios para este artículo')
            ->emptyStateIcon('heroicon-o-tag')
            ->poll('60s');
    }
}
