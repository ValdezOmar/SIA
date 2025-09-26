<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\Pages;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\RelationManagers\EmpresaAreasRelationManager;
use App\Models\Sistema\Empresa;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class EmpresaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Empresa::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $pluralModelLabel = 'Configuración de empresa';
    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $cluster = Sistema::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identificación')
                    ->schema([
                        Forms\Components\TextInput::make('razon_social')
                            ->label('Razón Social')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nombre_comercial')
                            ->label('Nombre Comercial')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('nro_matricula')
                            ->label('Nro. Matrícula')
                            ->maxLength(50),
                        Forms\Components\Select::make('areas')
                            ->label('Áreas de la empresa')
                            ->multiple()
                            ->relationship('areas', 'nombre')
                            ->searchable()
                            ->preload()
                            ->hint('Seleccione todas las áreas que pertenecen a esta sociedad.'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Datos de Contacto')
                    ->schema([
                        Forms\Components\Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2),

                        Forms\Components\TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->maxLength(150),

                        Forms\Components\TextInput::make('pais')
                            ->label('País')
                            ->default('Bolivia')
                            ->maxLength(100),

                        Forms\Components\TextInput::make('telefono')
                            ->label('Teléfono')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('celular')
                            ->label('Celular')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(150),

                        Forms\Components\TextInput::make('sitio_web')
                            ->label('Sitio Web')
                            ->url()
                            ->maxLength(150),
                        Forms\Components\TextInput::make('seguro_medico')
                            ->label('Caja de Salud')                            
                            ->hint('Caja de salud asignada a la empresa.')
                            ->hintIcon('heroicon-o-heart')
                    ])
                    ->columns(2),

                Forms\Components\Toggle::make('empresa_activo')
                    ->label('Empresa Activa')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('razon_social')
                    ->label('Razón Social')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nombre_comercial')
                    ->label('Nombre Comercial')
                    ->searchable(),

                Tables\Columns\TextColumn::make('nit')
                    ->label('NIT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->sortable(),

                Tables\Columns\IconColumn::make('empresa_activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('empresa_activo')
                    ->label('Activa'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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

    public static function getRelations(): array
    {
        return [
            //EmpresaAreasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpresas::route('/'),
            'create' => Pages\CreateEmpresa::route('/create'),
            'edit' => Pages\EditEmpresa::route('/{record}/edit'),
        ];
    }
}