<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Clusters\ParametrosInventario\Resources\GrupoArticuloResource\Pages;

use App\Models\Inventario\GrupoArticulo;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
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

class GrupoArticuloResource extends Resource
{
    protected static ?string $model = GrupoArticulo::class;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Grupos de Artículos';

    protected static ?string $modelLabel = 'Grupo de Artículo';

    protected static ?string $pluralModelLabel = 'Grupos de Artículos';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Grupo')
                    ->tabs([
                        
                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos del Grupo')
                                    ->icon('heroicon-o-folder')
                                    ->description('Información del grupo de artículos')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Ej: GRP-001')
                                                    ->helperText('Código único del grupo')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre')
                                                    ->label('Nombre del Grupo')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Electrónicos')
                                                    ->helperText('Nombre descriptivo del grupo')
                                                    ->columnSpan(1),
                                            ]),

                                        Select::make('grupo_padre_id')
                                            ->label('Grupo Padre')
                                            ->options(fn () => GrupoArticulo::where('activo', true)
                                                ->whereNull('grupo_padre_id')
                                                ->pluck('nombre', 'id')
                                                ->toArray()
                                            )
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Seleccione un grupo padre (opcional)')
                                            ->helperText('Grupo al que pertenece este subgrupo')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Grupo Activo')
                                                    ->default(true)
                                                    ->helperText('Desactive para inhabilitar temporalmente este grupo')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: ESTRUCTURA ==========
                        Tabs\Tab::make('Estructura')
                            //->icon('heroicon-o-sitemap')
                            ->schema([
                                Section::make('Jerarquía del Grupo')
                                    //->icon('heroicon-o-sitemap')
                                    ->description('Visualización de la estructura jerárquica')
                                    ->schema([
                                        Forms\Components\Placeholder::make('estructura_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'La estructura se mostrará después de guardar el grupo.';
                                                }

                                                try {
                                                    $subgrupos = $record->subgrupos()->where('activo', true)->get();
                                                    
                                                    if ($subgrupos->isEmpty()) {
                                                        return '<div class="text-sm text-gray-500">Este grupo no tiene subgrupos.</div>';
                                                    }

                                                    $html = '<div class="bg-gray-50 p-4 rounded-lg border border-gray-200">';
                                                    $html .= '<p class="text-sm font-medium text-gray-700 mb-2">Subgrupos de ' . $record->nombre . ':</p>';
                                                    $html .= '<ul class="list-disc pl-5 space-y-1">';
                                                    
                                                    foreach ($subgrupos as $subgrupo) {
                                                        $html .= '<li class="text-sm text-gray-600">' . $subgrupo->codigo . ' - ' . $subgrupo->nombre;
                                                        $html .= ' <span class="text-xs text-gray-400">(' . $subgrupo->articulos()->count() . ' artículos)</span>';
                                                        $html .= '</li>';
                                                    }
                                                    
                                                    $html .= '</ul>';
                                                    $html .= '</div>';
                                                    
                                                    return new \Illuminate\Support\HtmlString($html);
                                                } catch (\Exception $e) {
                                                    return '<div class="text-sm text-gray-500">No hay información de estructura disponible.</div>';
                                                }
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 3: ESTADÍSTICAS ==========
                        Tabs\Tab::make('Estadísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen del Grupo')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Forms\Components\Placeholder::make('estadisticas')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'Las estadísticas se mostrarán después de guardar el grupo.';
                                                }

                                                try {
                                                    $totalArticulos = $record->articulos()->count();
                                                    $articulosActivos = $record->articulos()->where('activo', true)->count();
                                                    $totalSubgrupos = $record->subgrupos()->count();
                                                    $subgruposActivos = $record->subgrupos()->where('activo', true)->count();

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                                                <div class="text-sm text-blue-600 font-medium">Total de Artículos</div>
                                                                <div class="text-2xl font-bold text-blue-900">' . number_format($totalArticulos) . '</div>
                                                                <div class="text-xs text-blue-500 mt-1">Artículos en este grupo</div>
                                                            </div>
                                                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                                                <div class="text-sm text-green-600 font-medium">Artículos Activos</div>
                                                                <div class="text-2xl font-bold text-green-900">' . number_format($articulosActivos) . '</div>
                                                                <div class="text-xs text-green-500 mt-1">Artículos disponibles</div>
                                                            </div>
                                                            <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                                                                <div class="text-sm text-yellow-600 font-medium">Total de Subgrupos</div>
                                                                <div class="text-2xl font-bold text-yellow-900">' . number_format($totalSubgrupos) . '</div>
                                                                <div class="text-xs text-yellow-500 mt-1">Subgrupos en este grupo</div>
                                                            </div>
                                                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                                                <div class="text-sm text-purple-600 font-medium">Subgrupos Activos</div>
                                                                <div class="text-2xl font-bold text-purple-900">' . number_format($subgruposActivos) . '</div>
                                                                <div class="text-xs text-purple-500 mt-1">Subgrupos disponibles</div>
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
                    ->toggleable(),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('grupoPadre.nombre')
                    ->label('Grupo Padre')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->placeholder('Ninguno'),

                TextColumn::make('subgrupos_count')
                    ->label('Subgrupos')
                    ->counts('subgrupos')
                    ->badge()
                    ->color('warning')
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
                    ->toggleable(),

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
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->placeholder('Todos'),

                Tables\Filters\SelectFilter::make('grupo_padre_id')
                    ->label('Grupo Padre')
                    ->options(fn () => GrupoArticulo::whereNull('grupo_padre_id')
                        ->pluck('nombre', 'id')
                        ->toArray()
                    )
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('5xl'),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('info')
                        ->action(function ($record) {
                            $newRecord = $record->replicate();
                            $newRecord->codigo = $record->codigo . '-COPY-' . time();
                            $newRecord->created_at = now();
                            $newRecord->updated_at = now();
                            $newRecord->save();

                            \Filament\Notifications\Notification::make()
                                ->title('Grupo duplicado exitosamente')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->color(fn ($record) => $record->activo ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update(['activo' => !$record->activo]);
                            \Filament\Notifications\Notification::make()
                                ->title($record->activo ? 'Grupo activado' : 'Grupo desactivado')
                                ->success()
                                ->send();
                        }),

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
                        ->modalHeading('Cambiar estado de grupos'),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar grupo...')
            ->emptyStateHeading('No hay grupos de artículos registrados')
            ->emptyStateDescription('Crea tu primer grupo para organizar tus artículos.')
            ->emptyStateIcon('heroicon-o-folder')
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
            'index' => Pages\ListGrupoArticulos::route('/'),
            'create' => Pages\CreateGrupoArticulo::route('/create'),
            'edit' => Pages\EditGrupoArticulo::route('/{record}/edit'),
        ];
    }
}