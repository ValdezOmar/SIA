<?php

namespace App\Filament\Resources\Almacen;

use App\Filament\Resources\Almacen\InventarioResource\Pages;
use App\Models\Almacen\Inventario;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Filament\Forms\Components\Select;
use Filament\Forms\Set;
use Filament\Forms\Components\View;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class InventarioResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Inventario::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $modelLabel = 'Inventario';
    protected static ?string $pluralModelLabel = 'Listado del inventario actual';
    protected static ?string $navigationLabel = 'Inventario';
    protected static ?string $navigationGroup = 'Almacenes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                View::make('filament.forms.components.inventario-card')
                    ->columnSpanFull(),

                Section::make('Datos Correctos')
                    ->description('Ingrese los datos correctos en caso de encontrar discrepancias para una posterior actualizacion y corrección en sistema')
                    ->schema([
                        TextInput::make('codigo_correcto')
                            ->label('Código')
                            ->prefixIcon('heroicon-o-table-cells')
                            ->afterStateUpdated(fn($state, Set $set) => $set('codigo_correcto', strtoupper($state))),

                        TextInput::make('descripcion_correcto')
                            ->label('Descripción')
                            ->prefixIcon('heroicon-o-document-text')
                            ->afterStateUpdated(fn($state, Set $set) => $set('descripcion_correcto', strtoupper($state))),

                        TextInput::make('presentacion_correcto')
                            ->label('Presentación')
                            ->prefixIcon('heroicon-o-cube')
                            ->afterStateUpdated(fn($state, Set $set) => $set('presentacion_correcto', strtoupper($state))),

                        TextInput::make('unidad_correcto')
                            ->label('Unidad de medida')
                            ->prefixIcon('heroicon-o-scale')
                            ->afterStateUpdated(fn($state, Set $set) => $set('unidad_correcto', strtoupper($state))),

                        TextInput::make('codigo_alterno_correcto')
                            ->label('Código alterno')
                            ->prefixIcon('heroicon-o-qr-code')
                            ->afterStateUpdated(fn($state, Set $set) => $set('codigo_alterno_correcto', strtoupper($state))),

                        TextInput::make('cod_almacen_correcto')
                            ->label('Código de almacén')
                            ->prefixIcon('heroicon-o-home-modern')
                            ->afterStateUpdated(fn($state, Set $set) => $set('cod_almacen_correcto', strtoupper($state))),

                        TextInput::make('nombre_almacen_correcto')
                            ->label('Nombre del almacén')
                            ->prefixIcon('heroicon-o-building-storefront')
                            ->afterStateUpdated(fn($state, Set $set) => $set('nombre_almacen_correcto', strtoupper($state))),

                        TextInput::make('lote_correcto')
                            ->label('Lote')
                            ->prefixIcon('heroicon-o-tag')
                            ->afterStateUpdated(fn($state, Set $set) => $set('lote_correcto', strtoupper($state))),

                        DatePicker::make('fecha_ven_correcto')
                            ->label('Fecha de vencimiento')
                            ->prefixIcon('heroicon-o-calendar'),

                        Select::make('empresa_correcto')
                            ->label('Empresa')
                            ->prefixIcon('heroicon-o-building-office')
                            ->options([
                                'Novanexa' => 'Novanexa',
                                'Requilab' => 'Requilab',
                                'Ireilab' => 'Ireilab',
                            ])
                            ->searchable()
                    ])
                    ->columns(3),

                Section::make('Conteo de Inventario Físico')
                    ->description('Registro de la comparación entre el sistema y el conteo físico')
                    ->schema([
                        TextInput::make('saldo_actual')
                            ->label('Saldo en sistema')
                            ->prefixIcon('heroicon-o-circle-stack') // Icono para datos del sistema
                            ->disabled()
                            ->columnSpan(1)
                            ->extraInputAttributes(['class' => 'font-mono text-gray-900 dark:text-white bg-gray-50 dark:bg-gray-700']),

                        TextInput::make('saldo_contado')
                            ->label('Saldo contado físicamente')
                            ->prefixIcon('heroicon-o-clipboard-document-check') // Icono de verificación
                            ->required()
                            ->numeric()
                            ->columnSpan(1)
                            ->rules(['gt:0'])
                            ->hint('Ingrese solo números')
                            ->extraInputAttributes(['class' => 'font-bold text-primary-600 dark:text-primary-400']),

                        BarcodeInput::make('sn_qr_correcto')
                            ->label('Registrar QR')
                            ->live()
                            ->required(false)
                            ->dehydrated(fn($state) => filled($state)) // Solo guardar si tiene valor
                            ->afterStateUpdated(fn($state, $set) => $set('sn_qr_correcto', $state))
                    ])
                    ->columns(3),
                Forms\Components\Textarea::make('observacion')
                    ->label('Observaciones')
                    ->hint('Ingrese todas las observaciones adicionales encontradas para este ítem')
                    // ->icon('heroicon-o-clipboard-document-list') // Icono intuitivo
                    ->rows(5) // Más espacio para escribir
                    ->maxLength(255) // Más capacidad
                    ->columnSpanFull(255)
                    ->extraAttributes([
                        'class' => 'custom-textarea', // Clase para estilos personalizados
                    ]),

                Section::make('Datos adicionales del sistema')
                    ->description('Información técnica del sistema')
                    ->hidden()
                    ->schema([
                        DatePicker::make('fecha_conteo_inventario')->label('Fecha de conteo'),
                        Toggle::make('activo')->label('Activo'),
                        TextInput::make('usuario')->label('Usuario responsable'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Item Almacén')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div>
                            <strong>{$record->descripcion}</strong><br>
                            <small>Codigo: <strong style='color:rgb(32, 115, 211); font-size: 0.80rem'>{$record->codigo}</strong><br>Cod. Alterno: <strong >{$record->codigo_alterno}</strong></small>
                        </div>
                    ")
                    ->searchable(['descripcion', 'codigo', 'codigo_alterno']),

                TextColumn::make('saldo_actual')
                    ->label('Conteo')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div style='text-align: left;'>          
                                <span style='font-size: 0.7rem; color: #6b7280;'>Almacén:</span>
                                <span style='font-size: 0.9rem; font-weight: 800;'>{$record->saldo_actual}</span> <br> 
                            " . ($record->saldo_contado ? "                               
                                    <span style='font-size: 0.7rem; color: #6b7280;'>Conteo :</span>
                                    <span style='font-size: 0.9rem; font-weight: 800;'>{$record->saldo_contado}</span><br>     
                                    <span style='font-size: 0.7rem; color: #6b7280;'>Diferencia:</span>
                                    <span style='font-size: 0.9rem; font-weight: 800; color: " . (($record->saldo_actual - $record->saldo_contado) != 0 ? '#dc2626' : '#16a34a') . ";'>
                                        " . ($record->saldo_actual - $record->saldo_contado) . "
                                    </span><br>
                                    <span style='
                                        background-color: #16a34a;
                                        color: white;
                                        padding: 0.25rem 0.5rem;
                                        border-radius: 0.25rem;
                                        font-size: 0.75rem;
                                        font-weight: 500;
                                    '>Verificado</span>                                
                            " : "                                
                                    <span style='
                                        background-color: #dc2626;
                                        color: white;
                                        padding: 0.25rem 0.5rem;
                                        border-radius: 0.25rem;
                                        font-size: 0.75rem;
                                        font-weight: 500;
                                    '>Sin contar</span>
                                
                            ") . "
                        </div>
                    ")
                    ->sortable(),

                TextColumn::make('lote')
                    ->label('Lote')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div>
                            <strong>{$record->lote}</strong><br>
                            <small>Presentacion: {$record->presentacion}</small><br>
                            <small>Unidad: {$record->unidad}</small>
                        </div>
                    ")
                    ->sortable()
                    ->searchable(['lote']),

                TextColumn::make('nombre_almacen')
                    ->label('Ubicación en Almacén')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $sucursal = match (true) {
                            $record->cod_almacen >= 100 && $record->cod_almacen <= 199 => 'La Paz',
                            $record->cod_almacen >= 200 && $record->cod_almacen <= 299 => 'Cochabamba',
                            $record->cod_almacen >= 300 && $record->cod_almacen <= 399 => 'Santa Cruz',
                            $record->cod_almacen >= 400 && $record->cod_almacen <= 499 => 'Sucre',
                            $record->cod_almacen >= 500 && $record->cod_almacen <= 599 => 'Tarija',
                            default => 'Sucursal desconocida',
                        };

                        return "
                            <div>
                                <strong>{$record->nombre_almacen}</strong><br>
                                <small>Cod. Almacén: <strong style='font-size: 0.85rem'>{$record->cod_almacen}</strong></small><br>
                                <small><strong style='text-align: center; font-size: 0.85rem'>{$record->empresa}</strong></small><br>
                                <small style='color: gray; font-size: 0.8rem'><strong>{$sucursal}</strong></small>
                            </div>
                        ";
                    })
                    ->searchable(['nombre_almacen', 'cod_almacen', 'empresa']),

                TextColumn::make('fecha_ven')
                    ->label('Vencimiento')
                    ->html()
                    ->getStateUsing(function ($record) {
                        if (!$record->fecha_ven) {
                            return <<<HTML
                                <div>Sin fecha</div>
                                <div style="color: rgb(111, 107, 128); font-size: 0.75rem">Sin registro</div>
                            HTML;
                        }

                        $fechaFormateada = \Carbon\Carbon::parse($record->fecha_ven)->format('d/m/Y');
                        $hoy = \Carbon\Carbon::now();
                        $mesesRestantes = (int)$hoy->floatDiffInMonths($record->fecha_ven, false);

                        // Definimos el texto y color según los meses restantes (enteros)
                        $estado = match (true) {
                            $mesesRestantes <= 0 => [  // Cambiado de < 0 a <= 0 para incluir el mes actual
                                'texto' => 'VENCIDO',
                                'color' => '#dc2626' // Rojo
                            ],
                            $mesesRestantes <= 4 => [
                                'texto' => "VENCE EN {$mesesRestantes} " . ($mesesRestantes == 1 ? 'MES' : 'MESES'),
                                'color' => '#ea580c' // Naranja
                            ],
                            $mesesRestantes <= 8 => [
                                'texto' => "VENCE EN {$mesesRestantes} MESES",
                                'color' => '#d97706' // Amarillo
                            ],
                            default => [
                                'texto' => "VENCE EN {$mesesRestantes} MESES",
                                'color' => '#16a34a' // Verde
                            ]
                        };

                        return <<<HTML
                            <div>{$fechaFormateada}</div>
                            <div style="color: {$estado['color']}; font-size: 0.75rem; font-weight: 500">
                                {$estado['texto']}
                            </div>
                        HTML;
                    })
                    ->sortable(),


            ])
            ->filters([
                //Filtro de estado de conteo
                SelectFilter::make('estado_conteo')
                    ->label('Estado de conteo')
                    ->options([
                        'verificado' => 'Verificados',
                        'sin_contar' => 'Sin contar',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] === 'verificado') {
                            return $query->whereNotNull('saldo_contado');
                        }
                        if ($data['value'] === 'sin_contar') {
                            return $query->whereNull('saldo_contado');
                        }
                        return $query;
                    }),
                //Filtro de busqueda de Empresas
                SelectFilter::make('empresas')
                    ->label('Filtrar por Empresa')
                    ->multiple()
                    ->options(function () {
                        return Inventario::query()
                            ->select('empresa')
                            ->whereNotNull('empresa')
                            ->distinct()
                            ->orderBy('empresa')
                            ->pluck('empresa', 'empresa')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $state) {
                        if (!empty($state['values'])) {
                            $query->whereIn('empresa', $state['values']);
                        }
                    })
                    ->searchable(),
                SelectFilter::make('estado_vencimiento')
                    ->label('Estado de Vencimiento')
                    ->options([
                        'vencido' => 'Vencido',
                        'menos_4_meses' => 'Vence en ≤4 meses',
                        'menos_8_meses' => 'Vence en ≤8 meses',
                        'mas_8_meses' => 'Vence en >8 meses',
                        'sin_fecha' => 'Sin fecha',
                    ])
                    ->query(function (Builder $query, array $state) {
                        // Primero filtramos siempre por saldo_actual > 0
                        $query->where('saldo_actual', '>', 0);

                        if (!empty($state['value'])) {
                            $hoy = now();

                            match ($state['value']) {
                                'vencido' => $query->whereDate('fecha_ven', '<', $hoy),
                                'menos_4_meses' => $query->whereBetween('fecha_ven', [
                                    $hoy,
                                    $hoy->copy()->addMonths(4)
                                ]),
                                'menos_8_meses' => $query->whereBetween('fecha_ven', [
                                    $hoy->copy()->addMonths(4),
                                    $hoy->copy()->addMonths(8)
                                ]),
                                'mas_8_meses' => $query->whereDate('fecha_ven', '>', $hoy->copy()->addMonths(8)),
                                'sin_fecha' => $query->whereNull('fecha_ven'),
                            };
                        }
                    }),
                // Filtro de almacenes
                SelectFilter::make('almacenes')
                    ->label('Filtrar Almacenes')
                    ->multiple()
                    ->options(function () {
                        return Cache::remember('almacenes-options', now()->addDay(), function () {
                            return Inventario::query()
                                ->select('cod_almacen', 'nombre_almacen')
                                ->whereNotNull('cod_almacen')
                                ->where('cod_almacen', '>=', '0')
                                ->distinct()
                                ->orderBy('cod_almacen')
                                ->get()
                                ->mapWithKeys(function ($item) {
                                    return [
                                        $item->cod_almacen => "{$item->cod_almacen} - {$item->nombre_almacen}"
                                    ];
                                })
                                ->toArray();
                        });
                    })
                    ->query(function (Builder $query, array $state) {
                        if (!empty($state['values'])) {
                            $query->whereIn('cod_almacen', $state['values']);
                        }
                    })
                    ->searchable(),
            ])
            ->actions([])
            ->bulkActions([])
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100]);
    }
    
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales
            //'view',
            //'create',
            'update',
            //'delete',
            'programar_inventario',

        ];
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventarios::route('/'),
            'edit' => Pages\EditInventario::route('/{record}/edit'),
        ];
    }
}