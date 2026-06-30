<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\UnidadMedidaResource\Pages;
use App\Models\Inventario\UnidadMedida;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Schema;

class UnidadMedidaResource extends Resource
{
    protected static ?string $model = UnidadMedida::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Unidades de Medida';

    protected static ?string $modelLabel = 'Unidad de Medida';

    protected static ?string $pluralModelLabel = 'Unidades de Medida';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Unidad')
                    ->tabs([
                        
                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Unidad')
                                    ->icon('heroicon-o-scale')
                                    ->description('Información de la unidad de medida')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Ej: UND')
                                                    ->helperText('Código único de la unidad')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'codigo')),

                                                TextInput::make('nombre')
                                                    ->label('Nombre')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Unidad, Kilogramo, Litro')
                                                    ->helperText('Nombre completo de la unidad de medida')
                                                    ->columnSpan(1),

                                                TextInput::make('abreviatura')
                                                    ->label('Abreviatura')
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->placeholder('Ej: UND, KG, LT')
                                                    ->helperText('Abreviatura de la unidad de medida')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Unidad Activa')
                                                    ->default(true)
                                                    ->helperText('Desactive para inhabilitar temporalmente esta unidad')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'activo')),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: ESTADÍSTICAS ==========
                        Tabs\Tab::make('Estadísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen de la Unidad')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Forms\Components\Placeholder::make('estadisticas')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'Las estadísticas se mostrarán después de guardar la unidad.';
                                                }

                                                try {
                                                    $totalArticulos = $record->articulos()->count();
                                                    $articulosActivos = $record->articulos()->where('activo', true)->count();

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                                                <div class="text-sm text-blue-600 font-medium">Total de Artículos</div>
                                                                <div class="text-2xl font-bold text-blue-900">' . number_format($totalArticulos) . '</div>
                                                                <div class="text-xs text-blue-500 mt-1">Artículos con esta unidad</div>
                                                            </div>
                                                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                                                <div class="text-sm text-green-600 font-medium">Artículos Activos</div>
                                                                <div class="text-2xl font-bold text-green-900">' . number_format($articulosActivos) . '</div>
                                                                <div class="text-xs text-green-500 mt-1">Artículos disponibles con esta unidad</div>
                                                            </div>
                                                        </div>'
                                                    );
                                                } catch (\Exception $e) {
                                                    return '<div class="text-sm text-gray-500">No hay estadísticas disponibles.</div>';
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Código copiado')
                    ->toggleable()
                    ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'codigo')),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('abreviatura')
                    ->label('Abreviatura')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('articulos_count')
                    ->label('Artículos')
                    ->counts('articulos')
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable()
                    ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'activo')),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas')
                    ->placeholder('Todos')
                    ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'activo')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            if (Schema::hasColumn('alm_unidades_medida', 'codigo')) {
                                $newRecord->codigo = $record->codigo . '-COPY-' . time();
                            }
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Unidad duplicada exitosamente')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->color(fn ($record) => $record->activo ?? true ? 'warning' : 'success')
                        ->action(function ($record) {
                            if (Schema::hasColumn('alm_unidades_medida', 'activo')) {
                                $record->update(['activo' => !$record->activo]);
                            }
                            \Filament\Notifications\Notification::make()
                                ->title($record->activo ? 'Unidad activada' : 'Unidad desactivada')
                                ->success()
                                ->send();
                        })
                        ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'activo')),

                    Tables\Actions\DeleteAction::make(),
                ])
                ->tooltip('Acciones')
                ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('toggle_active_bulk')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->action(fn ($records) => $records->each->update(['activo' => !$records->first()->activo]))
                        ->requiresConfirmation()
                        ->modalHeading('Cambiar estado de unidades')
                        ->visible(fn () => Schema::hasColumn('alm_unidades_medida', 'activo')),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar unidad de medida...')
            ->emptyStateHeading('No hay unidades de medida registradas')
            ->emptyStateDescription('Crea tu primera unidad de medida para comenzar.')
            ->emptyStateIcon('heroicon-o-scale')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\ArticulosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUnidadMedidas::route('/'),
            'create' => Pages\CreateUnidadMedida::route('/create'),
            'edit' => Pages\EditUnidadMedida::route('/{record}/edit'),
        ];
    }
}