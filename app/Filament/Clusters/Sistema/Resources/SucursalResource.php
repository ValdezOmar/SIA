<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages;
use App\Models\Sistema\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class SucursalResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Sucursal::class;
    protected static ?string $cluster = Sistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';    
    protected static ?string $modelLabel = 'Sucursal';
    protected static ?string $pluralModelLabel = 'Sucursales';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->description('Datos principales de la sucursal.')
                    ->schema([
                        Forms\Components\Select::make('empresa_id')
                            ->label('Empresa')
                            ->relationship('empresa', 'razon_social')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hint('Selecciona la empresa a la que pertenece esta sucursal.'),

                        Forms\Components\TextInput::make('nombre')
                            ->label('Nombre de la sucursal')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Ej: Sucursal Central La Paz')
                            ->hint('Escribe un nombre claro y fácil de identificar.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Ubicación y Contacto')
                    ->description('Dirección y datos de contacto de la sucursal.')
                    ->schema([
                        Forms\Components\Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2)
                            ->placeholder('Ej: Av. Mariscal Santa Cruz #123')
                            ->hint('Especifica la dirección completa de la sucursal.'),

                        Forms\Components\TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->maxLength(150)
                            ->placeholder('Ej: La Paz')
                            ->hint('Ciudad donde se encuentra la sucursal.'),

                        Forms\Components\TextInput::make('pais')
                            ->label('País')
                            ->maxLength(100)
                            ->default('Bolivia')
                            ->placeholder('Ej: Bolivia')
                            ->hint('País de ubicación.'),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->maxLength(50)
                            ->placeholder('Ej: (2) 2456789')
                            ->hint('Número de contacto fijo de la sucursal.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuración')
                    ->description('Estado de la sucursal en el sistema.')
                    ->schema([
                        Forms\Components\Toggle::make('activo')
                            ->label('Sucursal activa')
                            ->default(true)
                            ->hint('Desactiva si la sucursal ya no está en funcionamiento.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('empresa.razon_social')
                    ->label('Empresa')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('nombre')
                    ->label('Nombre de sucursal')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono'),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activa')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas')
                    ->placeholder('Todas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),

                Tables\Actions\EditAction::make()
                    ->label('Editar'),

                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas'),
                ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSucursals::route('/'),
            'create' => Pages\CreateSucursal::route('/create'),
            'edit' => Pages\EditSucursal::route('/{record}/edit'),
        ];
    }
}