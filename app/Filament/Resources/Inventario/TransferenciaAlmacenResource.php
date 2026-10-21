<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages\CreateTransferenciaAlmacen;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages\ListTransferenciaAlmacens;
use App\Forms\Components\CalculoRepeater;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\TransferenciaAlmacen;
use App\Models\User;
use App\Support\ArticuloSelectOptions;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TransferenciaAlmacenResource extends Resource
{
    protected static ?string $model = TransferenciaAlmacen::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string|\UnitEnum|null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Traspasos entre almacenes';

    protected static ?string $modelLabel = 'Traspaso';

    protected static ?string $pluralModelLabel = 'Traspasos entre almacenes';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $usuarioId = Filament::auth()->id();

        if (! $usuarioId) {
            return null;
        }

        $pendientes = TransferenciaAlmacen::query()
            ->where('receptor_id', $usuarioId)
            ->where('estado', 'en_transito')
            ->whereDoesntHave('detalles', fn (Builder $detalle): Builder => $detalle
                ->whereNotNull('kardex_salida_id')
                ->orWhereNotNull('kardex_entrada_id'))
            ->count();

        return $pendientes > 0 ? (string) $pendientes : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Solicitudes pendientes de revisar asignadas a usted';
    }

    public static function getEloquentQuery(): Builder
    {
        $almacenes = AlmacenResource::getEloquentQuery()->select('id');

        return parent::getEloquentQuery()
            ->where(function (Builder $query) use ($almacenes): void {
                $query->whereIn('almacen_origen_id', clone $almacenes)
                    ->orWhereIn('almacen_destino_id', clone $almacenes);
            });
    }

    private static function almacenesOrigen(): array
    {
        return AlmacenResource::getEloquentQuery()->activo()->orderBy('nombre')->pluck('nombre', 'id')->all();
    }

    private static function almacenesDestino(): array
    {
        $user = Auth::user();

        return Almacen::query()->activo()
            ->when($user && ! $user->hasAnyRole(['super_admin', 'admin']),
                fn (Builder $query) => $query->where('empresa_id', $user->empresa_id ?? 0))
            ->orderBy('nombre')->pluck('nombre', 'id')->all();
    }

    private static function almacenOrigenUsuario(): ?int
    {
        $usuario = Auth::user();

        if (! $usuario?->sucursal_id) {
            return null;
        }

        return AlmacenResource::getEloquentQuery()
            ->activo()
            ->where('sucursal_id', $usuario->sucursal_id)
            ->orderBy('nombre')
            ->value('id');
    }

    private static function encargadoAlmacen(?int $almacenId): ?int
    {
        $sucursalId = Almacen::query()->whereKey($almacenId)->value('sucursal_id');

        if (! $sucursalId) {
            return null;
        }

        $deSucursal = fn (Builder $query): Builder => $query->whereHas(
            'empleado.historialActivo',
            fn (Builder $historial): Builder => $historial->where('sucursal_id', $sucursalId)
        );

        return User::query()->role('Almacenes')->where($deSucursal)->orderBy('name')->value('id')
            ?? User::query()->where($deSucursal)->orderBy('name')->value('id');
    }

    public static function canDelete(Model $record): bool
    {
        return $record->estado === 'en_transito'
            && ! $record->detalles()->whereNotNull('kardex_salida_id')->exists()
            && (int) $record->creado_por === (int) Auth::id();
    }

    private static function puedeResolver(TransferenciaAlmacen $record): bool
    {
        return $record->estado === 'en_transito'
            && (int) $record->receptor_id === (int) Auth::id()
            && ! $record->detalles()->where(fn (Builder $query): Builder => $query
                ->whereNotNull('kardex_salida_id')
                ->orWhereNotNull('kardex_entrada_id'))->exists();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Cómo funciona')
                    ->description('Al guardar se enviará automáticamente una solicitud al almacén de destino. El stock solo se moverá cuando el receptor acepte el traspaso.')
                    ->icon('heroicon-o-information-circle')
                    ->collapsible()
                    ->columnSpanFull()
                    ->schema([]),
                Section::make('Ruta y responsable')
                    ->description('El receptor será la única persona que podrá confirmar o rechazar la recepción.')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('Se genera al guardar'),
                        Select::make('almacen_origen_id')
                            ->label('Almacén de origen')
                            ->options(fn () => self::almacenesOrigen())
                            ->default(fn (): ?int => self::almacenOrigenUsuario())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('El stock se descontará solo cuando se acepte el traspaso.'),
                        Select::make('almacen_destino_id')
                            ->label('Almacén de destino')
                            ->options(fn () => self::almacenesDestino())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->different('almacen_origen_id')
                            ->afterStateUpdated(function ($state, callable $set): void {
                                if ($encargadoId = self::encargadoAlmacen((int) $state)) {
                                    $set('receptor_id', $encargadoId);
                                }
                            })
                            ->helperText('El stock se incrementará sólo después de la aprobación.'),
                        Select::make('receptor_id')
                            ->label('Receptor responsable')
                            ->options(fn () => User::query()
                                ->when(Auth::user() && ! Auth::user()->hasAnyRole(['super_admin', 'admin']), function (Builder $query): Builder {
                                    $empresaId = Auth::user()->empresa_id;

                                    return $query->whereHas('empleado.historialActivo', fn (Builder $historial): Builder => $historial
                                        ->where('empresa_id', $empresaId));
                                })
                                ->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Persona que decidirá aceptar o rechazar la solicitud.'),
                    ])->columns(4),
                Section::make('Artículos a trasladar')
                    ->description('Indique cantidades disponibles. Para productos con serie o lote, detalle los identificadores para conservar la trazabilidad.')
                    ->columnSpanFull()
                    ->schema([
                        CalculoRepeater::make('detalles')
                            ->relationship()
                            ->defaultItems(1)
                            ->minItems(1)
                            ->addActionLabel('Agregar artículo')
                            ->schema([
                                Select::make('articulo_id')
                                    ->label('Artículo')
                                    ->options(fn () => Articulo::query()->where('activo', true)
                                        ->when(Auth::user() && ! Auth::user()->hasAnyRole(['super_admin', 'admin']),
                                            fn (Builder $query) => $query->where('empresa_id', Auth::user()->empresa_id ?? 0))
                                        ->orderBy('codigo')->get()
                                        ->mapWithKeys(fn (Articulo $articulo) => [$articulo->id => $articulo->codigo.' — '.($articulo->nombre_comercial ?: $articulo->descripcion)])
                                        ->all())
                                    ->allowHtml()
                                    ->options(fn (): array => ArticuloSelectOptions::inventariablesSinStock())
                                    ->getSearchResultsUsing(fn (string $search): array => ArticuloSelectOptions::inventariablesSinStock($search))
                                    ->getOptionLabelUsing(fn ($value): ?string => ArticuloSelectOptions::labelSinStock($value))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->helperText('Seleccione el producto que saldrá del almacén origen.'),
                                TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->minValue(0.000001)
                                    ->step(0.000001)
                                    ->required(),
                                Textarea::make('series')
                                    ->label('Series')
                                    ->rows(2)
                                    ->visible(fn (Get $get) => (bool) Articulo::find($get('articulo_id'))?->maneja_series)
                                    ->helperText('Una serie por línea o separada por coma. Deben coincidir con la cantidad.')
                                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(PHP_EOL, $state) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? array_values(array_filter(array_map('trim', preg_split('/[,\\n]+/', $state)))) : $state),
                                Textarea::make('lotes')
                                    ->label('Lotes')
                                    ->rows(2)
                                    ->visible(fn (Get $get) => (bool) Articulo::find($get('articulo_id'))?->maneja_lotes)
                                    ->helperText('Un lote por línea con formato LOTE:CANTIDAD. La suma debe coincidir con la cantidad.')
                                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(PHP_EOL, $state) : $state)
                                    ->dehydrateStateUsing(fn ($state) => is_string($state) ? array_values(array_filter(array_map('trim', preg_split('/[,\\n]+/', $state)))) : $state),
                            ])->columns(2),
                    ]),
                Section::make('Observaciones')
                    ->columnSpanFull()
                    ->schema([
                        Textarea::make('observaciones')
                            ->label('Nota para el receptor')
                            ->rows(3)
                            ->maxLength(2000)
                            ->helperText('Incluya una referencia útil, por ejemplo guía de transporte o motivo del traslado.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable()->weight('bold'),
                TextColumn::make('almacenOrigen.nombre')->label('Origen')->searchable()->sortable(),
                TextColumn::make('almacenDestino.nombre')->label('Destino')->searchable()->sortable(),
                TextColumn::make('receptor.name')->label('Receptor asignado')->searchable(),
                TextColumn::make('detalles_count')->label('Artículos')->counts('detalles')->badge()->color('info'),
                TextColumn::make('estado')->label('Estado')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'en_transito' => 'Pendiente',
                    'recibida' => 'Recibida',
                    'rechazada' => 'Rechazada',
                    'borrador' => 'Anulada',
                    default => ucfirst($state),
                })->color(fn (string $state): string => match ($state) {
                    'en_transito' => 'warning',
                    'recibida' => 'success',
                    'rechazada' => 'danger',
                    'borrador' => 'gray',
                    default => 'gray',
                }),
                TextColumn::make('fecha_envio')->label('Solicitado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'en_transito' => 'Pendiente',
                        'recibida' => 'Recibida',
                        'rechazada' => 'Rechazada',
                        'borrador' => 'Anulada',
                    ])
                    ->default('en_transito')
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['value'] ?? null, function (Builder $q, string $estado): Builder {
                            $q->where('estado', $estado);

                            if ($estado === 'en_transito') {
                                $q->whereDoesntHave('detalles', fn (Builder $detalle): Builder => $detalle
                                    ->whereNotNull('kardex_salida_id')
                                    ->orWhereNotNull('kardex_entrada_id'));
                            }

                            return $q;
                        })),
                SelectFilter::make('almacen_origen_id')->label('Origen')->options(fn () => self::almacenesOrigen()),
                SelectFilter::make('almacen_destino_id')->label('Destino')->options(fn () => self::almacenesDestino()),
                SelectFilter::make('creado_por')->label('Solicitado por')->relationship('creador', 'name')->searchable()->preload(),
                SelectFilter::make('receptor_id')->label('Receptor')->relationship('receptor', 'name')->searchable()->preload(),
                Filter::make('fecha_envio')->label('Fecha de solicitud')->schema([
                    DatePicker::make('desde')->label('Desde'),
                    DatePicker::make('hasta')->label('Hasta'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['desde'] ?? null, fn (Builder $q, string $fecha): Builder => $q->whereDate('fecha_envio', '>=', $fecha))
                    ->when($data['hasta'] ?? null, fn (Builder $q, string $fecha): Builder => $q->whereDate('fecha_envio', '<=', $fecha))),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver traspaso')
                        ->icon('heroicon-o-eye')
                        ->modalHeading('Detalle de la solicitud de traspaso')
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->schema([
                            Section::make('Datos de la solicitud')
                                ->schema([
                                    TextEntry::make('codigo')->label('Código')->weight('bold')->copyable(),
                                    TextEntry::make('almacenOrigen.nombre')->label('Almacén de origen'),
                                    TextEntry::make('almacenDestino.nombre')->label('Almacén de destino'),
                                    TextEntry::make('creador.name')->label('Solicitado por')->placeholder('Usuario no disponible'),
                                    TextEntry::make('receptor.name')->label('Responsable de destino'),
                                    TextEntry::make('fecha_envio')->label('Fecha de solicitud')->dateTime('d/m/Y H:i'),
                                    TextEntry::make('observaciones')->label('Observaciones')->placeholder('Sin observaciones')->columnSpanFull(),
                                    TextEntry::make('observaciones_resolucion')->label('Observaciones de resolución')->placeholder('Sin observaciones de resolución')->columnSpanFull(),
                                ])
                                ->columns(3),
                            Section::make('Artículos solicitados')
                                ->description(fn (TransferenciaAlmacen $record): string => $record->detalles()->count().' artículo(s) en esta solicitud.')
                                ->schema([
                                    RepeatableEntry::make('detalles')
                                        ->label('')
                                        ->state(fn (TransferenciaAlmacen $record) => $record->detalles()->with('articulo')->get())
                                        ->table([
                                            TableColumn::make('Código'),
                                            TableColumn::make('Artículo'),
                                            TableColumn::make('Cantidad'),
                                            TableColumn::make('Series'),
                                            TableColumn::make('Lotes'),
                                        ])
                                        ->schema([
                                            TextEntry::make('articulo.codigo')->label('Código')->placeholder('Sin código'),
                                            TextEntry::make('articulo.nombre_comercial')->label('Artículo')->placeholder('Sin nombre'),
                                            TextEntry::make('cantidad')->label('Cantidad')->numeric(decimalPlaces: 4),
                                            TextEntry::make('series')->label('Series')->getStateUsing(fn ($record): string => collect($record->series ?? [])->flatten()->implode(', '))->placeholder('Sin series'),
                                            TextEntry::make('lotes')->label('Lotes')->getStateUsing(fn ($record): string => collect($record->lotes ?? [])->flatten()->implode(', '))->placeholder('Sin lotes'),
                                        ]),
                                ]),
                        ])
                        ->extraModalFooterActions([
                            Action::make('aceptar_desde_vista')
                                ->label('Aceptar traspaso')
                                ->icon('heroicon-o-check-circle')
                                ->color('success')
                                ->visible(fn (TransferenciaAlmacen $record): bool => self::puedeResolver($record))
                                ->schema([
                                    Textarea::make('observaciones')
                                        ->label('Observaciones de la aceptación')
                                        ->rows(3)
                                        ->maxLength(2000)
                                        ->helperText('Opcional. Se guardará junto al resultado de la revisión.'),
                                ])
                                ->action(function (TransferenciaAlmacen $record, array $data, Action $action): void {
                                    try {
                                        $record->recibir($data['observaciones'] ?? null);
                                    } catch (\Exception $exception) {
                                        if ($exception->getMessage() !== 'Stock insuficiente para la salida') {
                                            throw $exception;
                                        }

                                        $articulosSinStock = $record->detalles()
                                            ->with('articulo:id,codigo,nombre_comercial')
                                            ->get()
                                            ->filter(function ($detalle) use ($record): bool {
                                                $disponible = (float) \App\Models\Inventario\Existencia::query()
                                                    ->where('articulo_id', $detalle->articulo_id)
                                                    ->where('almacen_id', $record->almacen_origen_id)
                                                    ->value('cantidad_disponible');

                                                return $disponible < (float) $detalle->cantidad;
                                            })
                                            ->map(fn ($detalle): string => ($detalle->articulo?->codigo ?: $detalle->articulo?->nombre_comercial ?: 'Artículo').' (solicitado: '.rtrim(rtrim(number_format((float) $detalle->cantidad, 4, ',', '.'), '0'), ',').'; disponible: '.rtrim(rtrim(number_format((float) \App\Models\Inventario\Existencia::query()->where('articulo_id', $detalle->articulo_id)->where('almacen_id', $record->almacen_origen_id)->value('cantidad_disponible'), 4, ',', '.'), '0'), ',').')')
                                            ->implode('; ');

                                        Notification::make()
                                            ->title('No se pudo aceptar el traspaso')
                                            ->body("Solicitud {$record->codigo} sigue Pendiente. No se descontó stock ni se registraron movimientos en Kardex. Motivo: existencias insuficientes en el almacén de origen. ".($articulosSinStock ? 'Artículos: '.$articulosSinStock.'. ' : '').'Registre stock, libere reservas o reduzca la cantidad solicitada y vuelva a intentar.')
                                            ->danger()
                                            ->persistent()
                                            ->send();

                                        $action->cancelParentActions();

                                        return;
                                    }

                                    Notification::make()
                                        ->title('Traspaso aceptado')
                                        ->body("Solicitud {$record->codigo} aceptada. El stock se descontó del almacén de origen y se ingresó al destino; se registraron los movimientos relacionados en Kardex. Estado nuevo: Recibida.")
                                        ->success()->persistent()->send();
                                    $action->cancelParentActions();
                                }),
                            Action::make('rechazar_desde_vista')
                                ->label('Rechazar traspaso')
                                ->icon('heroicon-o-x-circle')
                                ->color('danger')
                                ->visible(fn (TransferenciaAlmacen $record): bool => self::puedeResolver($record))
                                ->schema([
                                    Textarea::make('motivo')->label('Motivo del rechazo')->required()->minLength(5)->maxLength(2000),
                                    Textarea::make('observaciones')
                                        ->label('Observaciones de la resolución')
                                        ->rows(3)
                                        ->maxLength(2000)
                                        ->helperText('Opcional. Añada detalles para quien solicitó el traspaso.'),
                                ])
                                ->action(function (TransferenciaAlmacen $record, array $data, Action $action): void {
                                    $record->rechazar($data['motivo'], $data['observaciones'] ?? null);
                                    Notification::make()
                                        ->title('Traspaso rechazado')
                                        ->body("Solicitud {$record->codigo} rechazada. El motivo y las observaciones quedaron registrados; no se movió stock ni se generaron movimientos Kardex. Estado nuevo: Rechazada.")
                                        ->success()->persistent()->send();
                                    $action->cancelParentActions();
                                }),
                        ]),
                    Action::make('anular')
                        ->label('Anular solicitud')
                        ->icon('heroicon-o-no-symbol')
                        ->color('gray')
                        ->visible(fn (TransferenciaAlmacen $record): bool => self::canDelete($record))
                        ->requiresConfirmation()
                        ->modalHeading('Anular solicitud de traspaso')
                        ->modalDescription('La solicitud dejará de estar pendiente. No se moverá stock.')
                        ->action(function (TransferenciaAlmacen $record): void {
                            $record->anular();
                            Notification::make()
                                ->title('Solicitud anulada')
                                ->body("Solicitud {$record->codigo} anulada por quien la creó. No se movió stock ni se generaron movimientos Kardex. Estado nuevo: Anulada.")
                                ->success()->persistent()->send();
                        }),
                ])->tooltip('Acciones')->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->emptyStateHeading('No hay traspasos registrados')
            ->emptyStateDescription('Cree un traspaso para mover stock entre almacenes con aprobación del destino.')
            ->emptyStateIcon('heroicon-o-arrows-right-left');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransferenciaAlmacens::route('/'),
            'create' => CreateTransferenciaAlmacen::route('/create'),
        ];
    }
}
