<?php

namespace App\Filament\Resources\Compras;

use App\Filament\Resources\Compras\SolicitudCompraResource\Pages;
use App\Models\Compras\SolicitudCompra;
use App\Models\Compras\SolicitudCompraDetalle;
use App\Models\Inventario\Articulo;
use App\Models\Sistema\Departamento;
use App\Models\User;
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
use Illuminate\Support\HtmlString;

class SolicitudCompraResource extends Resource
{
    protected static ?string $model = SolicitudCompra::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationGroup = 'Compras';

    protected static ?string $navigationLabel = 'Solicitudes de Compra';

    protected static ?string $modelLabel = 'Solicitud de Compra';

    protected static ?string $pluralModelLabel = 'Solicitudes de Compra';

    protected static ?int $navigationSort = 1;

    // ========== HELPER METHODS ==========

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

    // Agregar este método
    private static function formatearMontoHtml($monto, $moneda = 'BOB', $clase = ''): HtmlString
    {
        $simbolo = self::getSimboloMoneda($moneda);
        return new HtmlString(
            '<span class="' . $clase . '">' .
                $simbolo . ' ' . number_format($monto ?? 0, 2) .
                '</span>'
        );
    }

    private static function recalcularTotales(callable $set, callable $get): void
    {
        $detalles = $get('detalles') ?? [];
        $subtotal = 0;
        $impuesto = 0;
        $total = 0;

        foreach ($detalles as $detalle) {
            if (is_array($detalle)) {
                $cantidad = floatval($detalle['cantidad'] ?? 0);
                $precio = floatval($detalle['precio_estimado'] ?? 0);
                $subtotal += $cantidad * $precio;
            }
        }

        $impuesto = $subtotal * 0.13;
        $total = $subtotal + $impuesto;

        $set('subtotal', $subtotal);
        $set('impuesto', $impuesto);
        $set('total', $total);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Solicitud')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Solicitud')
                                    ->icon('heroicon-o-document-text')
                                    ->description('Información principal de la solicitud de compra')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('SOL-000001')
                                                    ->helperText('Código único de la solicitud')
                                                    ->default(fn() => SolicitudCompra::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_solicitud')
                                                    ->label('Fecha Solicitud')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha de la solicitud')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_requerida')
                                                    ->label('Fecha Requerida')
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now()->addDays(7))
                                                    ->native(false)
                                                    ->helperText('Fecha en que se requiere el material')
                                                    ->prefixIcon('heroicon-o-calendar-days')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => '📝 Borrador',
                                                        'pendiente' => '⏳ Pendiente',
                                                        'aprobada' => '✅ Aprobada',
                                                        'rechazada' => '❌ Rechazada',
                                                        'en_cotizacion' => '📊 En Cotización',
                                                        'convertida' => '🔄 Convertida',
                                                    ])
                                                    ->default('borrador')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado actual')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                Select::make('solicitado_por')
                                                    ->label('Solicitante')
                                                    ->relationship('solicitante', 'name')
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(Auth::id())
                                                    ->required()
                                                    ->helperText('Persona que solicita la compra')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->columnSpan(1),

                                                Select::make('area_id')
                                                    ->label('Área')
                                                    ->options(
                                                        fn() => \App\Models\Sistema\Area::pluck('nombre', 'id')->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione un área')
                                                    ->helperText('Área solicitante')
                                                    ->prefixIcon('heroicon-o-building-office')
                                                    ->columnSpan(1),

                                                Select::make('prioridad')
                                                    ->label('Prioridad')
                                                    ->options([
                                                        'baja' => '🟢 Baja',
                                                        'normal' => '🟡 Normal',
                                                        'alta' => '🟠 Alta',
                                                        'urgente' => '🔴 Urgente',
                                                    ])
                                                    ->default('normal')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Prioridad de la solicitud')
                                                    ->prefixIcon('heroicon-o-flag')
                                                    ->columnSpan(1),

                                                Placeholder::make('creado_por')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? Auth::user()?->name ?? 'N/A')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Textarea::make('justificacion')
                                                    ->label('Justificación')
                                                    ->rows(3)
                                                    ->placeholder('Justificación de la solicitud...')
                                                    ->helperText('Motivo o razón de la solicitud')
                                                    ->columnSpanFull(),

                                                Textarea::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->rows(3)
                                                    ->placeholder('Observaciones adicionales...')
                                                    ->helperText('Información adicional sobre la solicitud')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Section::make('Totales')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('subtotal')
                                                    ->label('Subtotal')
                                                    ->content(function ($get) {
                                                        return self::formatearMonto($get('subtotal') ?? 0);
                                                    }),

                                                Placeholder::make('impuesto')
                                                    ->label('Impuesto (13%)')
                                                    ->content(function ($get) {
                                                        return self::formatearMonto($get('impuesto') ?? 0);
                                                    }),

                                                Placeholder::make('total')
                                                    ->label('Total Estimado')
                                                    ->content(function ($get) {
                                                        return self::formatearMontoHtml(
                                                            $get('total') ?? 0,
                                                            'BOB',
                                                            'font-bold text-lg text-primary-600 dark:text-primary-400'
                                                        );
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Productos')
                            ->icon('heroicon-o-shopping-bag')
                            ->badge(function ($record) {
                                if (!$record) return 0;
                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle de Productos')
                                    ->icon('heroicon-o-shopping-bag')
                                    ->description('Artículos solicitados')
                                    ->schema([
                                        Repeater::make('detalles')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                Grid::make(12)
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
                                                            ->placeholder('Buscar artículo...')
                                                            ->prefixIcon('heroicon-o-cube')
                                                            ->columnSpan(4)
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set) {
                                                                if ($state) {
                                                                    $articulo = Articulo::find($state);
                                                                    if ($articulo) {
                                                                        $set('codigo_articulo', $articulo->codigo);
                                                                        $set('descripcion_articulo', $articulo->descripcion ?? $articulo->nombre_comercial ?? '');
                                                                        $set('unidad_medida', $articulo->unidadMedida?->abreviatura ?? 'UND');
                                                                    }
                                                                }
                                                            }),

                                                        TextInput::make('cantidad')
                                                            ->label('Cantidad')
                                                            ->numeric()
                                                            ->required()
                                                            ->minValue(0.01)
                                                            ->step(1.00)
                                                            ->default(1)
                                                            ->placeholder('0.00')
                                                            ->prefixIcon('heroicon-o-numbered-list')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::recalcularTotales($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('precio_estimado')
                                                            ->label('Precio Estimado')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix('$')
                                                            ->helperText('Precio estimado por unidad')
                                                            ->live()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                self::recalcularTotales($set, $get);
                                                            })
                                                            ->columnSpan(2),

                                                        Placeholder::make('subtotal_linea')
                                                            ->label('Subtotal')
                                                            ->content(function ($get) {
                                                                $cantidad = floatval($get('cantidad') ?? 0);
                                                                $precio = floatval($get('precio_estimado') ?? 0);
                                                                return '$ ' . number_format($cantidad * $precio, 2);
                                                            })
                                                            ->extraAttributes(['class' => 'font-bold'])
                                                            ->columnSpan(2),
                                                    ]),

                                                TextInput::make('observaciones')
                                                    ->label('Observaciones')
                                                    ->maxLength(255)
                                                    ->placeholder('Observaciones sobre este producto...')
                                                    ->prefixIcon('heroicon-o-clipboard-document')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(1)
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
                                                $data['subtotal'] = floatval($data['cantidad'] ?? 0) * floatval($data['precio_estimado'] ?? 0);

                                                return $data;
                                            }),
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
                                                Placeholder::make('creado_por')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? 'N/A'),

                                                Placeholder::make('aprobado_por')
                                                    ->label('Aprobado por')
                                                    ->content(fn($record) => $record?->aprobador?->name ?? 'N/A'),

                                                Placeholder::make('fecha_aprobacion')
                                                    ->label('Fecha aprobación')
                                                    ->content(fn($record) => $record?->fecha_aprobacion?->format('d/m/Y H:i') ?? 'N/A'),
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

                TextColumn::make('solicitante.name')
                    ->label('Solicitante')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('area.nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('fecha_solicitud')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('prioridad')
                    ->label('Prioridad')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'baja' => '🟢 Baja',
                        'normal' => '🟡 Normal',
                        'alta' => '🟠 Alta',
                        'urgente' => '🔴 Urgente',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'baja',
                        'info' => 'normal',
                        'warning' => 'alta',
                        'danger' => 'urgente',
                    ])
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match ($state) {
                        'borrador' => '📝 Borrador',
                        'pendiente' => '⏳ Pendiente',
                        'aprobada' => '✅ Aprobada',
                        'rechazada' => '❌ Rechazada',
                        'en_cotizacion' => '📊 En Cotización',
                        'convertida' => '🔄 Convertida',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'borrador',
                        'warning' => 'pendiente',
                        'success' => 'aprobada',
                        'danger' => 'rechazada',
                        'info' => 'en_cotizacion',
                        'primary' => 'convertida',
                    ])
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('BOB')
                    ->sortable()
                    ->toggleable(),

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
                        'borrador' => 'Borrador',
                        'pendiente' => 'Pendiente',
                        'aprobada' => 'Aprobada',
                        'rechazada' => 'Rechazada',
                        'en_cotizacion' => 'En Cotización',
                        'convertida' => 'Convertida',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'normal' => 'Normal',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('solicitado_por')
                    ->label('Solicitante')
                    ->relationship('solicitante', 'name')
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

                    Tables\Actions\Action::make('aprobar')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->aprobar();
                            Notification::make()
                                ->title('Solicitud aprobada')
                                ->body('La solicitud ' . $record->codigo . ' ha sido aprobada.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'pendiente'),

                    Tables\Actions\Action::make('rechazar')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('motivo')
                                ->label('Motivo de rechazo')
                                ->required()
                                ->rows(2)
                                ->placeholder('Indique el motivo del rechazo...'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->rechazar($data['motivo']);
                            Notification::make()
                                ->title('Solicitud rechazada')
                                ->body('La solicitud ' . $record->codigo . ' ha sido rechazada.')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn($record) => in_array($record->estado, ['pendiente', 'en_cotizacion'])),

                    Tables\Actions\Action::make('convertir_orden')
                        ->label('Convertir a Orden')
                        ->icon('heroicon-o-arrow-path')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Convertir a Orden de Compra')
                        ->modalSubheading('¿Deseas convertir esta solicitud en una orden de compra?')
                        ->action(function ($record) {
                            $orden = $record->crearOrdenCompra();
                            Notification::make()
                                ->title('Solicitud convertida')
                                ->body('Se ha creado la orden de compra ' . $orden->codigo)
                                ->success()
                                ->send();
                        })
                        ->visible(fn($record) => $record->estado === 'aprobada'),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => in_array($record->estado, ['borrador', 'rechazada'])),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar solicitud...')
            ->emptyStateHeading('No hay solicitudes de compra')
            ->emptyStateDescription('Crea una solicitud de compra para comenzar.')
            ->emptyStateIcon('heroicon-o-document-plus')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\CotizacionesProveedorRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSolicitudCompras::route('/'),
            'create' => Pages\CreateSolicitudCompra::route('/create'),
            'edit' => Pages\EditSolicitudCompra::route('/{record}/edit'),
        ];
    }
}
