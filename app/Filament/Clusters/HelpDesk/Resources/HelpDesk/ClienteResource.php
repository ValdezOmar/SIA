<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\Pages;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\ClienteResource\RelationManagers;
use App\Models\HelpDesk\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClienteResource extends Resource
{
     protected static ?string $model = Cliente::class;
    protected static ?string $cluster = HelpDesk::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Parametros';
    protected static ?string $navigationLabel = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Cliente')
                ->schema([
                    Forms\Components\TextInput::make('razon_social')
                        ->label('Razón Social')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('ci_nit')
                        ->label('CI / NIT')
                        ->maxLength(50),

                    Forms\Components\TextInput::make('telefono')->tel(),
                    Forms\Components\TextInput::make('correo')->email(),
                    Forms\Components\TextInput::make('tipo_institucion')->label('Tipo de Institución'),
                    Forms\Components\TextInput::make('direccion')->columnSpanFull(),
                    Forms\Components\TextInput::make('ciudad'),
                    Forms\Components\Textarea::make('observaciones')->rows(3),
                    Forms\Components\Toggle::make('activo')->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('razon_social')->label('Cliente')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('ci_nit')->label('CI/NIT'),
            Tables\Columns\TextColumn::make('telefono'),
            Tables\Columns\TextColumn::make('correo'),
            Tables\Columns\IconColumn::make('activo')->boolean(),
            Tables\Columns\TextColumn::make('created_at')->label('Creado')->date(),
        ])
        ->filters([
            Tables\Filters\TernaryFilter::make('activo')->label('Activo'),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

   

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}