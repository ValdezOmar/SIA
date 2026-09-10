<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages\ListUbicacions;
use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages\CreateUbicacion;
use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages\EditUbicacion;
use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Clusters\ParametrosInventario\Resources\UbicacionResource\Pages;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Ubicacion;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UbicacionResource extends Resource
{
    protected static ?string $model = Ubicacion::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Ubicaciones';

    protected static ?string $modelLabel = 'Ubicación';

    protected static ?string $pluralModelLabel = 'Ubicaciones';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('almacen', fn (Builder $query) => $query
                ->whereIn('id', AlmacenResource::getEloquentQuery()->select('id')));
    }

    private static function almacenesDisponibles(): array
    {
        return AlmacenResource::getEloquentQuery()
            ->where('activo', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la Ubicación')
                    ->icon('heroicon-o-map-pin')
                    ->description('Configuración de la ubicación dentro del almacén')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('almacen_id')
                                    ->label('Almacén')
                                    ->options(fn () => self::almacenesDisponibles())
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un almacén')
                                    ->helperText('Almacén donde se encuentra esta ubicación')
                                    ->columnSpan(1)
                                    ->required(false)
                                    ->placeholder('Se genera automáticamente'),

                                TextInput::make('codigo')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->required(false)
                                    ->placeholder('Se genera automáticamente')
                                    ->label('Código de Ubicación')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: A-01-03')
                                    ->helperText('Código único para identificar la ubicación')
                                    ->columnSpan(1)
                                    ->required(false)
                                    ->placeholder('Se genera automáticamente')
                                    ->disabled(),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('pasillo')
                                    ->label('Pasillo')
                                    ->maxLength(255)
                                    ->placeholder('Ej: A')
                                    ->helperText('Número o letra del pasillo')
                                    ->columnSpan(1),

                                TextInput::make('estante')
                                    ->label('Estante')
                                    ->maxLength(255)
                                    ->placeholder('Ej: 01')
                                    ->helperText('Número del estante')
                                    ->columnSpan(1),

                                TextInput::make('nivel')
                                    ->label('Nivel')
                                    ->maxLength(255)
                                    ->placeholder('Ej: 3')
                                    ->helperText('Nivel dentro del estante')
                                    ->columnSpan(1),

                                TextInput::make('posicion')
                                    ->label('Posición')
                                    ->maxLength(255)
                                    ->placeholder('Ej: B')
                                    ->helperText('Posición dentro del nivel')
                                    ->columnSpan(1),
                            ]),

                        Toggle::make('activo')
                            ->label('Ubicación Activa')
                            ->default(true)
                            ->helperText('Desactive para inhabilitar temporalmente esta ubicación'),
                    ]),

                // Información del almacén seleccionado
                Placeholder::make('almacen_info')
                    ->label('')
                    ->content(function ($get) {
                        $almacenId = $get('almacen_id');
                        if (! $almacenId) {
                            return 'Seleccione un almacén para ver su información.';
                        }

                        $almacen = Almacen::find($almacenId);
                        if (! $almacen) {
                            return 'Almacén no encontrado.';
                        }

                        return "🏪 {$almacen->nombre}\n".
                               "📋 Código: {$almacen->codigo}\n".
                               ($almacen->direccion ? "📌 Dirección: {$almacen->direccion}" : '');
                    })
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->toggleable(),

                TextColumn::make('almacen.codigo')
                    ->label('Almacén Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(false),

                TextColumn::make('almacen.nombre')
                    ->label('Almacén')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('ubicacion_completa')
                    ->label('Ubicación Completa')
                    ->getStateUsing(function ($record) {
                        $partes = [];
                        if ($record->pasillo) {
                            $partes[] = "Pasillo {$record->pasillo}";
                        }
                        if ($record->estante) {
                            $partes[] = "Estante {$record->estante}";
                        }
                        if ($record->nivel) {
                            $partes[] = "Nivel {$record->nivel}";
                        }
                        if ($record->posicion) {
                            $partes[] = "Posición {$record->posicion}";
                        }

                        return implode(' → ', $partes) ?: '-';
                    })
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('pasillo')
                    ->label('Pasillo')
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('estante')
                    ->label('Estante')
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('nivel')
                    ->label('Nivel')
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('posicion')
                    ->label('Posición')
                    ->searchable()
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),

                TextColumn::make('existencias_count')
                    ->label('Stock')
                    ->counts('existencias')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
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
                SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->options(fn () => self::almacenesDisponibles())
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),

                Filter::make('con_stock')
                    ->label('Con Stock')
                    ->query(fn ($query) => $query->whereHas('existencias', function ($q) {
                        $q->where('cantidad', '>', 0);
                    })),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->codigo = $record->codigo.'-COPY-'.time();
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            Notification::make()
                                ->title('Ubicación duplicada exitosamente')
                                ->success()
                                ->send();
                        }),

                    Action::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->color(fn ($record) => $record->activo ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update(['activo' => ! $record->activo]);
                            Notification::make()
                                ->title($record->activo ? 'Ubicación activada' : 'Ubicación desactivada')
                                ->success()
                                ->send();
                        }),

                    DeleteAction::make(),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->action(fn ($records) => $records->each->update(['activo' => ! $records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado de ubicaciones'),
                ]),
            ])
            ->defaultSort('almacen.nombre')
            ->searchPlaceholder('Buscar ubicación...')
            ->emptyStateHeading('No hay ubicaciones registradas')
            ->emptyStateDescription('Crea tu primera ubicación para organizar tu almacén.')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ExistenciasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUbicacions::route('/'),
            'create' => CreateUbicacion::route('/create'),
            'edit' => EditUbicacion::route('/{record}/edit'),
        ];
    }
}
