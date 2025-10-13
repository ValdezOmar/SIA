<?php

namespace App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Form;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
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
                TextColumn::make('razon_social')
                    ->label('Razón Social')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->badge()
                    ->color('success'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Vincular Empresa')
                    ->recordSelect(function ($select) {
                        return $select
                            ->searchable()
                            ->preload()
                            ->placeholder('Seleccione una empresa...');
                    }),
            ])
            ->actions([
                DetachAction::make()
                    ->label('Quitar'),
            ])
            ->emptyStateHeading('Sin empresas asociadas')
            ->emptyStateDescription('Puedes vincular una o más empresas a esta área.');
    }
}