<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\Inventario\PrecioArticulo;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\CreateAction;
use Exception;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use App\Models\Inventario\ListaPrecio;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PreciosRelationManager extends RelationManager
{
    protected static string $relationship = 'precios';

    protected static ?string $title = 'Precios por Lista';

    protected static ?string $modelLabel = 'Precio';

    protected static ?string $pluralModelLabel = 'Precios';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Configuración del Precio')
                    ->icon('heroicon-o-tag')
                    ->description('Asigna un precio a este artículo en una lista específica')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(1)
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
                                    ->columnSpanFull()
                                    ->placeholder('Seleccione una lista')
                                    ->helperText('Lista de precios donde se aplicará este precio')
                                    ->default(function ($set) {
                                        $primera = ListaPrecio::where('activo', true)->first();
                                        if ($primera) {
                                            $articuloId = request()->route('record');
                                            if ($articuloId) {
                                                $existe = PrecioArticulo::where('articulo_id', $articuloId)
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
                                            $existe = PrecioArticulo::where('articulo_id', $articuloId)
                                                ->where('lista_precio_id', $state)
                                                ->exists();
                                            $set('precio_disabled', $existe);
                                            if ($existe) {
                                                $set('precio', null);
                                            }
                                        }
                                    }),

                                TextInput::make('precio')
                                    ->columnSpanFull(),

                                Placeholder::make('precio_existente')
                                    ->label('La lista de precio ya tiene un precio asignado')
                                    ->columnSpanFull()
                                    ->hidden(function ($get) {

                                        $lista = $get('lista_precio_id');

                                        if (! $lista) {
                                            return true;
                                        }

                                        return ! PrecioArticulo::query()
                                            ->where('articulo_id', $this->getOwnerRecord()->id)
                                            ->where('lista_precio_id', $lista)
                                            ->exists();
                                    }),
                            ]),
                    ]),

                Section::make('Indicadores del precio')
                    ->description('Resumen del precio vigente y de su actividad auditada.')
                    ->columnSpanFull()
                    ->visible(fn ($record): bool => (bool) $record?->exists)
                    ->schema([
                        Placeholder::make('resumen_precio')
                            ->hiddenLabel()
                            ->content(fn (?PrecioArticulo $record): HtmlString => self::resumenPrecioHtml($record))
                            ->columnSpanFull(),
                    ]),

                Section::make('Información de la Lista')
                    ->icon('heroicon-o-document-text')
                    ->columnSpanFull()
                    ->schema([
                        Grid::make(1)
                            ->schema([
                                Placeholder::make('lista_info')
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
                                            $tienePrecio = PrecioArticulo::where('articulo_id', $articuloId)
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
                SelectFilter::make('lista_precio_id')
                    ->label('Filtrar por Lista')
                    ->options(
                        fn() => ListaPrecio::where('activo', true)
                            ->pluck('nombre', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                Filter::make('precio_mayor_que')
                    ->label('Precio mayor que')
                    ->schema([
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

                Filter::make('precio_menor_que')
                    ->label('Precio menor que')
                    ->schema([
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
                CreateAction::make()
                    ->label('Nuevo Precio')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Precio')
                    ->modalWidth('4xl')
                    ->mutateDataUsing(function (array $data): array {
                        $data['articulo_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    })
                    ->before(function (array $data) {
                        $articuloId = $this->getOwnerRecord()->id;
                        $listaPrecioId = $data['lista_precio_id'] ?? null;

                        if (! $listaPrecioId) {
                            throw new Exception('Debes seleccionar una lista de precios.');
                        }

                        $exists = PrecioArticulo::where('articulo_id', $articuloId)
                            ->where('lista_precio_id', $listaPrecioId)
                            ->exists();

                        if ($exists) {
                            $listaNombre = ListaPrecio::find($listaPrecioId)?->nombre ?? 'seleccionada';
                            throw new Exception("El artículo ya tiene un precio en la lista \"{$listaNombre}\".");
                        }
                    })
                    ->failureNotificationTitle('No se puede asignar el precio')
                    ->failureNotification(function (Exception $e) {
                        return Notification::make()
                            ->title('No se puede asignar el precio')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->actions([
                                Action::make('ok')
                                    ->label('Entendido')
                                    ->button()
                                    ->color('danger')
                                    ->close(),
                            ])
                            ->send();
                    })
                    ->after(function ($record) {
                        Notification::make()
                            ->title('Precio agregado exitosamente')
                            ->body("El precio de {$record->precio} ha sido asignado a {$record->listaPrecio->nombre}")
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->mutateDataUsing(function (array $data): array {
                            $data['articulo_id'] = $this->getOwnerRecord()->id;

                            return $data;
                        })
                        ->after(function ($record) {
                            Notification::make()
                                ->title('Precio actualizado')
                                ->body("El precio ha sido actualizado a {$record->precio}")
                                ->success()
                                ->send();
                        }),
                    DeleteAction::make()
                        ->before(function ($record) {
                            Notification::make()
                                ->title('Precio eliminado')
                                ->body("El precio de {$record->listaPrecio->nombre} ha sido eliminado")
                                ->warning()
                                ->send();
                        }),
                    ViewAction::make()
                        ->label('Ver auditoría')
                        ->icon('heroicon-o-clock')
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->schema([
                            Section::make('Precio vigente')
                                ->schema([
                                    TextEntry::make('listaPrecio.codigo')->label('Código de lista'),
                                    TextEntry::make('listaPrecio.nombre')->label('Lista'),
                                    TextEntry::make('precio')->label('Precio actual')->money(fn ($record) => $record->listaPrecio?->moneda ?? 'BOB'),
                                    TextEntry::make('updated_at')->label('Última actualización')->dateTime('d/m/Y H:i:s'),
                                ])
                                ->columns(4),
                            Section::make('Historial de cambios')
                                ->description('Auditoría completa de este precio, desde su creación hasta la actualidad.')
                                ->schema([
                                    RepeatableEntry::make('historial')
                                        ->label('')
                                        ->state(fn (PrecioArticulo $record) => $record->historial()->orderByDesc('registrado_en')->get())
                                        ->table([
                                            TableColumn::make('Fecha y hora'),
                                            TableColumn::make('Acción'),
                                            TableColumn::make('Lista anterior'),
                                            TableColumn::make('Precio anterior'),
                                            TableColumn::make('Lista nueva'),
                                            TableColumn::make('Precio nuevo'),
                                            TableColumn::make('Usuario'),
                                        ])
                                        ->schema([
                                            TextEntry::make('registrado_en')->dateTime('d/m/Y H:i:s'),
                                            TextEntry::make('accion')
                                                ->badge()
                                                ->formatStateUsing(fn (string $state): string => match ($state) {
                                                    'creado' => 'Creado',
                                                    'actualizado' => 'Actualizado',
                                                    'eliminado' => 'Eliminado',
                                                    default => ucfirst($state),
                                                })
                                                ->color(fn (string $state): string => match ($state) {
                                                    'creado' => 'success',
                                                    'actualizado' => 'warning',
                                                    'eliminado' => 'danger',
                                                    default => 'gray',
                                                }),
                                            TextEntry::make('lista_precio_anterior_nombre')
                                                ->getStateUsing(fn ($record): string => $record->lista_precio_anterior_nombre
                                                    ? trim(($record->lista_precio_anterior_codigo ? $record->lista_precio_anterior_codigo.' · ' : '').$record->lista_precio_anterior_nombre)
                                                    : '—'),
                                            TextEntry::make('precio_anterior')
                                                ->getStateUsing(fn ($record): string => $record->precio_anterior === null
                                                    ? '—'
                                                    : number_format((float) $record->precio_anterior, 2).' '.($record->lista_precio_anterior_moneda ?? $record->lista_precio_nuevo_moneda ?? 'BOB')),
                                            TextEntry::make('lista_precio_nuevo_nombre')
                                                ->getStateUsing(fn ($record): string => $record->lista_precio_nuevo_nombre
                                                    ? trim(($record->lista_precio_nuevo_codigo ? $record->lista_precio_nuevo_codigo.' · ' : '').$record->lista_precio_nuevo_nombre)
                                                    : '—'),
                                            TextEntry::make('precio_nuevo')
                                                ->getStateUsing(fn ($record): string => $record->precio_nuevo === null
                                                    ? '—'
                                                    : number_format((float) $record->precio_nuevo, 2).' '.($record->lista_precio_nuevo_moneda ?? $record->lista_precio_anterior_moneda ?? 'BOB')),
                                            TextEntry::make('usuario_nombre')->placeholder('Sistema'),
                                        ]),
                                ]),
                        ]),
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

    private static function resumenPrecioHtml(?PrecioArticulo $record): HtmlString
    {
        if (! $record?->exists) {
            return new HtmlString('');
        }

        $moneda = $record->listaPrecio?->moneda ?? 'BOB';
        $historial = $record->historial();
        $ultimoCambio = (clone $historial)
            ->orderByDesc('registrado_en')
            ->orderByDesc('id')
            ->first();
        $cantidadCambios = (clone $historial)->count();
        $variacion = 'Sin variación anterior';

        if ($ultimoCambio?->precio_anterior !== null && $ultimoCambio->precio_nuevo !== null) {
            $precioAnterior = (float) $ultimoCambio->precio_anterior;
            $diferencia = (float) $ultimoCambio->precio_nuevo - $precioAnterior;
            $porcentaje = $precioAnterior !== 0.0
                ? ' ('.number_format(($diferencia / $precioAnterior) * 100, 2).'%)'
                : '';

            $variacion = ($diferencia > 0 ? '+' : '').number_format($diferencia, 2).' '.$moneda.$porcentaje;
        }

        $actividad = $ultimoCambio
            ? $ultimoCambio->registrado_en->format('d/m/Y H:i:s').' · '.($ultimoCambio->usuario_nombre ?? 'Sistema')
            : 'Sin movimientos registrados';

        $indicadores = [
            ['titulo' => 'Precio vigente', 'valor' => number_format((float) $record->precio, 2).' '.$moneda, 'detalle' => $record->listaPrecio?->nombre ?? 'Lista no disponible'],
            ['titulo' => 'Variación del último cambio', 'valor' => $variacion, 'detalle' => 'Comparación con el precio anterior registrado'],
            ['titulo' => 'Cambios auditados', 'valor' => number_format($cantidadCambios), 'detalle' => 'Altas, modificaciones y eliminaciones de este precio'],
            ['titulo' => 'Última actividad', 'valor' => $actividad, 'detalle' => 'Fecha, hora y usuario que realizó el cambio'],
        ];

        $tarjetas = collect($indicadores)->map(fn (array $indicador): string =>
            '<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">'
                .'<p class="text-sm font-medium text-gray-500 dark:text-gray-400">'.e($indicador['titulo']).'</p>'
                .'<p class="mt-1 text-lg font-semibold text-gray-950 dark:text-white">'.e($indicador['valor']).'</p>'
                .'<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">'.e($indicador['detalle']).'</p>'
                .'</div>'
        )->implode('');

        return new HtmlString('<div class="grid grid-cols-1 gap-3">'.$tarjetas.'</div>');
    }
}
