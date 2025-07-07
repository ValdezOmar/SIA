<?php

namespace App\Filament\Resources\Almacen;

use App\Filament\Exports\ArticuloExporter;
use App\Filament\Resources\Almacen\ArticuloResource\Pages;
use App\Models\Almacen\Articulo;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use DesignTheBox\BarcodeField\Forms\Components\BarcodeInput;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Filters\Filter;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ArticuloResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Articulo::class;
    protected static ?string $modelLabel = 'Disponibilidad de Stock'; //Seccion para configurar el nombre en Filament-Shield

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $pluralModelLabel = 'Disponibilidad de Stock';
    protected static ?string $navigationLabel = 'Disponibilidad de Stock';
    protected static ?string $navigationGroup = 'Almacenes';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
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

                TextColumn::make('saldo_actual')
                    ->label('Saldo Actual')
                    ->html()
                    ->getStateUsing(fn($record) => "
                        <div style='
                            text-align: center;
                            font-size: 1rem;
                            font-weight: 800;
                        '>
                            {$record->saldo_actual}
                        </div>
                    ")
                    ->sortable(),

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
            ])
            ->filters([
                //Filtro de busqueda QR
                Filter::make('Buscar por QR o Código de Barra')
                    ->form([
                        BarcodeInput::make('sn_qr')
                            ->label('Escanear Código')
                            ->icon('heroicon-o-qr-code')
                            ->reactive(),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                isset($data['sn_qr']) && !empty($data['sn_qr']),
                                fn($query) => $query->where('sn_qr', $data['sn_qr'])
                            );
                    }),

                //Filtro de busqueda de Empresas
                SelectFilter::make('empresas')
                    ->label('Filtrar por Empresa')
                    ->multiple()
                    ->options(function () {
                        return Articulo::query()
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
                // Filtro de almacenes Los almacenes especificados (101,102,etc.) estarán seleccionados al cargar
                SelectFilter::make('almacenes')
                    ->label('Filtrar por Almacenes')
                    ->multiple()
                    ->options(function () {
                        return Cache::remember('almacenes-options', now()->addDay(), function () {
                            return Articulo::query()
                                ->select('cod_almacen', 'nombre_almacen')
                                ->whereNotNull('cod_almacen')
                                ->where('cod_almacen', '!=', '0')
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
            ->headerActions([
                ExportAction::make()
                    ->exporter(ArticuloExporter::class)
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->button()
                    ->fileDisk('local') // O 'local' si prefieres almacenar localmente
                    ->columnMapping(false) // Ocultar selección de columnas (usar todas las definidas)
                    //modifyQueryUsing(fn(Builder $query) => $query->where('saldo_actual', '>', 0)) // Solo artículos con stock
            ])
            ->bulkActions([])
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(50) //Filas mostradas
            ->striped();                      //Filas con fondo alternado
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales
            //'view',
            //'create',
            //'update',
            //'delete',
            //permisos personalizados:
            'tab_todos',
            'tab_comercial',
            'tab_almacen',
        ];
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\Almacen\ArticuloResource\Widgets\ArticuloStats::class,
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
            'index' => Pages\ListArticulos::route('/'),
        ];
    }
}