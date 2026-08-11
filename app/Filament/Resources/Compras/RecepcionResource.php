<?php

namespace App\Filament\Resources\Compras;

use App\Filament\Resources\Compras\RecepcionResource\Pages;
use App\Models\Compras\Recepcion;
use App\Models\Compras\OrdenCompra;
use App\Models\Compras\Proveedor;
use App\Models\Inventario\Articulo;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class RecepcionResource extends Resource
{
    protected static ?string $model = Recepcion::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Recepciones';

    protected static ?string $modelLabel = 'Recepción';

    protected static ?string $pluralModelLabel = 'Recepciones';

    protected static ?int $navigationSort = 3;

    private static function formatearMonto($monto): string
    {
        return 'Bs ' . number_format($monto ?? 0, 2);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Recepción')
                    ->tabs([
                        Tabs\Tab::make('📋 General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Recepción')
                                    ->icon('heroicon-o-inbox')
                                    ->description('Información principal de la recepción')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->disabled()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('REC-000001')
                                                    ->helperText('Código único de la recepción')
                                                    ->default(fn() => Recepcion::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_recepcion')
                                                    ->label('Fecha Recepción')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de recepción')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'pendiente' => '⏳ Pendiente',
                                                        'parcial' => '📦 Parcial',
                                                        'completada' => '✅ Completada',
                                                        'rechazada' => '❌ Rechazada',
                                                    ])
                                                    ->default('pendiente')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),

                                                TextInput::make('guia_remision')
                                                    ->label('Guía de Remisión')
                                                    ->maxLength(50)
                                                    ->placeholder('Número de guía')
                                                    ->helperText('Número de guía de remisión')
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Select::make('orden_compra_id')
                                                    ->label('Orden de Compra')
                                                    ->options(
                                                        fn() => OrdenCompra::whereIn('estado', ['confirmada', 'parcial', 'recibida'])
                                                            ->orderBy('codigo')
                                                            ->get()
                                                            ->mapWithKeys(fn($item) => [
                                                                $item->id => $item->codigo . ' - ' . ($item->proveedor?->nombre ?? 'Sin proveedor')
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una orden')
                                                    ->helperText('Orden de compra asociada')
                                                    ->prefixIcon('heroicon-o-shopping-cart')
                                                    ->reactive()
                                                    ->columnSpan(2),

                                                Select::make('proveedor_id')
                                                    ->label('Proveedor')
                                                    ->options(
                                                        fn() => Proveedor::where('activo', true)
                                                            ->orderBy('nombre')
                                                            ->pluck('nombre', 'id')
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un proveedor')
                                                    ->helperText('Proveedor de la recepción')
                                                    ->prefixIcon('heroicon-o-building-office-2')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->columnSpan(1),

                                                TextInput::make('transportista')
                                                    ->label('Transportista')
                                                    ->maxLength(100)
                                                    ->placeholder('Nombre del transportista')
                                                    ->helperText('Transportista de la mercadería')
                                                    ->prefixIcon('heroicon-o-truck')
                                                    ->columnSpan(1),
                                            ]),

                                        Textarea::make('observaciones')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->placeholder('Observaciones de la recepción...')
                                            ->helperText('Información adicional sobre la recepción')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('📦 Productos')
                            ->icon('heroicon-o-shopping-bag')
                            ->badge(function ($record) {
                                if (!$record) return 0;
                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle de Recepción')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Artículos recibidos')
                                    ->schema([
                                        Repeater::make('detalles')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                Grid::make(12)
                                                    ->schema([
                                                        Select::make('orden_detalle_id')
                                                            ->label('Producto')
                                                            ->options(function ($get, $livewire) {
                                                                $ordenId = $get('../../orden_compra_id') ?? $livewire->getOwnerRecord()?->orden_compra_id;
                                                                if (!$ordenId) return [];

                                                                $orden = OrdenCompra::with('detalles.articulo')->find($ordenId);
                                                                if (!$orden) return [];

                                                                return $orden->detalles->mapWithKeys(function ($detalle) {
                                                                    $recibido = \App\Models\Compras\RecepcionDetalle::where('orden_detalle_id', $detalle->id)->sum('cantidad_aceptada');
                                                                    $pendiente = $detalle->cantidad - $recibido;
                                                                    if ($pendiente <= 0) return [];
                                                                    return [
                                                                        $detalle->id => $detalle->articulo->codigo . ' - ' . $detalle->articulo->nombre_comercial .
                                                                            ' (Pendiente: ' . number_format($pendiente, 2) . ')'
                                                                    ];
                                                                })->toArray();
                                                            })
                                                            ->required()
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione un producto')
                                                            ->helperText('Producto de la orden de compra')
                                                            ->prefixIcon('heroicon-o-cube')
                                                            ->reactive()
                                                            ->columnSpan(4)
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                if ($state) {
                                                                    $detalle = \App\Models\Compras\OrdenCompraDetalle::with('articulo')->find($state);
                                                                    if ($detalle) {
                                                                        $set('articulo_id', $detalle->articulo_id);
                                                                        $set('codigo_articulo', $detalle->codigo_articulo);
                                                                        $set('descripcion_articulo', $detalle->descripcion_articulo);
                                                                        $set('unidad_medida', $detalle->unidad_medida);
                                                                        $set('costo_unitario', $detalle->precio_unitario);
                                                                        $recibido = \App\Models\Compras\RecepcionDetalle::where('orden_detalle_id', $state)->sum('cantidad_aceptada');
                                                                        $pendiente = $detalle->cantidad - $recibido;
                                                                        $set('cantidad', $pendiente);
                                                                    }
                                                                }
                                                            }),

                                                        TextInput::make('cantidad_aceptada')
                                                            ->label('Cantidad Aceptada')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->step(1)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-numbered-list')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($state);
                                                                $costo = floatval($get('costo_unitario') ?? 0);
                                                                $set('costo_total', $cantidad * $costo);
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('cantidad_rechazada')
                                                            ->label('Cantidad Rechazada')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(1)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-x-circle')
                                                            ->columnSpan(2),

                                                        TextInput::make('costo_unitario')
                                                            ->label('Costo Unitario')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0)
                                                            ->step(1)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix('$')
                                                            ->helperText('Costo unitario del producto')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                $cantidad = floatval($get('cantidad_aceptada') ?? 0);
                                                                $costo = floatval($state);
                                                                $set('costo_total', $cantidad * $costo);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('costo_total')
                                                            ->label('Costo Total')
                                                            ->content(function ($get) {
                                                                return '$ ' . number_format($get('costo_total') ?? 0, 2);
                                                            })
                                                            ->extraAttributes(['class' => 'font-bold'])
                                                            ->columnSpan(2),
                                                    ]),

                                                Textarea::make('motivo_rechazo')
                                                    ->label('Motivo de Rechazo')
                                                    ->rows(2)
                                                    ->placeholder('Motivo del rechazo...')
                                                    ->helperText('Especificar motivo si hay cantidad rechazada')
                                                    ->visible(fn($get) => floatval($get('cantidad_rechazada') ?? 0) > 0)
                                                    ->columnSpanFull(),

                                                TextInput::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->maxLength(255)
                                                    ->placeholder('Observaciones sobre este producto...')
                                                    ->prefixIcon('heroicon-o-clipboard-document')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(0)
                                            ->collapsible()
                                            ->cloneable()
                                            ->addActionLabel('➕ Agregar Producto')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                                $articulo = null;
                                                if (isset($data['articulo_id']) && $data['articulo_id']) {
                                                    $articulo = Articulo::find($data['articulo_id']);
                                                }

                                                $data['codigo_articulo'] = $articulo ? $articulo->codigo : 'SIN_CODIGO';
                                                $data['descripcion_articulo'] = $articulo ? ($articulo->descripcion ?? $articulo->nombre_comercial ?? 'Sin descripción') : '';
                                                $data['unidad_medida'] = $articulo ? ($articulo->unidadMedida?->abreviatura ?? 'UND') : 'UND';
                                                $data['costo_total'] = floatval($data['cantidad_aceptada'] ?? 0) * floatval($data['costo_unitario'] ?? 0);

                                                return $data;
                                            }),
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
                    ->width('120px')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('ordenCompra.codigo')
                    ->label('Orden Compra')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                DatePicker::make('fecha_recepcion')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'pendiente' => '⏳ Pendiente',
                        'parcial' => '📦 Parcial',
                        'completada' => '✅ Completada',
                        'rechazada' => '❌ Rechazada',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'info' => 'parcial',
                        'success' => 'completada',
                        'danger' => 'rechazada',
                    ])
                    ->toggleable(),

                TextColumn::make('total_items')
                    ->label('Items')
                    ->getStateUsing(fn($record) => $record->detalles()->count())
                    ->badge()
                    ->color('info')
                    ->toggleable()
                    ->width('60px'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'parcial' => 'Parcial',
                        'completada' => 'Completada',
                        'rechazada' => 'Rechazada',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('proveedor_id')
                    ->label('Proveedor')
                    ->relationship('proveedor', 'nombre')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('orden_compra_id')
                    ->label('Orden Compra')
                    ->relationship('ordenCompra', 'codigo')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('completar')
                        ->label('Completar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->estado = 'completada';
                            $record->save();
                            Notification::make()
                                ->title('Recepción completada')
                                ->body('La recepción ' . $record->codigo . ' ha sido completada.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'parcial'),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => $record->estado === 'pendiente'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn($records) => $records->every(fn($record) => $record->estado === 'pendiente')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar recepción...')
            ->emptyStateHeading('No hay recepciones registradas')
            ->emptyStateDescription('Crea una recepción para registrar ingreso de mercadería.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecepcions::route('/'),
            'create' => Pages\CreateRecepcion::route('/create'),
            'edit' => Pages\EditRecepcion::route('/{record}/edit'),
        ];
    }
}