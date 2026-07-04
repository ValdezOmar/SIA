<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources\ListaPrecioResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PreciosRelationManager extends RelationManager
{
    protected static string $relationship = 'precios';

    protected static ?string $title = '💰 Artículos con Precios';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('articulo.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('articulo.descripcion')
                    ->label('Artículo')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->articulo->descripcion),

                TextColumn::make('precio')
                    ->label('Precio')
                    ->money(fn ($record) => $this->getOwnerRecord()->moneda ?? 'BOB')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}