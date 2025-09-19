<?php

namespace App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Tables\Table;

class EmpresasRelationManager extends RelationManager
{
    protected static string $relationship = 'empresas'; // relación en el modelo Área
    protected static ?string $title = 'Empresas asociadas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('razon_social')
                ->label('Razón Social')
                ->disabled(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('razon_social')
            ->columns([
                Tables\Columns\TextColumn::make('razon_social')
                    ->label('Razón Social')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->badge()
                    ->color('success'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Vincular Empresa')
                    ->preloadRecordSelect()
                    ->recordSelectSearchable(),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Quitar'),
            ]);
    }
}