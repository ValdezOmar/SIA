<?php

namespace App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;

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
                ->helperText('Use el nombre oficial que verá el personal.'),
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
                Tables\Actions\CreateAction::make()
                    ->label('Añadir cargo')
                    ->tooltip('Registrar un cargo para esta área'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar')->tooltip('Modificar este cargo'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->emptyStateHeading('Aún no hay cargos')
            ->emptyStateDescription('Añada los cargos que pertenecen a esta área.');
    }
}
