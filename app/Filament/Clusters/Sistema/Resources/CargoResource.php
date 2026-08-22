<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\CargoResource\Pages;
use App\Models\Sistema\Cargo;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CargoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Cargo::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = 'Cargos';

    protected static ?string $modelLabel = 'Cargo';

    protected static ?string $pluralModelLabel = 'Cargos';

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
                        ->maxLength(150)
                        ->helperText('Use el nombre del puesto, no el de una persona.'),

                    Forms\Components\Select::make('area_id')
                        ->label('Área')
                        ->relationship('area', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('El área ayuda a organizar la estructura y los permisos.'),
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
            ->filters([
                Tables\Filters\SelectFilter::make('area_id')->label('Área')->relationship('area', 'nombre')->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar')->tooltip('Editar cargo'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('nombre')
            ->emptyStateHeading('Aún no hay cargos')
            ->emptyStateDescription('Cree un cargo y asígnelo a un área para completar la estructura organizacional.')
            ->emptyStateIcon('heroicon-o-briefcase');
    }

    // Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any', // Mostrar en menúF
            'view', // Ver registro
            'create', // Crear Registro
            'update', // Actualizar registro
            'delete', // Eliminar Registro
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCargos::route('/'),
            'create' => Pages\CreateCargo::route('/create'),
            'edit' => Pages\EditCargo::route('/{record}/edit'),
        ];
    }
}
