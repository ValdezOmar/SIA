<?php

namespace App\Filament\Resources\Inventario;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Forms\Components\CalculoRepeater;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages\ListTransferenciaAlmacens;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages\CreateTransferenciaAlmacen;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages\EditTransferenciaAlmacen;
use App\Filament\Resources\Inventario\TransferenciaAlmacenResource\Pages;
use App\Filament\Clusters\ParametrosInventario\Resources\AlmacenResource;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\TransferenciaAlmacen;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TransferenciaAlmacenResource extends Resource
{
    protected static ?string $model = TransferenciaAlmacen::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Traspasos entre almacenes';

    protected static ?string $modelLabel = 'Traspaso';

    protected static ?string $pluralModelLabel = 'Traspasos entre almacenes';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        $almacenes = AlmacenResource::getEloquentQuery()->select('id');

        return parent::getEloquentQuery()->where(function (Builder $query) use ($almacenes): void {
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

    public static function canDelete(Model $record): bool
    {
        return $record->estado === 'borrador';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Cómo funciona')
                ->description('1. Prepare el traspaso. 2. Envíelo para descontar el origen. 3. El receptor asignado lo aprueba y el stock ingresa al destino.')
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->schema([]),
            Section::make('Ruta y responsable')
                ->description('El receptor será la única persona que podrá confirmar o rechazar la recepción.')
                ->schema([
                    TextInput::make('codigo')
                        ->label('Código')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Se genera al guardar'),
                    Select::make('almacen_origen_id')
                        ->label('Almacén de origen')
                        ->options(fn () => self::almacenesOrigen())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Aquí se descontará el stock al enviar.'),
                    Select::make('almacen_destino_id')
                        ->label('Almacén de destino')
                        ->options(fn () => self::almacenesDestino())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->different('almacen_origen_id')
                        ->helperText('El stock se incrementará sólo después de la aprobación.'),
                    Select::make('receptor_id')
                        ->label('Receptor responsable')
                        ->options(fn () => User::query()
                            ->when(Auth::user() && ! Auth::user()->hasAnyRole(['super_admin', 'admin']),
                                fn (Builder $query) => $query->where('empresa_id', Auth::user()->empresa_id ?? 0))
                            ->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Persona que verificará y aprobará la recepción.'),
                ])->columns(4),
            Section::make('Artículos a trasladar')
                ->description('Indique cantidades disponibles. Para productos con serie o lote, detalle los identificadores para conservar la trazabilidad.')
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
                                ->searchable()
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
                ->schema([
                    Textarea::make('observaciones')
                        ->label('Nota para el receptor')
                        ->rows(3)
                        ->maxLength(2000)
                        ->helperText('Incluya una referencia útil, por ejemplo guía de transporte o motivo del traslado.'),
                ]),
        ])->disabled(fn (?TransferenciaAlmacen $record) => $record && $record->estado !== 'borrador');
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
                TextColumn::make('estado')->label('Estado')->badge()->formatStateUsing(fn (string $state) => match ($state) {
                    'borrador' => 'Borrador', 'en_transito' => 'En tránsito', 'recibida' => 'Recibida', 'rechazada' => 'Rechazada', default => $state,
                })->color(fn (string $state) => match ($state) {
                    'borrador' => 'gray', 'en_transito' => 'warning', 'recibida' => 'success', 'rechazada' => 'danger', default => 'gray',
                }),
                TextColumn::make('fecha_envio')->label('Enviado')->dateTime('d/m/Y H:i')->sortable()->placeholder('Pendiente'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')->options([
                    'borrador' => 'Borrador', 'en_transito' => 'En tránsito', 'recibida' => 'Recibida', 'rechazada' => 'Rechazada',
                ]),
                SelectFilter::make('almacen_destino_id')->label('Destino')->options(fn () => self::almacenesDestino()),
            ])
            ->recordActions([
                EditAction::make()->label('Abrir'),
                DeleteAction::make()->visible(fn (TransferenciaAlmacen $record) => $record->estado === 'borrador'),
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
            'edit' => EditTransferenciaAlmacen::route('/{record}/edit'),
        ];
    }
}
