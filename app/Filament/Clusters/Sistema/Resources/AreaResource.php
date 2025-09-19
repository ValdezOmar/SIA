<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\AreaResource\Pages;
use App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers\CargosRelationManager;
use App\Models\Sistema\Area;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class AreaResource extends Resource implements HasShieldPermissions
{
     protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Áreas';
    protected static ?string $pluralLabel = 'Áreas';
    protected static ?string $cluster = Sistema::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Card::make()
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del Área')
                        ->placeholder('Ej. Recursos Humanos, Finanzas, TI')
                        ->required()
                        ->maxLength(150)
                        ->hint('Ingrese el nombre oficial del área.'),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre del Área')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cargos_count')
                    ->counts('cargos')
                    ->label('N° Cargos')
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
        //Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any', //Mostrar en menú
            'view', //Ver registro
            'create', //Crear Registro
            'update', //Actualizar registro            
            'delete' //Eliminar Registro
        ];
    }

    public static function getRelations(): array
    {
        return [
            CargosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit'   => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}