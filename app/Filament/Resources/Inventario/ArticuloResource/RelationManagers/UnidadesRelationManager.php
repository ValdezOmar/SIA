<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\UnidadMedida;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UnidadesRelationManager extends RelationManager
{
    protected static string $relationship = 'unidades';

    protected static ?string $title = 'Unidades de Medida Alternas';

    protected static ?string $modelLabel = 'Unidad Alterna';

    protected static ?string $pluralModelLabel = 'Unidades Alternas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración de Unidad Alterna')
                    ->icon('heroicon-o-scale')
                    ->description('Define unidades de medida alternas para este artículo')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('unidad_medida_id')
                                    ->label('Unidad de Medida')
                                    ->options(fn () => UnidadMedida::pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione una unidad')
                                    ->helperText('Unidad de medida alternativa')
                                    ->columnSpan(1),

                                TextInput::make('factor_conversion')
                                    ->label('Factor de Conversión')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.000001)
                                    ->default(1)
                                    ->placeholder('1.00')
                                    ->helperText('Factor de conversión a la unidad base')
                                    ->columnSpan(1),
                            ]),

                        Grid::make(3)
                            ->schema([
                                Toggle::make('es_compra')
                                    ->label('Para Compras')
                                    ->default(false)
                                    ->helperText('Usar esta unidad en compras'),

                                Toggle::make('es_venta')
                                    ->label('Para Ventas')
                                    ->default(false)
                                    ->helperText('Usar esta unidad en ventas'),

                                Toggle::make('es_inventario')
                                    ->label('Para Inventario')
                                    ->default(false)
                                    ->helperText('Usar esta unidad en inventario'),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('unidadMedida')
            ->columns([
                TextColumn::make('unidadMedida.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('unidadMedida.nombre')
                    ->label('Unidad')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('unidadMedida.abreviatura')
                    ->label('Abreviatura')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('factor_conversion')
                    ->label('Factor de Conversión')
                    ->numeric(6)
                    ->sortable()
                    ->toggleable(),

                IconColumn::make('es_compra')
                    ->label('Compra')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                IconColumn::make('es_venta')
                    ->label('Venta')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                IconColumn::make('es_inventario')
                    ->label('Inventario')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('es_compra')
                    ->label('Para Compras')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No'),

                Tables\Filters\TernaryFilter::make('es_venta')
                    ->label('Para Ventas')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No'),

                Tables\Filters\TernaryFilter::make('es_inventario')
                    ->label('Para Inventario')
                    ->boolean()
                    ->trueLabel('Sí')
                    ->falseLabel('No'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Unidad Alterna')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Agregar Unidad Alterna al Artículo')
                    ->modalWidth('4xl'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->tooltip('Acciones')
                ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('unidadMedida.nombre')
            ->searchPlaceholder('Buscar unidades alternas...')
            ->emptyStateHeading('Sin unidades alternas')
            ->emptyStateDescription('Agrega unidades de medida alternas para este artículo')
            ->emptyStateIcon('heroicon-o-scale')
            ->poll('60s');
    }
}