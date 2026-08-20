<?php

namespace App\Filament\Resources\Contabilidad;

use App\Filament\Resources\Contabilidad\ProyectoResource\Pages;
use App\Models\Contabilidad\Proyecto;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\Ventas\Cliente;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class ProyectoResource extends Resource
{
    protected static ?string $model = Proyecto::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Proyectos';

    protected static ?string $modelLabel = 'Proyecto';

    protected static ?string $pluralModelLabel = 'Proyectos';

    protected static ?int $navigationSort = 4;

    private static function formatearMonto($monto): string
    {
        return 'Bs ' . number_format($monto ?? 0, 2);
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
            ->when($defaultEmpresaId, fn($query) => $query->where('empresa_id', $defaultEmpresaId))
            ->value('id');

        return $form
            ->schema([
                Section::make('Datos del Proyecto')
                    ->icon('heroicon-o-flag')
                    ->description('Información del proyecto')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('empresa_id')
                                    ->label('Empresa')
                                    ->options(function () {
                                        return Empresa::query()
                                            ->orderByRaw('COALESCE(nombre_comercial, razon_social)')
                                            ->get()
                                            ->mapWithKeys(fn($empresa) => [
                                                $empresa->id => $empresa->nombre_comercial ?: $empresa->razon_social,
                                            ])
                                            ->toArray();
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => $defaultEmpresaId)
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
                                    ->disabled(!$isAdmin)
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
                                    ->default(fn() => $defaultSucursalId)
                                    ->disabled(!$isAdmin)
                                    ->dehydrated()
                                    ->visible($isAdmin)
                                    ->required(),

                                Hidden::make('empresa_id')
                                    ->default(fn() => Auth::user()?->empresa_id ?: $defaultEmpresaId)
                                    ->visible(!$isAdmin)
                                    ->dehydrated(),

                                Hidden::make('sucursal_id')
                                    ->default(fn() => Auth::user()?->sucursal_id ?: $defaultSucursalId)
                                    ->visible(!$isAdmin)
                                    ->dehydrated(),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('codigo')
                                    ->label('Código')
                                    ->required()
                                    ->maxLength(20)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('PROY-001')
                                    ->helperText('Código único del proyecto')
                                    ->prefixIcon('heroicon-o-hashtag'),

                                TextInput::make('nombre')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Proyecto de Desarrollo, Implementación, etc.')
                                    ->helperText('Nombre del proyecto')
                                    ->prefixIcon('heroicon-o-document-text'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                DatePicker::make('fecha_inicio')
                                    ->label('Fecha Inicio')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->helperText('Fecha de inicio del proyecto')
                                    ->prefixIcon('heroicon-o-calendar'),

                                DatePicker::make('fecha_fin')
                                    ->label('Fecha Fin')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->helperText('Fecha de finalización del proyecto')
                                    ->prefixIcon('heroicon-o-calendar-days'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('estado')
                                    ->label('Estado')
                                    ->options([
                                        'planeacion' => '📋 Planeación',
                                        'activo' => '✅ Activo',
                                        'pausado' => '⏸️ Pausado',
                                        'finalizado' => '🏁 Finalizado',
                                        'cancelado' => '❌ Cancelado',
                                    ])
                                    ->default('planeacion')
                                    ->required()
                                    ->searchable()
                                    ->helperText('Estado del proyecto')
                                    ->prefixIcon('heroicon-o-tag'),

                                Select::make('responsable_id')
                                    ->label('Responsable')
                                    ->relationship('responsable', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->default(fn() => Auth::id())
                                    ->placeholder('Seleccione un responsable')
                                    ->helperText('Responsable del proyecto')
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Select::make('cliente_id')
                                    ->label('Cliente')
                                    ->options(
                                        fn() => Cliente::where('activo', true)
                                            ->when(Auth::user()?->empresa_id, fn($q) => 
                                                $q->where('empresa_id', Auth::user()->empresa_id)
                                            )
                                            ->pluck('nombre', 'id')
                                            ->toArray()
                                    )
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Seleccione un cliente')
                                    ->helperText('Cliente asociado al proyecto')
                                    ->prefixIcon('heroicon-o-user-group'),

                                TextInput::make('presupuesto')
                                    ->label('Presupuesto')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1.00)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefix('$')
                                    ->helperText('Presupuesto total del proyecto'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('gastado')
                                    ->label('Gastado')
                                    ->numeric()
                                    ->minValue(0)
                                    ->step(1.00)
                                    ->default(0)
                                    ->placeholder('0.00')
                                    ->prefix('$')
                                    ->disabled()
                                    ->helperText('Monto gastado del proyecto'),

                                Placeholder::make('saldo')
                                    ->label('Saldo Disponible')
                                    ->content(function ($get) {
                                        $presupuesto = floatval($get('presupuesto') ?? 0);
                                        $gastado = floatval($get('gastado') ?? 0);
                                        $saldo = $presupuesto - $gastado;
                                        $color = $saldo >= 0 ? 'text-success-600' : 'text-danger-600';
                                        return new HtmlString(
                                            '<span class="font-bold ' . $color . '">' .
                                                self::formatearMonto($saldo) .
                                                '</span>'
                                        );
                                    }),
                            ]),

                        Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Descripción del proyecto...')
                            ->helperText('Información adicional sobre el proyecto')
                            ->columnSpanFull(),
                    ]),
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
                    ->toggleable()
                    ->width('120px')
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(30),

                BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn($state) => match($state) {
                        'planeacion' => '📋 Planeación',
                        'activo' => '✅ Activo',
                        'pausado' => '⏸️ Pausado',
                        'finalizado' => '🏁 Finalizado',
                        'cancelado' => '❌ Cancelado',
                        default => $state,
                    })
                    ->colors([
                        'gray' => 'planeacion',
                        'success' => 'activo',
                        'warning' => 'pausado',
                        'info' => 'finalizado',
                        'danger' => 'cancelado',
                    ])
                    ->toggleable(),

                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-'),

                TextColumn::make('empresa.nombre_comercial')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn($state, $record) => $record->empresa?->nombre_comercial ?: $record->empresa?->razon_social ?? 'N/A')
                    ->visible($isAdmin)
                    ->placeholder('-'),

                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible($isAdmin)
                    ->placeholder('-'),

                TextColumn::make('presupuesto')
                    ->label('Presupuesto')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->prefix('$'),

                TextColumn::make('gastado')
                    ->label('Gastado')
                    ->numeric(2)
                    ->sortable()
                    ->toggleable()
                    ->prefix('$')
                    ->color('danger'),

                TextColumn::make('saldo')
                    ->label('Saldo')
                    ->numeric(2)
                    ->sortable()
                    ->getStateUsing(fn($record) => ($record->presupuesto ?? 0) - ($record->gastado ?? 0))
                    ->toggleable()
                    ->prefix('$')
                    ->color(fn($state) => $state >= 0 ? 'success' : 'danger'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                // Filtro por empresa
                SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'nombre_comercial')
                    ->searchable()
                    ->preload()
                    ->default(fn() => Auth::user()?->empresa_id)
                    ->visible($isAdmin),

                // Filtro por sucursal
                SelectFilter::make('sucursal_id')
                    ->label('Sucursal')
                    ->relationship('sucursal', 'nombre')
                    ->searchable()
                    ->preload()
                    ->default(fn() => Auth::user()?->sucursal_id)
                    ->visible($isAdmin),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'planeacion' => 'Planeación',
                        'activo' => 'Activo',
                        'pausado' => 'Pausado',
                        'finalizado' => 'Finalizado',
                        'cancelado' => 'Cancelado',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('responsable_id')
                    ->label('Responsable')
                    ->relationship('responsable', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('4xl'),

                    Tables\Actions\Action::make('cambiar_estado')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->form([
                            Select::make('estado')
                                ->label('Nuevo Estado')
                                ->options([
                                    'planeacion' => '📋 Planeación',
                                    'activo' => '✅ Activo',
                                    'pausado' => '⏸️ Pausado',
                                    'finalizado' => '🏁 Finalizado',
                                    'cancelado' => '❌ Cancelado',
                                ])
                                ->required(),
                            Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->rows(2)
                                ->placeholder('Motivo del cambio de estado...'),
                        ])
                        ->action(function (array $data, $record) {
                            $record->update(['estado' => $data['estado']]);
                            Notification::make()
                                ->title('Estado actualizado')
                                ->body('El proyecto ahora está en estado: ' . ucfirst($data['estado']))
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => $record->estado === 'planeacion'),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Buscar proyecto...')
            ->emptyStateHeading('No hay proyectos registrados')
            ->emptyStateDescription('Crea un proyecto para comenzar.')
            ->emptyStateIcon('heroicon-o-flag')
            ->poll('60s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProyectos::route('/'),
            'create' => Pages\CreateProyecto::route('/create'),
            'edit' => Pages\EditProyecto::route('/{record}/edit'),
        ];
    }
}