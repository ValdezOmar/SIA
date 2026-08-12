<?php

namespace App\Filament\Resources\Contabilidad;

use App\Filament\Resources\Contabilidad\ProyectoResource\Pages;
use App\Models\Contabilidad\Proyecto;
use App\Models\Ventas\Cliente;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Datos del Proyecto')
                    ->icon('heroicon-o-flag')
                    ->description('Información del proyecto')
                    ->schema([
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