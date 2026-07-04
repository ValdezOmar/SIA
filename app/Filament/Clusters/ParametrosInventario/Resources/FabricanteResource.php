<?php

namespace App\Filament\Clusters\ParametrosInventario\Resources;

use App\Filament\Clusters\ParametrosInventario;
use App\Filament\Clusters\ParametrosInventario\Resources\FabricanteResource\Pages;

use App\Models\Inventario\Fabricante;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
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

class FabricanteResource extends Resource
{
    protected static ?string $model = Fabricante::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $cluster = ParametrosInventario::class;

    protected static ?string $navigationLabel = 'Fabricantes';

    protected static ?string $modelLabel = 'Fabricante';

    protected static ?string $pluralModelLabel = 'Fabricantes';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Fabricante')
                    ->tabs([
                        
                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos Básicos')
                                    ->icon('heroicon-o-identification')
                                    ->description('Información principal del fabricante')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Ej: FAB-001')
                                                    ->helperText('Código único del fabricante')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre')
                                                    ->label('Nombre')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Samsung Electronics')
                                                    ->helperText('Nombre completo del fabricante')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('nombre_comercial')
                                                    ->label('Nombre Comercial')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Samsung')
                                                    ->helperText('Nombre comercial o marca del fabricante')
                                                    ->columnSpan(1),

                                                TextInput::make('sitio_web')
                                                    ->label('Sitio Web')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: https://www.samsung.com')
                                                    ->helperText('Sitio web oficial del fabricante')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Fabricante Activo')
                                                    ->default(true)
                                                    ->helperText('Desactive para inhabilitar temporalmente este fabricante')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: CONTACTO ==========
                        Tabs\Tab::make('Contacto')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Datos de Contacto')
                                    ->icon('heroicon-o-phone')
                                    ->description('Información de contacto del fabricante')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('telefono')
                                                    ->label('Teléfono')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: (591) 2-1234567')
                                                    ->helperText('Teléfono de contacto del fabricante')
                                                    ->columnSpan(1),

                                                TextInput::make('correo')
                                                    ->label('Correo Electrónico')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: contacto@samsung.com')
                                                    ->helperText('Correo electrónico de contacto')
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('direccion')
                                            ->label('Dirección')
                                            ->rows(3)
                                            ->placeholder('Ej: Av. Principal #123, Zona Industrial')
                                            ->helperText('Dirección física del fabricante')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 3: INFORMACIÓN ADICIONAL ==========
                        Tabs\Tab::make('Información Adicional')
                            ->icon('heroicon-o-clipboard-document')
                            ->schema([
                                Section::make('Observaciones')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->description('Notas adicionales sobre el fabricante')
                                    ->schema([
                                        Textarea::make('observaciones')
                                            ->label('Observaciones')
                                            ->rows(6)
                                            ->placeholder('Notas adicionales sobre el fabricante...')
                                            ->helperText('Información adicional relevante sobre el fabricante')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ========== TAB 4: ESTADÍSTICAS ==========
                        Tabs\Tab::make('Estadísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen del Fabricante')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Forms\Components\Placeholder::make('estadisticas')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'Las estadísticas se mostrarán después de guardar el fabricante.';
                                                }

                                                try {
                                                    $totalArticulos = $record->articulos()->count();
                                                    $articulosActivos = $record->articulos()->where('activo', true)->count();

                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                                                <div class="text-sm text-blue-600 font-medium">Total de Artículos</div>
                                                                <div class="text-2xl font-bold text-blue-900">' . number_format($totalArticulos) . '</div>
                                                                <div class="text-xs text-blue-500 mt-1">Artículos de este fabricante</div>
                                                            </div>
                                                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                                                <div class="text-sm text-green-600 font-medium">Artículos Activos</div>
                                                                <div class="text-2xl font-bold text-green-900">' . number_format($articulosActivos) . '</div>
                                                                <div class="text-xs text-green-500 mt-1">Artículos disponibles de este fabricante</div>
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

                TextColumn::make('nombre_comercial')
                    ->label('Nombre Comercial')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('telefono')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('correo')
                    ->label('Correo')
                    ->searchable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('articulos_count')
                    ->label('Artículos')
                    ->counts('articulos')
                    ->badge()
                    ->color('info')
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

                Tables\Filters\Filter::make('nombre')
                    ->label('Buscar por nombre')
                    ->form([
                        TextInput::make('nombre')
                            ->label('Nombre')
                            ->placeholder('Buscar fabricante...'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['nombre'],
                            fn ($query, $nombre) => $query->where('nombre', 'like', "%{$nombre}%")
                                ->orWhere('nombre_comercial', 'like', "%{$nombre}%")
                        );
                    }),
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
                                ->title('Fabricante duplicado exitosamente')
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
                                ->title($record->activo ? 'Fabricante activado' : 'Fabricante desactivado')
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
                        ->modalHeading('Cambiar estado de fabricantes')
                        ->modalSubheading('¿Deseas cambiar el estado de los fabricantes seleccionados?'),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar fabricante...')
            ->emptyStateHeading('No hay fabricantes registrados')
            ->emptyStateDescription('Crea tu primer fabricante para comenzar a gestionar tus artículos.')
            ->emptyStateIcon('heroicon-o-building-office-2')
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
            'index' => Pages\ListFabricantes::route('/'),
            'create' => Pages\CreateFabricante::route('/create'),
            'edit' => Pages\EditFabricante::route('/{record}/edit'),
        ];
    }
}