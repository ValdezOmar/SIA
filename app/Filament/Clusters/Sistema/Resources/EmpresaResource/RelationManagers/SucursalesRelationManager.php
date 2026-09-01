<?php

namespace App\Filament\Clusters\Sistema\Resources\EmpresaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SucursalesRelationManager extends RelationManager
{
    protected static string $relationship = 'sucursales';

    protected static ?string $title = 'Sucursales';

    protected static ?string $modelLabel = 'sucursal';

    protected static ?string $pluralModelLabel = 'sucursales';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la sucursal')
                ->description('La sucursal quedará asociada automáticamente a esta empresa.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre de la sucursal')
                        ->placeholder('Ej. Oficina Central La Paz')
                        ->helperText('Use la ciudad o una referencia fácil de reconocer.')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('ciudad')
                        ->label('Ciudad')
                        ->placeholder('Ej. La Paz')
                        ->maxLength(150),

                    Forms\Components\Textarea::make('direccion')
                        ->label('Dirección')
                        ->placeholder('Calle, número, zona y referencias')
                        ->helperText('Incluya referencias útiles para empleados y entregas.')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('pais')
                        ->label('País')
                        ->default('Bolivia')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('telefono')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(50),

                    Forms\Components\Toggle::make('activo')
                        ->label('Sucursal activa')
                        ->helperText('Desactívela cuando deje de operar; su historial se conservará.')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Sucursal')
                    ->description(fn ($record): string => $record->direccion ?: 'Sin dirección registrada')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->placeholder('Sin ciudad')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->placeholder('Sin teléfono'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar sucursal')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('nombre')
            ->emptyStateHeading('Esta empresa aún no tiene sucursales')
            ->emptyStateDescription('Agregue la oficina principal y las demás ubicaciones donde opera la empresa.')
            ->emptyStateIcon('heroicon-o-building-storefront');
    }
}
