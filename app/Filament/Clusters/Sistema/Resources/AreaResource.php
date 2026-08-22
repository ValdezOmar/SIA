<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\AreaResource\Pages;
use App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers\CargosRelationManager;
use App\Filament\Clusters\Sistema\Resources\AreaResource\RelationManagers\EmpresasRelationManager;
use App\Models\Sistema\Area;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AreaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Area::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Áreas';

    protected static ?string $pluralLabel = 'Áreas';

    protected static ?string $modelLabel = 'Área';

    protected static ?string $pluralModelLabel = 'Áreas';

    protected static ?string $cluster = Sistema::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del área')
                ->description('Cree las áreas que organizan cargos, empresas y responsabilidades.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del Área')
                        ->placeholder('Ej. Recursos Humanos, Finanzas, TI')
                        ->required()
                        ->maxLength(150)
                        ->helperText('Use un nombre corto y reconocible. Ejemplo: Recursos Humanos.'),
                ])
                ->columns(1),
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
                Tables\Actions\EditAction::make()->label('Editar')->tooltip('Editar área'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('nombre')
            ->emptyStateHeading('Aún no hay áreas')
            ->emptyStateDescription('Cree un área antes de registrar cargos o relacionarla con una empresa.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    // Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any', // Mostrar en menú
            'view', // Ver registro
            'create', // Crear Registro
            'update', // Actualizar registro
            'delete', // Eliminar Registro
        ];
    }

    public static function getRelations(): array
    {
        return [
            CargosRelationManager::class,
            EmpresasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAreas::route('/'),
            'create' => Pages\CreateArea::route('/create'),
            'edit' => Pages\EditArea::route('/{record}/edit'),
        ];
    }
}
