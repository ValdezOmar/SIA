<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ImagenesRelationManager extends RelationManager
{
    protected static string $relationship = 'imagenes';

    protected static ?string $title = 'Imágenes del artículo';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                FileUpload::make('archivo')
                    ->label('Imagen')
                    ->image()
                    ->directory('articulos')
                    ->required()
                    ->helperText('Suba una imagen clara del artículo.'),

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
                ImageColumn::make('archivo')->label('Imagen'),
                Tables\Columns\TextColumn::make('archivo')->label('Archivo')->limit(40),

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
