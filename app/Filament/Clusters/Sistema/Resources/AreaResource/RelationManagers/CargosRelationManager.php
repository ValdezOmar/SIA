<?php

namespace App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;

class CargosRelationManager extends RelationManager
{
    protected static string $relationship = 'cargos';
    protected static ?string $title = 'Cargos';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('nombre')
                ->label('Nombre del Cargo')
                ->placeholder('Ej. Contador, Analista, Programador')
                ->required()
                ->maxLength(150)
                ->hint('Escriba el título oficial del cargo.'),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Añadir Cargo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}