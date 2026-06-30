<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\UbicacionResource\Pages;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\Ubicacion;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UbicacionResource extends Resource
{
    protected static ?string $model = Ubicacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Ubicaciones';

    protected static ?string $modelLabel = 'Ubicación';

    protected static ?string $pluralModelLabel = 'Ubicaciones';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos de la Ubicación')
                    ->icon('heroicon-o-map-pin')
                    ->description('Configuración de la ubicación dentro del almacén')
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
                                    ->helperText('Almacén donde se encuentra esta ubicación')
                                    ->columnSpan(1),

                                TextInput::make('codigo')
                                    ->label('Código de Ubicación')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: A-01-03')
                                    ->helperText('Código único para identificar la ubicación')
                                    ->columnSpan(1),
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

                        return "🏪 {$almacen->nombre}\n" .
                               "📋 Código: {$almacen->codigo}\n" .
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
                        if ($record->pasillo) $partes[] = "Pasillo {$record->pasillo}";
                        if ($record->estante) $partes[] = "Estante {$record->estante}";
                        if ($record->nivel) $partes[] = "Nivel {$record->nivel}";
                        if ($record->posicion) $partes[] = "Posición {$record->posicion}";
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
                Tables\Filters\SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->options(fn () => Almacen::where('activo', true)
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),

                Tables\Filters\Filter::make('con_stock')
                    ->label('Con Stock')
                    ->query(fn ($query) => $query->whereHas('existencias', function ($q) {
                        $q->where('cantidad', '>', 0);
                    })),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->codigo = $record->codigo . '-COPY-' . time();
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Ubicación duplicada exitosamente')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->color(fn ($record) => $record->activo ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update(['activo' => !$record->activo]);
                            \Filament\Notifications\Notification::make()
                                ->title($record->activo ? 'Ubicación activada' : 'Ubicación desactivada')
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
                    Tables\Actions\BulkAction::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->action(fn ($records) => $records->each->update(['activo' => !$records->first()->activo]))
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
            'index' => Pages\ListUbicacions::route('/'),
            'create' => Pages\CreateUbicacion::route('/create'),
            'edit' => Pages\EditUbicacion::route('/{record}/edit'),
        ];
    }
}