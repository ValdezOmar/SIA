<?php

namespace App\Filament\Resources\Contabilidad;

use App\Filament\Resources\Contabilidad\PlanCuentaResource\Pages;
use App\Models\Contabilidad\PlanCuenta;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class PlanCuentaResource extends Resource
{
    protected static ?string $model = PlanCuenta::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Contabilidad';

    protected static ?string $navigationLabel = 'Plan de Cuentas';

    protected static ?string $modelLabel = 'Cuenta';

    protected static ?string $pluralModelLabel = 'Plan de Cuentas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Gestión de Cuenta')
                    ->tabs([
                        Tabs\Tab::make('📋 General')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Datos de la Cuenta')
                                    ->icon('heroicon-o-identification')
                                    ->description('Información principal de la cuenta contable')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->label('Código')
                                                    ->required()
                                                    ->maxLength(20)
                                                    ->unique(ignoreRecord: true)
                                                    ->placeholder('1.1.1')
                                                    ->helperText('Código único de la cuenta')
                                                    ->prefixIcon('heroicon-o-hashtag')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        $padreId = $get('cuenta_padre_id');
                                                        if ($padreId) {
                                                            $padre = PlanCuenta::find($padreId);
                                                            if ($padre) {
                                                                $set('trayectoria', $padre->trayectoria . '.' . $padre->id);
                                                                $set('nivel', $padre->nivel + 1);
                                                            }
                                                        }
                                                    }),

                                                TextInput::make('nombre')
                                                    ->label('Nombre')
                                                    ->required()
                                                    ->maxLength(255)
                                                    ->placeholder('Caja, Bancos, Clientes, etc.')
                                                    ->helperText('Nombre descriptivo de la cuenta')
                                                    ->prefixIcon('heroicon-o-document-text'),

                                                TextInput::make('nombre_completo')
                                                    ->label('Nombre Completo')
                                                    ->maxLength(255)
                                                    ->placeholder('Caja General, Banco XX, etc.')
                                                    ->helperText('Nombre completo de la cuenta'),
                                            ]),

                                        Grid::make(3)
                                            ->schema([
                                                Select::make('cuenta_padre_id')
                                                    ->label('Cuenta Padre')
                                                    ->options(
                                                        fn() => PlanCuenta::whereNull('cuenta_padre_id')
                                                            ->orWhere('id', request()->route('record'))
                                                            ->orderBy('codigo')
                                                            ->get()
                                                            ->mapWithKeys(fn($item) => [
                                                                $item->id => $item->codigo . ' - ' . $item->nombre
                                                            ])
                                                            ->toArray()
                                                    )
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Seleccione una cuenta padre')
                                                    ->helperText('Cuenta superior en la jerarquía')
                                                    ->prefixIcon('heroicon-o-arrow-up')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set, $get) {
                                                        if ($state) {
                                                            $padre = PlanCuenta::find($state);
                                                            if ($padre) {
                                                                $set('trayectoria', $padre->trayectoria . '.' . $padre->id);
                                                                $set('nivel', $padre->nivel + 1);
                                                                $set('tipo_cuenta', $padre->tipo_cuenta);
                                                                $set('naturaleza', $padre->naturaleza);
                                                            }
                                                        }
                                                    }),

                                                TextInput::make('nivel')
                                                    ->label('Nivel')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->default(1)
                                                    ->helperText('Nivel jerárquico de la cuenta')
                                                    ->prefixIcon('heroicon-o-numbered-list'),

                                                TextInput::make('trayectoria')
                                                    ->label('Trayectoria')
                                                    ->disabled()
                                                    ->helperText('Ruta jerárquica de la cuenta')
                                                    ->prefixIcon('heroicon-o-arrow-path'),
                                            ]),
                                    ]),

                                Section::make('Clasificación')
                                    ->icon('heroicon-o-tag')
                                    ->schema([
                                        Grid::make(3)
                                            ->schema([
                                                Select::make('tipo_cuenta')
                                                    ->label('Tipo de Cuenta')
                                                    ->options([
                                                        'activo' => '🟢 Activo',
                                                        'pasivo' => '🔴 Pasivo',
                                                        'patrimonio' => '🔵 Patrimonio',
                                                        'ingreso' => '🟡 Ingreso',
                                                        'gasto' => '🟠 Gasto',
                                                        'costo' => '🟣 Costo',
                                                    ])
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Clasificación contable de la cuenta')
                                                    ->prefixIcon('heroicon-o-tag')
                                                    ->reactive()
                                                    ->afterStateUpdated(function ($state, callable $set) {
                                                        $naturaleza = match($state) {
                                                            'activo', 'gasto', 'costo' => 'deudora',
                                                            'pasivo', 'patrimonio', 'ingreso' => 'acreedora',
                                                            default => 'deudora'
                                                        };
                                                        $set('naturaleza', $naturaleza);
                                                    }),

                                                Select::make('naturaleza')
                                                    ->label('Naturaleza')
                                                    ->options([
                                                        'deudora' => '🟦 Deudora',
                                                        'acreedora' => '🟥 Acreedora',
                                                    ])
                                                    ->required()
                                                    ->searchable()
                                                    ->helperText('Naturaleza de la cuenta (Debe/Haber)')
                                                    ->prefixIcon('heroicon-o-arrow-path')
                                                    ->disabled(),

                                                Select::make('tipo_detalle')
                                                    ->label('Tipo de Detalle')
                                                    ->options([
                                                        'general' => '📋 General',
                                                        'auxiliar' => '📊 Auxiliar',
                                                        'analitica' => '📈 Analítica',
                                                        'control' => '🎯 Control',
                                                        'ajuste' => '⚙️ Ajuste',
                                                    ])
                                                    ->default('general')
                                                    ->searchable()
                                                    ->helperText('Nivel de detalle de la cuenta')
                                                    ->prefixIcon('heroicon-o-document-text'),
                                            ]),
                                    ]),

                                Section::make('Configuración')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Activa')
                                                    ->default(true)
                                                    ->helperText('Cuenta disponible para uso'),

                                                Toggle::make('es_control')
                                                    ->label('Cuenta de Control')
                                                    ->helperText('Cuenta de control/gestión'),

                                                Toggle::make('es_analitica')
                                                    ->label('Cuenta Analítica')
                                                    ->helperText('Permite análisis detallado'),

                                                Toggle::make('permite_movimiento')
                                                    ->label('Permite Movimientos')
                                                    ->default(true)
                                                    ->helperText('Permite registrar movimientos en esta cuenta'),
                                            ]),

                                        Grid::make(2)
                                            ->schema([
                                                Toggle::make('requiere_centro_costo')
                                                    ->label('Requiere Centro de Costo')
                                                    ->helperText('Obligatorio asignar centro de costo'),

                                                Toggle::make('requiere_proyecto')
                                                    ->label('Requiere Proyecto')
                                                    ->helperText('Obligatorio asignar proyecto'),
                                            ]),
                                    ]),

                                Textarea::make('descripcion')
                                    ->label('Descripción')
                                    ->rows(3)
                                    ->placeholder('Descripción detallada de la cuenta...')
                                    ->helperText('Información adicional sobre la cuenta')
                                    ->columnSpanFull(),
                            ]),

                        Tabs\Tab::make('📊 Saldos')
                            ->icon('heroicon-o-chart-bar')
                            ->schema([
                                Section::make('Resumen de Saldos')
                                    ->icon('heroicon-o-chart-bar')
                                    ->schema([
                                        Placeholder::make('saldos_info')
                                            ->label('')
                                            ->content(function ($record) {
                                                if (!$record) {
                                                    return 'Los saldos se mostrarán después de guardar la cuenta.';
                                                }

                                                $saldos = $record->saldos()
                                                    ->orderBy('anio', 'desc')
                                                    ->orderBy('mes', 'desc')
                                                    ->limit(12)
                                                    ->get();

                                                if ($saldos->isEmpty()) {
                                                    return new HtmlString(
                                                        '<div class="text-sm text-gray-500">No hay saldos registrados para esta cuenta.</div>'
                                                    );
                                                }

                                                $html = '<div class="overflow-x-auto">';
                                                $html .= '<table class="min-w-full divide-y divide-gray-200">';
                                                $html .= '<thead><tr>';
                                                $html .= '<th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período</th>';
                                                $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debe</th>';
                                                $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Haber</th>';
                                                $html .= '<th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>';
                                                $html .= '</tr></thead>';
                                                $html .= '<tbody class="divide-y divide-gray-200">';

                                                foreach ($saldos as $saldo) {
                                                    $nombreMes = \DateTime::createFromFormat('!m', $saldo->mes)->format('F');
                                                    $saldoFinal = $record->naturaleza === 'deudora' 
                                                        ? $saldo->saldo_final_debe - $saldo->saldo_final_haber
                                                        : $saldo->saldo_final_haber - $saldo->saldo_final_debe;
                                                    $color = $saldoFinal > 0 ? 'text-green-600' : ($saldoFinal < 0 ? 'text-red-600' : 'text-gray-500');

                                                    $html .= '<tr>';
                                                    $html .= '<td class="px-4 py-2 text-sm">' . $nombreMes . ' ' . $saldo->anio . '</td>';
                                                    $html .= '<td class="px-4 py-2 text-sm text-right">' . number_format($saldo->saldo_final_debe, 2) . '</td>';
                                                    $html .= '<td class="px-4 py-2 text-sm text-right">' . number_format($saldo->saldo_final_haber, 2) . '</td>';
                                                    $html .= '<td class="px-4 py-2 text-sm text-right font-bold ' . $color . '">' . number_format($saldoFinal, 2) . '</td>';
                                                    $html .= '</tr>';
                                                }

                                                $html .= '</tbody></table></div>';
                                                return new HtmlString($html);
                                            })
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('📝 Auditoría')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Información de Auditoría')
                                    ->icon('heroicon-o-clock')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Placeholder::make('creado_por')
                                                    ->label('Creado por')
                                                    ->content(fn($record) => $record?->creador?->name ?? 'N/A'),

                                                Placeholder::make('created_at')
                                                    ->label('Fecha creación')
                                                    ->content(fn($record) => $record?->created_at?->format('d/m/Y H:i') ?? 'N/A'),
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

                TextColumn::make('nombre')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->limit(40),

                TextColumn::make('cuentaPadre.nombre')
                    ->label('Cuenta Padre')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('-')
                    ->color('gray'),

                TextColumn::make('nivel')
                    ->label('Nivel')
                    ->badge()
                    ->color('info')
                    ->sortable()
                    ->toggleable()
                    ->width('60px'),

                TextColumn::make('tipo_cuenta')
                    ->label('Tipo')
                    ->formatStateUsing(fn($state) => match($state) {
                        'activo' => '🟢 Activo',
                        'pasivo' => '🔴 Pasivo',
                        'patrimonio' => '🔵 Patrimonio',
                        'ingreso' => '🟡 Ingreso',
                        'gasto' => '🟠 Gasto',
                        'costo' => '🟣 Costo',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn($state) => match($state) {
                        'activo' => 'success',
                        'pasivo' => 'danger',
                        'patrimonio' => 'primary',
                        'ingreso' => 'warning',
                        'gasto' => 'danger',
                        'costo' => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('naturaleza')
                    ->label('Naturaleza')
                    ->formatStateUsing(fn($state) => $state === 'deudora' ? '🟦 Deudora' : '🟥 Acreedora')
                    ->badge()
                    ->color(fn($state) => $state === 'deudora' ? 'info' : 'danger')
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
            ])
            ->filters([
                SelectFilter::make('tipo_cuenta')
                    ->label('Tipo de Cuenta')
                    ->options([
                        'activo' => 'Activo',
                        'pasivo' => 'Pasivo',
                        'patrimonio' => 'Patrimonio',
                        'ingreso' => 'Ingreso',
                        'gasto' => 'Gasto',
                        'costo' => 'Costo',
                    ])
                    ->searchable()
                    ->preload(),

                SelectFilter::make('nivel')
                    ->label('Nivel')
                    ->options([
                        1 => 'Nivel 1',
                        2 => 'Nivel 2',
                        3 => 'Nivel 3',
                        4 => 'Nivel 4',
                        5 => 'Nivel 5',
                    ])
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas')
                    ->placeholder('Todas'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\ViewAction::make()
                        ->slideOver()
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-power')
                        ->color(fn($record) => $record->activo ? 'warning' : 'success')
                        ->action(function ($record) {
                            $record->update(['activo' => !$record->activo]);
                            \Filament\Notifications\Notification::make()
                                ->title($record->activo ? 'Cuenta activada' : 'Cuenta desactivada')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => !$record->tieneMovimientos()),
                ])
                    ->tooltip('Acciones')
                    ->icon('heroicon-o-ellipsis-vertical'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('codigo')
            ->searchPlaceholder('Buscar cuenta por código, nombre...')
            ->emptyStateHeading('No hay cuentas registradas')
            ->emptyStateDescription('Crea tu plan de cuentas para comenzar.')
            ->emptyStateIcon('heroicon-o-document-chart-bar')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanCuentas::route('/'),
            'create' => Pages\CreatePlanCuenta::route('/create'),
            'edit' => Pages\EditPlanCuenta::route('/{record}/edit'),
        ];
    }
}