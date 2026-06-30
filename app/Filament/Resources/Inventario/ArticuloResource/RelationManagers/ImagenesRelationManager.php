<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImagenesRelationManager extends RelationManager
{
    protected static string $relationship = 'imagenes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('archivo')
                    ->required()
                    ->maxLength(255),
                FileUpload::make('archivo')
                    ->image()
                    ->directory('articulos'),

                TextInput::make('orden')
                    ->numeric(),

                Toggle::make('principal'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('archivo')
            ->columns([
                Tables\Columns\TextColumn::make('archivo'),
                ImageColumn::make('archivo'),

                TextColumn::make('orden'),

                IconColumn::make('principal')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
