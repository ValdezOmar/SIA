<?php

namespace App\Filament\Resources\Compras;

use App\Filament\Resources\Compras\ProveedorResource\Pages;
use App\Models\Compras\Proveedor;
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

class ProveedorResource extends Resource
{
    protected static ?string $model = Proveedor::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Proveedores';

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Proveedor')
                    ->tabs([
                        
                        // ========== TAB 1: INFORMACIÓN GENERAL ==========
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos Básicos')
                                    ->icon('heroicon-o-identification')
                                    ->description('Información principal del proveedor')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('Ej: PROV-001')
                                                    ->helperText('Código único para identificar al proveedor')
                                                    ->columnSpan(1),

                                                TextInput::make('nombre')
                                                    ->label('Nombre / Razón Social')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Distribuidora ABC S.A.')
                                                    ->helperText('Nombre completo o razón social del proveedor')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('nit')
                                                    ->label('NIT / RUC')
                                                    ->maxLength(50)
                                                    ->placeholder('Ej: 123456789')
                                                    ->helperText('Número de identificación tributaria')
                                                    ->columnSpan(1),

                                                TextInput::make('telefono')
                                                    ->label('Teléfono')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: (591) 2-1234567')
                                                    ->helperText('Teléfono de contacto')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('correo')
                                                    ->label('Correo Electrónico')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: contacto@proveedor.com')
                                                    ->helperText('Correo electrónico de contacto')
                                                    ->columnSpan(1),

                                                Textarea::make('direccion')
                                                    ->label('Dirección')
                                                    ->rows(3)
                                                    ->placeholder('Ej: Av. Principal #123, Zona Central')
                                                    ->helperText('Dirección física del proveedor')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),

                                Section::make('Estado')
                                    ->icon('heroicon-o-check-circle')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Proveedor Activo')
                                                    ->default(true)
                                                    ->helperText('Desactive para inhabilitar temporalmente este proveedor')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 2: INFORMACIÓN DE CONTACTO ==========
                        Tabs\Tab::make('Contacto')
                            ->icon('heroicon-o-phone')
                            ->schema([
                                Section::make('Datos de Contacto')
                                    ->icon('heroicon-o-phone')
                                    ->description('Información adicional de contacto')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('contacto_nombre')
                                                    ->label('Persona de Contacto')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Juan Pérez')
                                                    ->helperText('Nombre de la persona de contacto principal')
                                                    ->columnSpan(1),

                                                TextInput::make('contacto_cargo')
                                                    ->label('Cargo / Puesto')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: Gerente de Ventas')
                                                    ->helperText('Cargo de la persona de contacto')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('contacto_telefono')
                                                    ->label('Teléfono de Contacto')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: (591) 7-1234567')
                                                    ->helperText('Teléfono directo de la persona de contacto')
                                                    ->columnSpan(1),

                                                TextInput::make('contacto_correo')
                                                    ->label('Correo de Contacto')
                                                    ->email()
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: juan@proveedor.com')
                                                    ->helperText('Correo electrónico directo de la persona de contacto')
                                                    ->columnSpan(1),
                                            ]),
                                    ]),
                            ]),

                        // ========== TAB 3: INFORMACIÓN COMERCIAL ==========
                        Tabs\Tab::make('Comercial')
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Section::make('Datos Comerciales')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Información comercial y condiciones')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Forms\Components\Select::make('tipo_proveedor')
                                                    ->label('Tipo de Proveedor')
                                                    ->options([
                                                        'nacional' => 'Nacional',
                                                        'internacional' => 'Internacional',
                                                        'local' => 'Local',
                                                    ])
                                                    ->default('nacional')
                                                    ->placeholder('Seleccione un tipo')
                                                    ->helperText('Clasificación del proveedor')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'tipo_proveedor')),

                                                Forms\Components\Select::make('calificacion')
                                                    ->label('Calificación')
                                                    ->options([
                                                        1 => 'Muy Bajo',
                                                        2 => 'Bajo',
                                                        3 => 'Medio',
                                                        4 => 'Alto',
                                                        5 => 'Muy Alto',
                                                    ])
                                                    ->default(3)
                                                    ->placeholder('Seleccione una calificación')
                                                    ->helperText('Calificación basada en el desempeño del proveedor')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'calificacion')),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('tiempo_entrega')
                                                    ->label('Tiempo de Entrega (días)')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->maxValue(365)
                                                    ->placeholder('Ej: 15')
                                                    ->helperText('Días promedio de entrega')
                                                    ->suffix('días')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'tiempo_entrega')),

                                                TextInput::make('condiciones_pago')
                                                    ->label('Condiciones de Pago')
                                                    ->maxLength(255)
                                                    ->placeholder('Ej: 30 días, 60 días')
                                                    ->helperText('Condiciones de pago acordadas')
                                                    ->columnSpan(1)
                                                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'condiciones_pago')),
                                            ]),

                                        Textarea::make('observaciones')
                                            ->label('Observaciones')
                                            ->rows(4)
                                            ->placeholder('Notas adicionales sobre el proveedor...')
                                            ->helperText('Información adicional relevante')
                                            ->columnSpanFull()
                                            ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'observaciones')),
                                    ]),
                            ]),

                        // ========== TAB 4: ESTADÍSTICAS ==========
                        Tabs\Tab::make('Estadísticas')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen del Proveedor')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Forms\Components\Placeholder::make('estadisticas')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'Las estadísticas se mostrarán después de guardar el proveedor.';
                                                }

                                                // Contar artículos asociados
                                                $totalArticulos = 0;
                                                $articulosPrincipales = 0;
                                                
                                                try {
                                                    $totalArticulos = $record->articulos()->count();
                                                    $articulosPrincipales = $record->articulos()->where('es_principal', true)->count();
                                                } catch (\Exception $e) {
                                                    // Si la relación no existe, ignorar
                                                }

                                                return view('filament.components.proveedor-estadisticas', [
                                                    'totalArticulos' => $totalArticulos,
                                                    'articulosPrincipales' => $articulosPrincipales,
                                                    'record' => $record,
                                                ]);
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
                    ->label('Nombre / Razón Social')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Nombre copiado')
                    ->toggleable(),

                TextColumn::make('nit')
                    ->label('NIT / RUC')
                    ->searchable()
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

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->toggleable(),

                TextColumn::make('articulos_count')
                    ->label('Artículos')
                    ->counts('articulos')
                    ->badge()
                    ->color('info')
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

                Tables\Filters\SelectFilter::make('tipo_proveedor')
                    ->label('Tipo de Proveedor')
                    ->options([
                        'nacional' => 'Nacional',
                        'internacional' => 'Internacional',
                        'local' => 'Local',
                    ])
                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'tipo_proveedor')),

                Tables\Filters\SelectFilter::make('calificacion')
                    ->label('Calificación')
                    ->options([
                        1 => 'Muy Bajo',
                        2 => 'Bajo',
                        3 => 'Medio',
                        4 => 'Alto',
                        5 => 'Muy Alto',
                    ])
                    ->visible(fn () => Schema::hasColumn('cmp_proveedores', 'calificacion')),
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
                                ->title('Proveedor duplicado exitosamente')
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
                                ->title($record->activo ? 'Proveedor activado' : 'Proveedor desactivado')
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
                        ->modalHeading('Cambiar estado de proveedores')
                        ->modalSubheading('¿Deseas cambiar el estado de los proveedores seleccionados?'),
                ]),
            ])
            ->defaultSort('nombre')
            ->searchPlaceholder('Buscar proveedor...')
            ->emptyStateHeading('No hay proveedores registrados')
            ->emptyStateDescription('Crea tu primer proveedor para comenzar a gestionar tus compras.')
            ->emptyStateIcon('heroicon-o-users')
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
            'index' => Pages\ListProveedors::route('/'),
            'create' => Pages\CreateProveedor::route('/create'),
            'edit' => Pages\EditProveedor::route('/{record}/edit'),
        ];
    }
}