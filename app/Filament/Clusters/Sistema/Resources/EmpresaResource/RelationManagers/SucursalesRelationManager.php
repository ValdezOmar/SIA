<?php

namespace App\Filament\Clusters\Sistema\Resources\EmpresaResource\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SucursalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sucursales';

    protected static ?string $title = 'Sucursales';

    protected static ?string $modelLabel = 'sucursal';

    protected static ?string $pluralModelLabel = 'sucursales';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Datos de la sucursal')
                ->icon('heroicon-o-building-storefront')
                ->description('La sucursal quedará asociada automáticamente a esta empresa.')
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre de la sucursal')
                        ->placeholder('Ej. Oficina Central La Paz')
                        ->helperText('Use la ciudad o una referencia fácil de reconocer.')
                        ->required()
                        ->maxLength(150),

                    TextInput::make('ciudad')
                        ->label('Ciudad')
                        ->placeholder('Ej. La Paz')
                        ->maxLength(150),

                    Textarea::make('direccion')
                        ->label('Dirección')
                        ->placeholder('Calle, número, zona y referencias')
                        ->helperText('Incluya referencias útiles para empleados y entregas.')
                        ->rows(2)
                        ->columnSpanFull(),

                    TextInput::make('pais')
                        ->label('País')
                        ->default('Bolivia')
                        ->maxLength(100),

                    TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(50),

                    Toggle::make('activo')
                        ->label('Sucursal activa')
                        ->helperText('Desactívela cuando deje de operar; su historial se conservará.')
                        ->default(true),
                ])
                ->columns(['default' => 1, 'lg' => 2]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->label('Sucursal')
                    ->description(fn ($record): string => $record->direccion ?: 'Sin dirección registrada')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->placeholder('Sin ciudad')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('Sin teléfono'),

                IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar sucursal')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Nueva sucursal')
                    ->modalWidth('3xl')
                    ->modalSubmitActionLabel('Guardar sucursal'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->modalHeading('Editar sucursal')
                    ->modalWidth('3xl')
                    ->modalSubmitActionLabel('Guardar cambios'),
                DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('nombre')
            ->emptyStateHeading('Esta empresa aún no tiene sucursales')
            ->emptyStateDescription('Agregue la oficina principal y las demás ubicaciones donde opera la empresa.')
            ->emptyStateIcon('heroicon-o-building-storefront');
    }
}
