<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Clusters\ParametrosInventario\Resources\AtributoResource\Pages;
use App\Models\Inventario\Atributo;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class AtributoResource extends Resource
{
    protected static ?string $model = Atributo::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Atributos';

    protected static ?string $modelLabel = 'Atributo';

    protected static ?string $pluralModelLabel = 'Atributos';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos del Atributo')
                    ->icon('heroicon-o-tag')
                    ->description('Configuración del atributo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(50)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Ej: ATRIB-001')
                                    ->helperText('Código único del atributo')
                                    ->columnSpan(1),

                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Ej: Talla, Color, Material')
                                    ->helperText('Nombre del atributo')
                                    ->columnSpan(1),
                            ]),

                        TextInput::make('descripcion')
                            ->label('Descripción')
                            ->maxLength(255)
                            ->placeholder('Ej: Talla del producto (S, M, L, XL)')
                            ->helperText('Descripción opcional del atributo')
                            ->columnSpanFull(),

                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Atributo disponible para su uso'),
                    ]),
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

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->toggleable()
                    ->limit(30)
                    ->placeholder('-'),

                TextColumn::make('articulos_count')
                    ->label('Artículos')
                    ->counts('articulos')
                    ->badge()
                    ->color('info')
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
                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),

                Tables\Filters\Filter::make('nombre')
                    ->label('Buscar por nombre')
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->placeholder('Buscar atributo...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['nombre'],
                            fn ($query, $nombre) => $query->where('nombre', 'like', "%{$nombre}%")
                        );
                    }),
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
                                ->title('Atributo duplicado exitosamente')
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
                        ->modalHeading('Cambiar estado de atributos'),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar atributo...')
            ->emptyStateHeading('No hay atributos registrados')
            ->emptyStateDescription('Crea tu primer atributo para comenzar.')
            ->emptyStateIcon('heroicon-o-tag')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ArticulosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAtributos::route('/'),
            'create' => Pages\CreateAtributo::route('/create'),
            'edit' => Pages\EditAtributo::route('/{record}/edit'),
        ];
    }
}