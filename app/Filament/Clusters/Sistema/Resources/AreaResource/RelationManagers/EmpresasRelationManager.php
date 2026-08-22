<?php

namespace App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class EmpresasRelationManager extends RelationManager
{
    protected static string $relationship = 'empresas';

    protected static ?string $title = 'Empresas asociadas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('razon_social')
                ->label('Razón Social')
                ->helperText('La empresa se administra desde su propio registro.')
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
                    ->tooltip('Asociar una empresa a esta área')
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
            ->emptyStateDescription('Vincule las empresas que utilizan esta área.');
    }
}
