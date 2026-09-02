<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\RelationManagers;

use App\Models\Inventario\Articulo;
use App\Support\ArticuloSelectOptions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PreciosRelationManager extends RelationManager
{
    protected static string $relationship = 'precios';

    protected static ?string $title = 'Artículos y precios';

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('articulo_id')
                ->label('Artículo')
                ->options(fn () => Articulo::query()->where('activo', true)->orderBy('codigo')->get()
                    ->mapWithKeys(fn (Articulo $articulo) => [$articulo->id => $articulo->codigo.' — '.($articulo->nombre_comercial ?: $articulo->descripcion)])
                    ->all())
                ->searchable()
                ->required()
                ->helperText('Elija el artículo al que aplicará este precio.'),
            TextInput::make('precio')
                ->label('Precio')
                ->numeric()
                ->minValue(0)
                ->step(0.01)
                ->required()
                ->helperText('Importe en la moneda definida para esta lista.'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('articulo_resumen')
                    ->label('Artículo')
                    ->getStateUsing(fn ($record): string => $record->articulo
                        ? ArticuloSelectOptions::format($record->articulo)
                        : 'Artículo no disponible')
                    ->html(),
                TextColumn::make('precio')->label('Precio')
                    ->money(fn () => $this->getOwnerRecord()->moneda ?? 'BOB')
                    ->sortable(),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([Tables\Actions\CreateAction::make()->label('Asignar precio')])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Aún no hay precios')
            ->emptyStateDescription('Asigne el precio de los artículos para esta lista.');
    }
}
