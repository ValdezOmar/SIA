<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\RelationManagers;
use App\Models\Sistema\Parametro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ParametroResource extends Resource
{
    protected static ?string $model = Parametro::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $cluster = Sistema::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('logo_path')
                    ->label('Logo')
                    ->image()
                    ->directory('parametros')
                    ->imageEditor(),

                Forms\Components\FileUpload::make('favicon_path')
                    ->label('Favicon')
                    ->image()
                    ->directory('parametros'),

                Forms\Components\FileUpload::make('fondo_path')
                    ->label('Fondo de Login')
                    ->image()
                    ->directory('parametros'),

                Forms\Components\ColorPicker::make('color_principal')
                    ->label('Color Principal')
                    ->required(),

                Forms\Components\ColorPicker::make('color_secundario')
                    ->label('Color Secundario')
                    ->required(),

                Forms\Components\Section::make('Integración con Google')
                    ->schema([
                        Forms\Components\Toggle::make('google_activo')
                            ->label('Activar Google Login'),

                        Forms\Components\TextInput::make('google_client_id')
                            ->label('Client ID')
                            ->visible(fn($get) => $get('google_activo')),

                        Forms\Components\TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->visible(fn($get) => $get('google_activo')),

                        Forms\Components\TextInput::make('google_redirect_uri')
                            ->label('Redirect URI')
                            ->visible(fn($get) => $get('google_activo')),
                    ])
                    ->collapsible(),

                Forms\Components\Select::make('timezone')
                    ->label('Zona Horaria')
                    ->options([
                        'America/La_Paz' => 'America/La_Paz',
                        'America/Lima' => 'America/Lima',
                        'America/Bogota' => 'America/Bogotá',
                        'America/Santiago' => 'America/Santiago',
                    ])
                    ->searchable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Como es registro único, mostramos solo 1 fila
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo_path')->label('Logo'),
                Tables\Columns\ColorColumn::make('color_principal')->label('Color Principal'),
                Tables\Columns\ColorColumn::make('color_secundario')->label('Color Secundario'),
                Tables\Columns\IconColumn::make('google_activo')->boolean()->label('Google Login'),
                Tables\Columns\TextColumn::make('timezone')->label('Zona Horaria'),
            ])
            ->defaultSort('id', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParametros::route('/'),
            
            'edit' => Pages\EditParametro::route('/{record}/edit'),
        ];
    }
}