<?php

namespace App\Filament\Resources\Inventario;

use App\Filament\Resources\Inventario\KardexResource\Pages;
use App\Models\Inventario\Kardex;
use App\Models\Inventario\Articulo;
use App\Models\Inventario\Almacen;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
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
use Illuminate\Support\HtmlString;

class KardexResource extends Resource
{
    protected static ?string $model = Kardex::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Kardex';

    protected static ?string $modelLabel = 'Registro Kardex';

    protected static ?string $pluralModelLabel = 'Registros Kardex';

    protected static ?int $navigationSort = 3;

    private static function getSimboloMoneda($moneda = 'BOB'): string
    {
        return match ($moneda) {
            'BOB' => 'Bs',
            'USD' => '$',
            'EUR' => '€',
            default => 'Bs',
        };
    }

    private static function formatearMonto($monto, $moneda = 'BOB'): string
    {
        return self::getSimboloMoneda($moneda) . ' ' . number_format($monto ?? 0, 2);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión Kardex')
                    ->tabs([
                        Tabs\Tab::make('Información General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos del Movimiento')
                                    ->icon('heroicon-o-archive-box')
                                    ->description('Información principal del movimiento de inventario')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('articulo_id')
                                                    ->label('Artículo')
                                                    ->options(
                                                        fn() => Articulo::where('activo', true)
                                                            ->orderBy('codigo')
                                                            ->get()
                                                            ->mapWithKeys(fn($item) => [
                                                                $item->id => $item->codigo . ' - ' . ($item->nombre_comercial ?? $item->descripcion ?? 'Sin descripción')
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable(['codigo', 'descripcion', 'nombre_comercial'])
                                                    ->preload()
                                                    ->placeholder('Seleccione un artículo')
                                                    ->prefixIcon('heroicon-o-cube')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            $articulo = Articulo::find($state);
                                                            if ($articulo) {
                                                                $set('unidad_medida', $articulo->unidadMedida?->abreviatura ?? 'UND');
                                                                $set('costo_unitario', $articulo->ultimo_costo ?? 0);
                                                            }
                                                        }
                                                    }),

                                                Select::make('almacen_id')
                                                    ->label('Almacén')
                                                    ->options(
                                                        fn() => Almacen::where('activo', true)
                                                            ->pluck('nombre', 'id')
                                                            ->toArray()
                                                    )
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un almacén')
                                                    ->prefixIcon('heroicon-o-building-storefront'),

                                                DatePicker::make('fecha_movimiento')
                                                    ->label('Fecha Movimiento')
                                                    ->displayFormat('d/m/Y H:i')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha y hora del movimiento')
                                                    ->prefixIcon('heroicon-o-calendar'),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('tipo_movimiento')
                                                    ->label('Tipo de Movimiento')
                                                    ->options([
                                                        'compra' => 'Compra',
                                                        'venta' => 'Venta',
                                                        'transferencia_entrada' => 'Transferencia Entrada',
                                                        'transferencia_salida' => 'Transferencia Salida',
                                                        'ajuste_incremento' => 'Ajuste (+)',
                                                        'ajuste_decremento' => 'Ajuste (-)',
                                                        'devolucion_compra' => 'Devolución Compra',
                                                        'devolucion_venta' => 'Devolución Venta',
                                                        'produccion_entrada' => 'Producción Entrada',
                                                        'produccion_salida' => 'Producción Salida',
                                                        'inventario_inicial' => 'Inventario Inicial',
                                                        'ajuste_fisico' => 'Ajuste Físico',
                                                        'merma' => 'Merma',
                                                        'despacho' => 'Despacho',
                                                        'consignacion' => 'Consignación',
                                                    ])
                                                    ->required()
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione el tipo de movimiento')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $direccion = match($state) {
                                                            'compra', 'transferencia_entrada', 'ajuste_incremento',
                                                            'devolucion_venta', 'produccion_entrada', 
                                                            'inventario_inicial', 'consignacion' => 'entrada',
                                                            'venta', 'transferencia_salida', 'ajuste_decremento',
                                                            'devolucion_compra', 'produccion_salida',
                                                            'merma', 'despacho' => 'salida',
                                                            default => null
                                                        };
                                                        $set('direccion', $direccion);
                                                    }),

