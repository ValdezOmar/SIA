<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\Pages;

use App\Models\Inventario\ListaPrecio;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListaPrecioResource extends Resource
{
    protected static ?string $model = ListaPrecio::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Listas de Precios';

    protected static ?string $modelLabel = 'Lista de Precios';

    protected static ?string $pluralModelLabel = 'Listas de Precios';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Ej: LST-001')
                            ->helperText('Código único de la lista'),

                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Ej: Lista General')
                            ->helperText('Nombre descriptivo de la lista'),
                    ]),

                Grid::make(2)
                    ->schema([
                        Select::make('moneda')
                            ->label('Moneda')
                            ->options([
                                'BOB' => '🇧🇴 Bolivianos (BOB)',
                                'USD' => '🇺🇸 Dólares (USD)',
                            ])
                            ->default('BOB')
                            ->required()
                            ->helperText('Moneda predeterminada para esta lista'),

                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Lista disponible para su uso'),
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
                    ->copyMessage('Código copiado'),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('moneda')
                    ->label('Moneda')
                    ->badge()
                    ->color(fn($state) => $state === 'BOB' ? 'success' : 'warning')
                    ->formatStateUsing(fn($state) => $state === 'BOB' ? '🇧🇴 BOB' : '🇺🇸 USD'),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('precios_count')
                    ->label('Precios')
                    ->counts('precios')
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
                Tables\Filters\SelectFilter::make('moneda')
                    ->label('Moneda')
                    ->options([
                        'BOB' => 'Bolivianos',
                        'USD' => 'Dólares',
                    ]),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),
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
                            $newRecord->codigo = $record->codigo . '-COPY';
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Lista duplicada exitosamente')
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
                        ->action(fn($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar listas de precios...')
            ->emptyStateHeading('No hay listas de precios')
            ->emptyStateDescription('Crea tu primera lista de precios')
            ->emptyStateIcon('heroicon-o-rectangle-stack');
    }

    public static function getRelations(): array
    {
        return [
            // Si quieres ver los precios asociados a esta lista
            // RelationManagers\PreciosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListListaPrecios::route('/'),
            'create' => Pages\CreateListaPrecio::route('/create'),
            'edit' => Pages\EditListaPrecio::route('/{record}/edit'),
        ];
    }
}
