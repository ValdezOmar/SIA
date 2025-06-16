<?php

namespace App\Filament\Resources\Almacen;

use App\Filament\Resources\Almacen\InventarioResource\Pages;
use App\Models\Almacen\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;


class InventarioResource extends Resource
{
    protected static ?string $model = Inventario::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'Inventario';
    protected static ?string $pluralModelLabel = 'Listado del inventario actual';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $navigationGroup = 'Almacenes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos originales del sistema')
                    ->description('Información de inventario almacenada en el sistema')
                    ->schema([
                        TextInput::make('codigo')->label('Código del producto')->disabled(),
                        TextInput::make('descripcion')->label('Descripción')->disabled(),
                        TextInput::make('presentacion')->label('Presentación')->disabled(),
                        TextInput::make('unidad')->label('Unidad de medida')->disabled(),
                        TextInput::make('codigo_alterno')->label('Código alterno')->disabled(),
                        TextInput::make('cod_almacen')->label('Código de almacén')->disabled(),
                        TextInput::make('nombre_almacen')->label('Nombre del almacén')->disabled(),
                        TextInput::make('lote')->label('Lote')->disabled(),
                        DatePicker::make('fecha_ven')->label('Fecha de vencimiento')->disabled(),
                        TextInput::make('sn_qr')->label('Código QR / Serial')->disabled(),
                        TextInput::make('empresa')->label('Empresa')->disabled(),

                    ])
                    ->columns(3),

                Section::make('Datos corregidos')
                    ->description('Ingrese los datos correctos en caso de discrepancias')
                    ->schema([
                        TextInput::make('codigo_correcto')->label('Código correcto'),
                        TextInput::make('descripcion_correcto')->label('Descripción correcta'),
                        TextInput::make('presentacion_correcto')->label('Presentación correcta'),
                        TextInput::make('unidad_correcto')->label('Unidad de medida'),
                        TextInput::make('codigo_alterno_correcto')->label('Código alterno'),
                        TextInput::make('cod_almacen_correcto')->label('Código de almacén'),
                        TextInput::make('nombre_almacen_correcto')->label('Nombre del almacén'),
                        TextInput::make('lote_correcto')->label('Lote'),
                        BarcodeInput::make('sn_qr_correcto')
                            ->label('QR correcto')
                            ->icon('heroicon-o-qr-code'),
                        DatePicker::make('fecha_ven_correcto')->label('Fecha de vencimiento'),
                        TextInput::make('sn_qr_correcto')->label('Código QR / Serial'),
                        TextInput::make('empresa_correcto')->label('Empresa'),
                    ])
                    ->columns(3),

                Section::make('Conteo de inventario físico')
                    ->description('Información del conteo físico realizado en campo')
                    ->schema([
                        TextInput::make('saldo_contado')->label('Saldo contado')->numeric(),
                        TextInput::make('saldo_actual')->label('Saldo en sistema')->disabled(),
                    ])
                    ->columns(2),
                Forms\Components\Textarea::make('observacion')->label('Observaciones')->rows(3)->maxLength(255)->columnSpanFull(),

                Section::make('Datos adicionales del sistema')
                    ->description('Información técnica del sistema')
                    ->hidden()
                    ->schema([
                        DatePicker::make('fecha_conteo_inventario')->label('Fecha de conteo'),
                        Toggle::make('activo')->label('Activo'),
                        TextInput::make('usuario')->label('Usuario responsable'),
                    ])
                    ->columns(2),
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
                    ->label('Empresa'),
            ])
            ->filters([
                //
            ])
            ->actions([])
            ->bulkActions([])
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
            'index' => Pages\ListInventarios::route('/'),
            //'create' => Pages\CreateInventario::route('/create'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}