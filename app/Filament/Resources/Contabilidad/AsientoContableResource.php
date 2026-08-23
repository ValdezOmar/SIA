<?php

namespace App\Filament\Resources\Contabilidad;

use App\Filament\Resources\Contabilidad\AsientoContableResource\Pages;
use App\Models\Contabilidad\AsientoContable;
use App\Models\Contabilidad\CentroCosto;
use App\Models\Contabilidad\PlanCuenta;
use App\Models\Contabilidad\Proyecto;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class AsientoContableResource extends Resource
{
    protected static ?string $model = AsientoContable::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Asientos Contables';

    protected static ?string $modelLabel = 'Asiento';

    protected static ?string $pluralModelLabel = 'Asientos Contables';

    protected static ?int $navigationSort = 3;

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && $record->estado === 'borrador';
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && $record->estado === 'borrador';
    }

    private static function formatearMonto($monto): string
    {
        return 'Bs '.number_format($monto ?? 0, 2);
    }

    /**
     * Aplicar filtros de empresa y sucursal a la consulta
     */
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        // Si el usuario tiene empresa asignada, filtrar por ella
        if (Auth::user()?->empresa_id) {
            $query->where('empresa_id', Auth::user()->empresa_id);
        }

        // Si el usuario tiene sucursal asignada, filtrar por ella
        if (Auth::user()?->sucursal_id) {
            $query->where('sucursal_id', Auth::user()->sucursal_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        $isAdmin = Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('super_admin');
        $defaultEmpresaId = Auth::user()?->empresa_id ?: Empresa::query()->value('id');
        $defaultSucursalId = Auth::user()?->sucursal_id ?: Sucursal::query()
            ->when($defaultEmpresaId, fn ($query) => $query->where('empresa_id', $defaultEmpresaId))
            ->value('id');

        return $form
            ->schema([
                Tabs::make('Gestión de Asiento')
                    ->tabs([
                        Tabs\Tab::make('General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos del Asiento')
                                    ->icon('heroicon-o-document-text')
                                    ->description('Información principal del asiento contable')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Select::make('empresa_id')
                                                    ->label('Empresa')
                                                    ->options(function () {
                                                        return Empresa::query()
                                                            ->orderByRaw('COALESCE(nombre_comercial, razon_social)')
                                                            ->get()
                                                            ->mapWithKeys(fn ($empresa) => [
                                                                $empresa->id => $empresa->nombre_comercial ?: $empresa->razon_social,
                                                            ])
                                                            ->toArray();
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(fn () => $defaultEmpresaId)
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $set('sucursal_id', null);

                                                        $primeraSucursal = Sucursal::query()
                                                            ->where('empresa_id', $state)
                                                            ->orderBy('nombre')
                                                            ->value('id');

                                                        if ($primeraSucursal) {
                                                            $set('sucursal_id', $primeraSucursal);
                                                        }
                                                    })
                                                    ->disabled(! $isAdmin)
                                                    ->dehydrated()
                                                    ->visible($isAdmin)
                                                    ->required(),

                                                Select::make('sucursal_id')
                                                    ->label('Sucursal')
                                                    ->options(function (callable $get) use ($defaultEmpresaId) {
                                                        $empresaId = $get('empresa_id') ?? $defaultEmpresaId;

                                                        return Sucursal::query()
                                                            ->where('empresa_id', $empresaId)
                                                            ->orderBy('nombre')
                                                            ->pluck('nombre', 'id')
                                                            ->toArray();
                                                    })
                                                    ->searchable()
                                                    ->preload()
                                                    ->default(fn () => $defaultSucursalId)
                                                    ->disabled(! $isAdmin)
                                                    ->dehydrated()
                                                    ->visible($isAdmin)
                                                    ->required(),

                                                Hidden::make('empresa_id')
                                                    ->default(fn () => Auth::user()?->empresa_id ?: $defaultEmpresaId)
                                                    ->visible(! $isAdmin)
                                                    ->dehydrated(),

                                                Hidden::make('sucursal_id')
                                                    ->default(fn () => Auth::user()?->sucursal_id ?: $defaultSucursalId)
                                                    ->visible(! $isAdmin)
                                                    ->dehydrated(),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->readOnly()
                                                    ->dehydrated()
                                                    ->maxLength(50)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('ASI-000001')
                                                    ->helperText('Código único del asiento')
                                                    ->default(fn () => AsientoContable::generarCodigo())
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpan(1),

                                                TextInput::make('numero_asiento')
                                                    ->label('Número de Asiento')
                                                    ->maxLength(50)
                                                    ->placeholder('AS-001')
                                                    ->helperText('Número de asiento (opcional)')
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->columnSpan(1),

                                                DatePicker::make('fecha_asiento')
                                                    ->label('Fecha Asiento')
                                                    ->displayFormat('d/m/Y')
                                                    ->required()
                                                    ->default(now())
                                                    ->native(false)
                                                    ->helperText('Fecha del asiento')
                                                    ->prefixIcon('heroicon-o-calendar')
                                                    ->columnSpan(1),

                                                Select::make('estado')
                                                    ->label('Estado')
                                                    ->disabled()
                                                    ->dehydrated()
                                                    ->options([
                                                        'borrador' => '📝 Borrador',
                                                        'confirmado' => '✅ Confirmado',
                                                        'anulado' => '❌ Anulado',
                                                    ])
                                                    ->default('borrador')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Estado del asiento')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->columnSpan(1),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('tipo')
                                                    ->label('Tipo de Asiento')
                                                    ->options([
                                                        'apertura' => '📂 Apertura',
                                                        'cierre' => '🔒 Cierre',
                                                        'diario' => '📋 Diario',
                                                        'compra' => '🛒 Compra',
                                                        'venta' => '💰 Venta',
                                                        'ingreso' => '📥 Ingreso',
                                                        'egreso' => '📤 Egreso',
                                                        'ajuste' => '⚙️ Ajuste',
                                                        'depreciacion' => '📉 Depreciación',
                                                        'inventario' => '📦 Inventario',
                                                        'conciliacion' => '🔄 Conciliación',
                                                    ])
                                                    ->default('diario')
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Tipo de asiento')
                                                    ->prefixIcon('heroicon-o-tag'),

                                                TextInput::make('documento_tipo')
                                                    ->label('Tipo Documento')
                                                    ->maxLength(50)
                                                    ->placeholder('factura, compra, etc.')
                                                    ->helperText('Tipo de documento origen')
                                                    ->prefixIcon('heroicon-o-document-text'),

                                                TextInput::make('documento_codigo')
                                                    ->label('Documento Origen')
                                                    ->maxLength(50)
                                                    ->placeholder('FAC-001, OC-001')
                                                    ->helperText('Código del documento origen')
                                                    ->prefixIcon('heroicon-o-document-text'),
                                            ]),

                                        Textarea::make('concepto')
                                            ->label('Concepto')
                                            ->rows(2)
                                            ->placeholder('Concepto del asiento...')
                                            ->helperText('Descripción del asiento')
                                            ->columnSpanFull(),

                                        Textarea::make('observaciones')
                                            ->label('Observaciones')
                                            ->rows(2)
                                            ->placeholder('Observaciones adicionales...')
                                            ->helperText('Información adicional')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('💰 Totales')
                                    ->icon('heroicon-o-calculator')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Placeholder::make('total_debe')
                                                    ->label('Total Debe')
                                                    ->content(function ($get, $record) {
                                                        $total = $record?->total_debe ?? 0;

                                                        return self::formatearMonto($total);
                                                    }),

                                                Placeholder::make('total_haber')
                                                    ->label('Total Haber')
                                                    ->content(function ($get, $record) {
                                                        $total = $record?->total_haber ?? 0;

                                                        return self::formatearMonto($total);
                                                    }),

                                                Placeholder::make('balance')
                                                    ->label('Balance')
                                                    ->content(function ($get, $record) {
                                                        $debe = $record?->total_debe ?? 0;
                                                        $haber = $record?->total_haber ?? 0;
                                                        $diferencia = $debe - $haber;
                                                        $color = abs($diferencia) < 0.01 ? 'text-success-600' : 'text-danger-600';

                                                        return new HtmlString(
                                                            '<span class="font-bold '.$color.'">'.
                                                                self::formatearMonto($diferencia).
                                                                ($diferencia == 0 ? ' ✅' : ' ⚠️').
                                                                '</span>'
                                                        );
                                                    }),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Partidas')
                            ->icon('heroicon-o-document-chart-bar')
                            ->badge(function ($record) {
                                if (! $record) {
                                    return 0;
                                }

                                return $record->detalles()->count();
                            })
                            ->schema([
                                Section::make('Detalle del Asiento')
                                    ->icon('heroicon-o-document-chart-bar')
                                    ->description('Partidas del asiento contable')
                                    ->schema([
                                        Repeater::make('detalles')
                                            ->relationship('detalles')
                                            ->label('')
                                            ->live()
                                            ->schema([
                                                Grid::make(12)
                                                    ->schema([
                                                        Select::make('cuenta_id')
                                                            ->label('Cuenta')
                                                            ->options(
                                                                fn () => PlanCuenta::where('activo', true)
                                                                    ->where('permite_movimiento', true)
                                                                    ->when(Auth::user()?->empresa_id, fn ($q) => $q->where('empresa_id', Auth::user()->empresa_id)
                                                                    )
                                                                    ->orderBy('codigo')
                                                                    ->get()
                                                                    ->mapWithKeys(fn ($item) => [
                                                                        $item->id => $item->codigo.' - '.$item->nombre,
                                                                    ])
                                                                    ->toArray()
                                                            )
                                                            ->required()
                                                            ->searchable(['codigo', 'nombre'])
                                                            ->preload()
                                                            ->placeholder('Seleccione una cuenta')
                                                            ->prefixIcon('heroicon-o-document-text')
                                                            ->columnSpan(4),

                                                        TextInput::make('debe')
                                                            ->label('Debe')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix('$')
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                if ($state > 0) {
                                                                    $set('haber', 0);
                                                                }
                                                            })
                                                            ->columnSpan(2),

                                                        TextInput::make('haber')
                                                            ->label('Haber')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->step(1.00)
                                                            ->default(0)
                                                            ->placeholder('0.00')
                                                            ->prefix('$')
                                                            ->reactive()
                                                            ->afterStateUpdated(function ($state, callable $set, $get) {
                                                                if ($state > 0) {
                                                                    $set('debe', 0);
                                                                }
                                                            })
                                                            ->columnSpan(2),

                                                        Select::make('centro_costo_id')
                                                            ->label('Centro Costo')
                                                            ->options(
                                                                fn () => CentroCosto::where('activo', true)
                                                                    ->when(Auth::user()?->empresa_id, fn ($q) => $q->where('empresa_id', Auth::user()->empresa_id)
                                                                    )
                                                                    ->pluck('nombre', 'id')
                                                                    ->toArray()
                                                            )
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione')
                                                            ->prefixIcon('heroicon-o-rectangle-stack')
                                                            ->columnSpan(2),

                                                        Select::make('proyecto_id')
                                                            ->label('Proyecto')
                                                            ->options(
                                                                fn () => Proyecto::whereIn('estado', ['activo', 'planeacion'])
                                                                    ->when(Auth::user()?->empresa_id, fn ($q) => $q->where('empresa_id', Auth::user()->empresa_id)
                                                                    )
                                                                    ->pluck('nombre', 'id')
                                                                    ->toArray()
                                                            )
                                                            ->searchable()
                                                            ->preload()
                                                            ->placeholder('Seleccione')
                                                            ->prefixIcon('heroicon-o-flag')
                                                            ->columnSpan(2),
                                                    ]),

                                                TextInput::make('descripcion')
                                                    ->label('Descripción')
                                                    ->maxLength(255)
                                                    ->placeholder('Descripción de la partida...')
                                                    ->prefixIcon('heroicon-o-document-text')
                                                    ->columnSpanFull(),

                                                TextInput::make('referencia')
                                                    ->label('Referencia')
                                                    ->maxLength(100)
                                                    ->placeholder('Referencia interna...')
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->columnSpanFull(),
                                            ])
                                            ->defaultItems(2)
                                            ->collapsible()
                                            ->cloneable()
                                            ->addActionLabel('➕ Agregar Partida')
                                            ->reorderable()
                                            ->columnSpanFull()
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                                // Agregar empresa y sucursal al detalle
                                                $data['empresa_id'] = $data['empresa_id'] ?? Auth::user()?->empresa_id;
                                                $data['sucursal_id'] = $data['sucursal_id'] ?? Auth::user()?->sucursal_id;

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
                                                    ->content(fn ($record) => $record?->creador?->name ?? 'N/A'),

                                                Placeholder::make('empresa')
                                                    ->label('Empresa')
                                                    ->content(fn ($record) => $record?->empresa?->nombre_comercial ?: $record?->empresa?->razon_social ?? 'N/A'),

                                                Placeholder::make('sucursal')
                                                    ->label('Sucursal')
                                                    ->content(fn ($record) => $record?->sucursal?->nombre ?? 'N/A'),

                                                Placeholder::make('autorizado_por')
                                                    ->label('Autorizado por')
                                                    ->content(fn ($record) => $record?->autorizador?->name ?? 'N/A'),

                                                Placeholder::make('fecha_autorizacion')
                                                    ->label('Fecha autorización')
                                                    ->content(fn ($record) => $record?->fecha_autorizacion?->format('d/m/Y H:i') ?? 'N/A'),
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
        $isAdmin = Auth::user()?->hasRole('admin') || Auth::user()?->hasRole('super_admin');

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

                TextColumn::make('numero_asiento')
                    ->label('N° Asiento')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('empresa.nombre_comercial')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state, $record) => $record->empresa?->nombre_comercial ?: $record->empresa?->razon_social ?? 'N/A')
                    ->visible($isAdmin)
                    ->placeholder('-'),

                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible($isAdmin)
                    ->placeholder('-'),

                BadgeColumn::make('tipo')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'apertura' => '📂 Apertura',
                        'cierre' => '🔒 Cierre',
                        'diario' => '📋 Diario',
                        'compra' => '🛒 Compra',
                        'venta' => '💰 Venta',
                        'ingreso' => '📥 Ingreso',
                        'egreso' => '📤 Egreso',
                        'ajuste' => '⚙️ Ajuste',
                        'depreciacion' => '📉 Depreciación',
                        'inventario' => '📦 Inventario',
                        'conciliacion' => '🔄 Conciliación',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'diario',
                        'success' => 'apertura',
                        'danger' => 'cierre',
                        'info' => 'compra',
                        'primary' => 'venta',
                        'success' => 'ingreso',
                        'danger' => 'egreso',
                        'warning' => 'ajuste',
                        'info' => 'depreciacion',
                        'gray' => 'inventario',
                        'gray' => 'conciliacion',
                    ])
                    ->toggleable(),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'borrador' => '📝 Borrador',
                        'confirmado' => '✅ Confirmado',
                        'anulado' => '❌ Anulado',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'borrador',
                        'success' => 'confirmado',
                        'danger' => 'anulado',
                    ])
                    ->toggleable(),

                TextColumn::make('total_debe')
                    ->label('Total Debe')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->prefix('$')
                    ->color('info'),

                TextColumn::make('total_haber')
                    ->label('Total Haber')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->prefix('$')
                    ->color('danger'),

                TextColumn::make('documento_codigo')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Filtro por empresa
                SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'nombre_comercial')
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::user()?->empresa_id)
                    ->visible($isAdmin),

                // Filtro por sucursal
                SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre')
                    ->searchable()
                    ->preload()
                    ->default(fn () => Auth::user()?->sucursal_id)
                    ->visible($isAdmin),

                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'apertura' => 'Apertura',
                        'cierre' => 'Cierre',
                        'diario' => 'Diario',
                        'compra' => 'Compra',
                        'venta' => 'Venta',
                        'ingreso' => 'Ingreso',
                        'egreso' => 'Egreso',
                        'ajuste' => 'Ajuste',
                        'depreciacion' => 'Depreciación',
                        'inventario' => 'Inventario',
                        'conciliacion' => 'Conciliación',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'borrador' => 'Borrador',
                        'confirmado' => 'Confirmado',
                        'anulado' => 'Anulado',
                    ])
                    ->searchable()
                    ->preload(),

                Filter::make('fecha_asiento')
                    ->label('Rango de Fechas')
                    ->form([
                        DatePicker::make('fecha_desde')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('fecha_hasta')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['fecha_desde'], fn ($q, $fecha) => $q->whereDate('fecha_asiento', '>=', $fecha))
                            ->when($data['fecha_hasta'], fn ($q, $fecha) => $q->whereDate('fecha_asiento', '<=', $fecha));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl')
                        ->visible(fn ($record) => $record->estado !== 'confirmado'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('confirmar')
                        ->label('Confirmar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            try {
                                $record->confirmar();
                                Notification::make()
                                    ->title('Asiento confirmado exitosamente')
                                    ->body('El asiento '.$record->codigo.' ha sido confirmado.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Error al confirmar')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn ($record) => $record->estado === 'borrador' && $record->esta_balanceado),

                    Tables\Actions\Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Textarea::make('motivo')
                                ->label('Motivo de anulación')
                                ->rows(2)
                                ->placeholder('Indique el motivo...'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->anular($data['motivo'] ?? null);
                            Notification::make()
                                ->title('Asiento anulado')
                                ->body('El asiento '.$record->codigo.' ha sido anulado.')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->estado === 'confirmado'),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn ($record) => $record->estado === 'borrador'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->defaultSort('fecha_asiento', 'desc')
            ->searchPlaceholder('Buscar asiento...')
            ->emptyStateHeading('No hay asientos contables registrados')
            ->emptyStateDescription('Crea un asiento contable para comenzar.')
            ->emptyStateIcon('heroicon-o-document-duplicate')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAsientoContables::route('/'),
            'create' => Pages\CreateAsientoContable::route('/create'),
            'edit' => Pages\EditAsientoContable::route('/{record}/edit'),
        ];
    }
}
