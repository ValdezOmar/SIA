<?php

namespace App\Filament\Resources\Inventario\ArticuloResource\RelationManagers;

use App\Models\Inventario\CapaCosto;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CapasCostosRelationManager extends RelationManager
{
    protected static string $relationship = 'capasCostos';

    protected static ?string $title = '📦 Capas de Costo (FIFO)';

    protected static ?string $modelLabel = 'Capa';

    protected static ?string $pluralModelLabel = 'Capas de Costo';

    private static function formatearMonto($monto): string
    {
        return 'Bs ' . number_format($monto ?? 0, 2);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Capa')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('cantidad_original')
                                    ->label('Cantidad Original')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0.01)
                                    ->step(1)
                                    ->placeholder('0.00')
                                    ->disabled(),

                                TextInput::make('cantidad_disponible')
                                    ->label('Cantidad Disponible')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(1)
                                    ->placeholder('0.00')
                                    ->disabled(),

                                TextInput::make('costo_unitario')
                                    ->label('Costo Unitario')
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->step(0.000001)
                                    ->placeholder('0.00')
                                    ->prefix('$')
                                    ->disabled(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('costo_total')
                                    ->label('Costo Total')
                                    ->content(function ($get) {
                                        $cantidad = floatval($get('cantidad_disponible') ?? 0);
                                        $costo = floatval($get('costo_unitario') ?? 0);
                                        return self::formatearMonto($cantidad * $costo);
                                    }),

                                Placeholder::make('fecha_creacion')
                                    ->label('Fecha de Creación')
                                    ->content(function ($record) {
                                        return $record?->created_at?->format('d/m/Y H:i') ?? 'N/A';
                                    }),
                            ]),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->width('60px'),

                TextColumn::make('cantidad_original')
                    ->label('Original')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('cantidad_disponible')
                    ->label('Disponible')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->color(fn($state) => $state <= 0 ? 'danger' : 'success')
                    ->weight('bold'),

                TextColumn::make('costo_unitario')
                    ->label('Costo Unit.')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->prefix('$'),

                TextColumn::make('costo_total')
                    ->label('Costo Total')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->getStateUsing(fn($record) => $record->cantidad_disponible * $record->costo_unitario)
                    ->prefix('$'),

                BadgeColumn::make('activo')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => $state ? '✅ Activa' : '❌ Inactiva')
                    ->colors([
                        'success' => true,
                        'danger' => false,
                    ])
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('activo')
                    ->label('Capas Activas')
                    ->query(fn($query) => $query->where('activo', true)->where('cantidad_disponible', '>', 0)),

                Filter::make('inactivo')
                    ->label('Capas Inactivas')
                    ->query(fn($query) => $query->where('activo', false)->orWhere('cantidad_disponible', '<=', 0)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->modalWidth('3xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar capas...')
            ->emptyStateHeading('No hay capas de costo registradas')
            ->emptyStateDescription('Las capas se crean automáticamente con las compras.')
            ->emptyStateIcon('heroicon-o-document-text')
            ->poll('60s');
    }
}