<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\CargoResource\Pages;
use App\Filament\Clusters\Sistema\Resources\CargoResource\RelationManagers;
use App\Models\Sistema\Cargo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class CargoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Cargo::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationLabel = 'Cargos';
    protected static ?string $cluster = Sistema::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Cargo')
                ->description('Defina el nombre y el área a la que pertenece este cargo.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del Cargo')
                        ->placeholder('Ej. Jefe de Ventas, Desarrollador Backend')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\Select::make('area_id')
                        ->label('Área')
                        ->relationship('area', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->hint('Seleccione el área donde se desempeña este cargo.'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Cargo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('area.nombre')
                    ->label('Área')
                    ->sortable()
                    ->badge()
                    ->color('success'),
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
            'view_any', //Mostrar en menúF
            'view', //Ver registro
            'create', //Crear Registro
            'update', //Actualizar registro            
            'delete' //Eliminar Registro
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCargos::route('/'),
            'create' => Pages\CreateCargo::route('/create'),
            'edit'   => Pages\EditCargo::route('/{record}/edit'),
        ];
    }
}