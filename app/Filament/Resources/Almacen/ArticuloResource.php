<?php

namespace App\Filament\Resources\Almacen;

use App\Filament\Resources\Almacen\ArticuloResource\Pages;
use App\Models\Almacen\Articulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulo::class;
    protected static ?string $modelLabel = 'Disponibilidad de Stock'; //Seccion para configurar el nombre en Filament-Shield

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $pluralModelLabel = 'Disponibilidad de Stock';
    protected static ?string $navigationLabel = 'Disponibilidad de Stock';
    protected static ?string $navigationGroup = 'Almacenes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                TextColumn::make('codigo')
                    ->label('Item Almacén')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div>
                            <strong>{$record->descripcion}</strong><br>
                            <small>Codigo: {$record->codigo}<br>Cod. Alterno: {$record->codigo_alterno}</small>
                        </div>
                    ")
                    ->searchable(['descripcion', 'codigo', 'codigo_alterno']),

                TextColumn::make('presentacion')
                    ->label('Presentación')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div>
                            <strong>{$record->presentacion}</strong><br>
                            <small>Unidad: {$record->unidad}</small>
                        </div>
                    ")
                    ->searchable(['presentacion', 'unidad']),

                TextColumn::make('lote')
                    ->label('Lote')
                    ->sortable(),

                TextColumn::make('fecha_ven')
                    ->label('Vencimiento')
                    ->html()
                    ->getStateUsing(fn($record) => $record->fecha_ven ? \Carbon\Carbon::parse($record->fecha_ven)->format('d/m/Y') : 'Sin registro')
                    ->sortable(),

                TextColumn::make('saldo_actual')
                    ->label('Saldo Actual')
                    ->numeric()
                    ->sortable(),                          

                TextColumn::make('nombre_almacen')
                    ->label('Ubicacion en Almacén')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div>
                            <strong>{$record->nombre_almacen}</strong><br>
                            <small>Cod. Almacén: {$record->cod_almacen}</small>
                        </div>
                    ")
                    ->searchable(['nombre_almacen', 'cod_almacen']),

                TextColumn::make('empresa')
                    ->label('Empresa')
                    ->sortable(),


            ])
            ->filters([
            ])
            ->actions([               
            ])
            ->bulkActions([              
            ])
            ->paginated([10, 25, 50]);
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
            'index' => Pages\ListArticulos::route('/'),
        ];
    }
}