                                                Select::make('direccion')
                                                    ->label('Dirección')
                                                    ->options([
                                                        'entrada' => 'Entrada',
                                                        'salida' => 'Salida',
                                                    ])
                                                    ->required()
                                                    ->searchable()
                                                    ->prefixIcon('heroicon-o-arrow-path')
                                                    ->disabled()
                                                    ->dehydrated(),

                                                TextInput::make('cantidad')
                                                    ->label('Cantidad')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0.01)
                                                    ->step(1.00)
                                                    ->placeholder('0.00')
                                                    ->helperText('Cantidad del movimiento')
                                                    ->prefixIcon('heroicon-o-numbered-list')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        $cantidad = floatval($state);
                                                        $costoUnitario = floatval($get('costo_unitario') ?? 0);
                                                        $set('costo_total', $cantidad * $costoUnitario);
                                                    }),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('costo_unitario')
                                                    ->label('Costo Unitario')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->step(0.000001)
                                                    ->placeholder('0.00')
                                                    ->prefix(fn($get) => self::getSimboloMoneda('BOB'))
                                                    ->helperText('Costo unitario del movimiento')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        $cantidad = floatval($get('cantidad') ?? 0);
                                                        $costoUnitario = floatval($state);
                                                        $set('costo_total', $cantidad * $costoUnitario);
                                                    }),

                                                TextInput::make('costo_total')
                                                    ->label('Costo Total')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(0)
                                                    ->step(0.000001)
                                                    ->placeholder('0.00')
                                                    ->prefix(fn($get) => self::getSimboloMoneda('BOB'))
                                                    ->helperText('Costo total del movimiento')
                                                    ->disabled(),

                                                TextInput::make('documento_codigo')
                                                    ->label('Documento Origen')
                                                    ->maxLength(50)
                                                    ->placeholder('OC-001, VTA-001, etc.')
                                                    ->helperText('Código del documento origen')
                                                    ->prefixIcon('heroicon-o-document-text'),
                                            ]),
                                    ]),

                                Section::make('Saldos y Costos')
                                    ->icon('heroicon-o-chart-bar')
                                    ->description('Saldos y costos antes y después del movimiento')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                Placeholder::make('cantidad_anterior')
                                                    ->label('Saldo Anterior')
                                                    ->content(function ($get) {
                                                        $articuloId = $get('articulo_id');
                                                        $almacenId = $get('almacen_id');
                                                        if ($articuloId && $almacenId) {
                                                            $existencia = \App\Models\Inventario\Existencia::where('articulo_id', $articuloId)
                                                                ->where('almacen_id', $almacenId)
                                                                ->first();
                                                            return number_format($existencia?->cantidad_disponible ?? 0, 2);
                                                        }
                                                        return '0.00';
                                                    }),

                                                Placeholder::make('cantidad_posterior')
                                                    ->label('Saldo Posterior')
                                                    ->content(function ($get) {
                                                        $articuloId = $get('articulo_id');
                                                        $almacenId = $get('almacen_id');
                                                        $cantidad = floatval($get('cantidad') ?? 0);
                                                        $direccion = $get('direccion');
                                                        
                                                        if ($articuloId && $almacenId) {
                                                            $existencia = \App\Models\Inventario\Existencia::where('articulo_id', $articuloId)
                                                                ->where('almacen_id', $almacenId)
                                                                ->first();
                                                            $saldoActual = $existencia?->cantidad_disponible ?? 0;
                                                            
                                                            if ($direccion === 'entrada') {
                                                                return number_format($saldoActual + $cantidad, 2);
                                                            } elseif ($direccion === 'salida') {
                                                                return number_format(max(0, $saldoActual - $cantidad), 2);
                                                            }
                                                        }
                                                        return '0.00';
                                                    }),

                                                Placeholder::make('costo_promedio')
                                                    ->label('Costo Promedio')
                                                    ->content(function ($get) {
                                                        $articuloId = $get('articulo_id');
                                                        $almacenId = $get('almacen_id');
                                                        if ($articuloId && $almacenId) {
                                                            $existencia = \App\Models\Inventario\Existencia::where('articulo_id', $articuloId)
                                                                ->where('almacen_id', $almacenId)
                                                                ->first();
                                                            return self::formatearMonto($existencia?->costo_promedio ?? 0);
                                                        }
                                                        return self::formatearMonto(0);
                                                    }),

                                                Placeholder::make('costo_acumulado')
                                                    ->label('Costo Acumulado')
                                                    ->content(function ($get) {
                                                        $articuloId = $get('articulo_id');
                                                        $almacenId = $get('almacen_id');
                                                        if ($articuloId && $almacenId) {
                                                            $existencia = \App\Models\Inventario\Existencia::where('articulo_id', $articuloId)
                                                                ->where('almacen_id', $almacenId)
                                                                ->first();
                                                            return self::formatearMonto($existencia?->costo_acumulado ?? 0);
                                                        }
                                                        return self::formatearMonto(0);
                                                    }),
                                            ]),
                                    ]),

                                Section::make('Información Adicional')
                                    ->icon('heroicon-o-clipboard-document')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->options([
                                                        'pendiente' => 'Pendiente',
                                                        'confirmado' => 'Confirmado',
                                                        'cancelado' => 'Cancelado',
                                                        'anulado' => 'Anulado',
                                                    ])
                                                    ->default('confirmado')
                                                    ->required()
                                                    ->searchable()
                                                    ->prefixIcon('heroicon-o-tag'),

                                                TextInput::make('motivo')
                                                    ->label('Motivo')
                                                    ->maxLength(255)
                                                    ->placeholder('Motivo del movimiento...')
                                                    ->helperText('Motivo o razón del movimiento')
                                                    ->prefixIcon('heroicon-o-document-text'),
                                            ]),

                                        Textarea::make('observaciones')
                                            ->label('Observaciones')
                                            ->rows(3)
                                            ->placeholder('Observaciones adicionales...')
                                            ->helperText('Información adicional sobre el movimiento')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Auditoría')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Información de Auditoría')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('usuario.name')
                                                    ->label('Usuario')
                                                    ->content(fn($record) => $record?->usuario?->name ?? 'N/A'),
                                                Placeholder::make('creador.name')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? 'N/A'),
                                                Placeholder::make('autorizador.name')
                                                    ->label('Autorizado por')
                                                    ->content(fn($record) => $record?->autorizador?->name ?? 'N/A'),
                                            ]),
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('created_at')
                                                    ->label('Fecha creación')
                                                    ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i') ?? 'N/A'),
                                                Placeholder::make('updated_at')
                                                    ->label('Última actualización')
                                                    ->content(fn($record) => $record?->updated_at?->format('d/m/Y H:i') ?? 'N/A'),
                                            ]),
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
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->width('60px'),

                TextColumn::make('articulo.codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->width('100px'),

                TextColumn::make('articulo.nombre_comercial')
                    ->label('Artículo')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->toggleable(),

                TextColumn::make('almacen.nombre')
                    ->label('Almacén')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                BadgeColumn::make('tipo_movimiento')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => match($state) {
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'transferencia_entrada' => 'Transf. Ent.',
                        'transferencia_salida' => 'Transf. Sal.',
                        'ajuste_incremento' => 'Ajuste (+)',
                        'ajuste_decremento' => 'Ajuste (-)',
                        'devolucion_compra' => 'Dev. Compra',
                        'devolucion_venta' => 'Dev. Venta',
                        'produccion_entrada' => 'Prod. Ent.',
                        'produccion_salida' => 'Prod. Sal.',
                        'inventario_inicial' => 'Inv. Inicial',
                        'ajuste_fisico' => 'Ajuste Fís.',
                        'merma' => 'Merma',
                        'despacho' => 'Despacho',
                        'consignacion' => 'Consignación',
                        default => $state,
                    })
                    ->colors([
                        'success' => 'compra',
                        'danger' => 'venta',
                        'info' => 'transferencia_entrada',
                        'warning' => 'transferencia_salida',
                        'success' => 'ajuste_incremento',
                        'danger' => 'ajuste_decremento',
                        'warning' => 'devolucion_compra',
                        'info' => 'devolucion_venta',
                        'primary' => 'produccion_entrada',
                        'primary' => 'produccion_salida',
                        'gray' => 'inventario_inicial',
                        'warning' => 'ajuste_fisico',
                        'danger' => 'merma',
                    ])
                    ->toggleable(),

                BadgeColumn::make('direccion')
                    ->label('Dir.')
                    ->formatStateUsing(fn($state) => $state === 'entrada' ? 'Entrada' : 'Salida')
                    ->colors([
                        'success' => 'entrada',
                        'danger' => 'salida',
                    ])
                    ->toggleable()
                    ->width('60px'),

                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->color(fn($record) => $record->direccion === 'entrada' ? 'success' : 'danger')
                    ->weight('bold'),

                TextColumn::make('costo_unitario')
                    ->label('Costo Unit.')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->prefix('$'),

                TextColumn::make('costo_total')
                    ->label('Costo Total')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->prefix('$'),

                TextColumn::make('cantidad_anterior')
                    ->label('Saldo Ant.')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('cantidad_posterior')
                    ->label('Saldo Post.')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->weight('bold'),

                TextColumn::make('documento_codigo')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->width('120px'),

                TextColumn::make('fecha_movimiento')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->width('140px'),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'pendiente' => 'Pendiente',
                        'confirmado' => 'Confirmado',
                        'cancelado' => 'Cancelado',
                        'anulado' => 'Anulado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'pendiente',
                        'success' => 'confirmado',
                        'danger' => 'cancelado',
                        'danger' => 'anulado',
                    ])
                    ->toggleable(),

                TextColumn::make('usuario.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('articulo_id')
                    ->label('Artículo')
                    ->options(
                        fn() => Articulo::where('activo', true)
                            ->orderBy('codigo')
                            ->get()
                            ->mapWithKeys(fn($item) => [
                                $item->id => $item->codigo . ' - ' . ($item->nombre_comercial ?? $item->descripcion ?? 'Sin descripción')
                            ])
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('almacen_id')
                    ->label('Almacén')
                    ->options(
                        fn() => Almacen::where('activo', true)
                            ->pluck('nombre', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tipo_movimiento')
                    ->label('Tipo de Movimiento')
                    ->options([
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'transferencia_entrada' => 'Transferencia Entrada',
                        'transferencia_salida' => 'Transferencia Salida',
                        'ajuste_incremento' => 'Ajuste (+)',
                        'ajuste_decremento' => 'Ajuste (-)',
                        'devolucion_compra' => 'Devolución Compra',
                        'devolucion_venta' => 'Devolución Venta',
                        'produccion_entrada' => 'Producción Entrada',
                        'produccion_salida' => 'Producción Salida',
                        'inventario_inicial' => 'Inventario Inicial',
                        'ajuste_fisico' => 'Ajuste Físico',
                        'merma' => 'Merma',
                        'despacho' => 'Despacho',
                        'consignacion' => 'Consignación',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('direccion')
                    ->label('Dirección')
                    ->options([
                        'entrada' => 'Entrada',
                        'salida' => 'Salida',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmado' => 'Confirmado',
                        'cancelado' => 'Cancelado',
                        'anulado' => 'Anulado',
                    ])
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('fecha_movimiento')
                    ->label('Rango de Fechas')
                    ->form([
                        DatePicker::make('fecha_desde')
                            ->label('Desde')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        DatePicker::make('fecha_hasta')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['fecha_desde'], fn($q, $fecha) => $q->whereDate('fecha_movimiento', '>=', $fecha))
                            ->when($data['fecha_hasta'], fn($q, $fecha) => $q->whereDate('fecha_movimiento', '<=', $fecha));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->visible(fn($record) => $record->estado !== 'confirmado'),

                    Tables\Actions\Action::make('confirmar')
                        ->label('Confirmar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->update(['estado' => 'confirmado']);
                            Notification::make()
                                ->title('Movimiento confirmado')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'pendiente'),

                    Tables\Actions\Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update(['estado' => 'anulado']);
                            Notification::make()
                                ->title('Movimiento anulado')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn($record) => in_array($record->estado, ['pendiente', 'confirmado'])),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            // ->bulkActions([
            //     Tables\Actions\BulkActionGroup::make([
            //         Tables\Actions\DeleteBulkAction::make()
            //             ->visible(fn($records) => $records->every(fn($record) => $record->estado === 'pendiente')),
            //     ]),
            // ])
            ->defaultSort('fecha_movimiento', 'desc')
            ->searchPlaceholder('Buscar en kardex...')
            ->emptyStateHeading('No hay movimientos registrados')
            ->emptyStateDescription('Registra movimientos de inventario para mantener el kardex actualizado.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKardexes::route('/'),
            'create' => Pages\CreateKardex::route('/create'),
            'edit' => Pages\EditKardex::route('/{record}/edit'),
        ];
    }
}