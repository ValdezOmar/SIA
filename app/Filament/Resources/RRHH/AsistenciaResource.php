<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\AsistenciaResource\Pages;
use App\Filament\Exports\AsistenciaExport;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\Asistencia;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ViewColumn;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\View;
use Filament\Forms;
use Filament\Forms\Components\Grid;

class AsistenciaResource extends Resource
{
    protected static ?string $model = Asistencia::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $modelLabel = 'Asistencia';
    protected static ?string $pluralModelLabel = 'Asistencias';
    protected static ?string $navigationLabel = 'Registro de Asistencias';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 2;
    protected static array $cachedCalculations = [];

    //Formulario de registro de asistencias remotas
    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $empleado = Empleado::where('correo_corporativo', $user->email)->first();
        $ciEmpleado = $empleado ? $empleado->ci : null;

        return $form

            ->schema([
                Grid::make(2)
                    ->schema([
                        // Columna izquierda: ubicación + CI
                        Group::make([
                            View::make('filament.forms.components.gps-location'),

                            TextInput::make('user_id')
                                ->label('CI/Número de Identificación')
                                ->required()
                                ->numeric()
                                ->default($ciEmpleado)
                                ->hidden(function ($get, $livewire) {
                                    // Deshabilitar si no hay localización en el componente ListAsistencias
                                    return empty($livewire->localizacion);
                                })
                                ->disabled(true),

                            Forms\Components\Textarea::make('justificacion')
                                ->label('Justificación del Registro Remoto')
                                ->required()
                                ->columnSpanFull()
                                ->maxLength(255)
                                ->hidden(function ($get, $livewire) {
                                    // Deshabilitar si no hay localización en el componente ListAsistencias
                                    return empty($livewire->localizacion);
                                })
                                ->extraAttributes(['class' => 'h-32'])

                        ])->columnSpan(1),

                        // Columna derecha: mapa
                        View::make('filament.forms.components.gps-map')
                            ->extraAttributes(['class' => 'rounded-xl overflow-hidden h-48 border border-gray-300'])
                            ->columnSpan(1),
                    ]),

                Hidden::make('fecha')
                    ->default(today()->format('Y-m-d')),

                Hidden::make('hora')
                    ->default(now()->format('H:i:s')),

                Hidden::make('registro_remoto')
                    ->default(true),

                // Captura localizacion desde alpine
                Hidden::make('localizacion')
                    ->default('')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Validar coordenadas
                        $coords = explode(',', $state);
                        if (count($coords) !== 2 || !is_numeric(trim($coords[0]))) {
                            $set('localizacion', '');
                        }
                    }),

                // Campo para el identificador del equipo
                Hidden::make('id_equipo')
                    ->default('')
                    ->reactive()
                    ->afterStateUpdated(function ($state, $set) {
                        // Validar que sea un JSON válido
                        if (!empty($state)) {
                            try {
                                json_decode($state);
                            } catch (\Exception $e) {
                                $set('id_equipo', '');
                            }
                        }
                    }),

                TextInput::make('titulo')
                    ->label('¡Importante!')
                    ->required()
                    ->hidden(function ($get, $livewire) {
                        return empty(!$livewire->localizacion);
                    })
                    ->extraAttributes(['class' => 'hidden'])
                    ->disabled(true),

                Placeholder::make('')
                    ->content('Los registros de asistencia remotos necesitan ser validados por la ubicación del GPS. Por favor haz clic en el botón "Obtener Ubicación GPS", activa la geolocalización y permite el acceso a tu ubicación.')
                    ->columnSpanFull()
                    ->hidden(function ($get, $livewire) {
                        return empty(!($livewire->localizacion));
                    })
                    ->extraAttributes([
                        'class' => 'bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800',
                    ]),
            ]);
    }

    //Obtiene periodo de fechas de marcaciones
    protected static function getPeriodoFechas(?string $mesSeleccionado = null): array
    {
        $now = now();

        if ($mesSeleccionado) {
            $fechaSeleccionada = Carbon::parse($mesSeleccionado);
            $fechaInicio = $fechaSeleccionada->copy()->subMonth()->day(26);
            $fechaFin = $fechaSeleccionada->copy()->day(25);

            // Ajustar fecha fin si excede la fecha actual
            if ($fechaFin->greaterThan($now)) {
                $fechaFin = $now->copy();
            }

            // Crear label descriptivo (ej. "Abril 2025 (26 mar - 25 abr)")
            $mesNombre = $fechaSeleccionada->translatedFormat('F Y');
            $inicioFormatted = $fechaInicio->translatedFormat('d M');
            $finFormatted = $fechaFin->translatedFormat('d M');
            $label = "$mesNombre ($inicioFormatted - $finFormatted)";
        } else {
            // Determinar período actual basado en día del mes
            if ($now->day >= 26) {
                $fechaInicio = $now->copy()->day(26);
                $fechaFin = $now->copy()->addMonth()->day(25);
            } else {
                $fechaInicio = $now->copy()->subMonth()->day(26);
                $fechaFin = $now->copy()->day(25);
            }

            // Ajustar fecha fin si excede la fecha actual
            if ($fechaFin->greaterThan($now)) {
                $fechaFin = $now->copy();
            }

            // Label para período actual
            $mesNombre = $now->translatedFormat('F Y');
            $inicioFormatted = $fechaInicio->translatedFormat('d M');
            $finFormatted = $fechaFin->translatedFormat('d M');
            $label = "$mesNombre ($inicioFormatted - $finFormatted)";
        }

        return [
            'inicio' => $fechaInicio,
            'fin' => $fechaFin,
            'label' => $label
        ];
    }

    //Contruye la Tabla de vista prrincipal
    public static function table(Table $table): Table
    {
        // Obtener el usuario actual
        $user = Auth::user();
        //static::$modalsToRender['remoteDetailsModal'] = static::getRemoteDetailsModal();

        // Verificar permisos - si no tiene acceso, retornar tabla vacía
        if ($user->hasRole('Empleado') && !$user->can('view_r::r::h::h::asistencia')) {
            return $table
                ->columns([])
                ->filters([])
                ->actions([])
                ->bulkActions([])
                ->paginated(false)
                ->emptyStateHeading('No tienes permisos para ver esta información');
        }

        // Construir la consulta base
        $baseQuery = Empleado::query()
            ->where('activo', true)
            //->with(['asistencias'])  //Lista solo los que tienen marcaciones
            ->orderBy('sucursal')
            ->orderBy('apellidos')
            ->orderBy('nombres');

        // Si el usuario tiene rol "Empleado", filtrar solo su registro
        if ($user->hasRole('Empleado') && $user->can('view_r::r::h::h::asistencia')) {
            $baseQuery->where('correo_corporativo', $user->email);
        }

        Log::debug('Iniciando construcción de tabla de asistencias');

        // Obtenemos el período de la sesión (o calculamos el actual si no hay filtro)
        $periodo = Session::get('periodo_asistencias', self::getPeriodoFechas());
        $fechaInicio = $periodo['inicio'];
        $fechaFin = $periodo['fin'];

        Log::debug('Período de consulta determinado', [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'source' => Session::has('periodo_asistencias') ? 'session' : 'calculated'
        ]);

        // Obtener fechas únicas con marcaciones del período actual
        Log::debug('Consultando fechas únicas con asistencias', [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d')
        ]);

        $uniqueDates = DB::table('rh_asistencias')
            ->select(DB::raw('DATE(fecha) as date'))
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->pluck('date');

        Log::debug('Fechas únicas con asistencias encontradas', [
            'cantidad_fechas' => $uniqueDates->count(),
            'fechas' => $uniqueDates->toArray()
        ]);

        // Columnas base optimizadas para espacio
        $columns = [

            TextColumn::make('nombre_completo')
                ->label('Datos del Empleado')
                ->html()
                ->getStateUsing(fn($record) => "
                    <div>
                        <strong>{$record->nombres}</strong><br>
                        <small>{$record->apellidos}<br>CI: {$record->ci}</small>
                    </div>
                ")
                ->searchable(['nombres', 'apellidos', 'ci']),

            TextColumn::make('empresa')
                ->label('Empresa')
                ->searchable()
                ->sortable()
                ->description((fn(Empleado $record) => $record->sucursal))
                ->searchable(['empresa', 'sucursal']),

            //Realiza el calculo de los retrasos, los cuenta y los inserta en la tabla de resumen Estado
            TextColumn::make('estado')
                ->label('Estado')
                ->html()
                ->getStateUsing(function ($record) use ($uniqueDates, $fechaInicio, $fechaFin) {
                    $cacheKey = 'estado_' . $record->ci;

                    // Retornar cálculo cacheado si existe
                    if (array_key_exists($cacheKey, self::$cachedCalculations)) {
                        return self::$cachedCalculations[$cacheKey];
                    }

                    Log::debug('Calculando estado para empleado', ['ci' => $record->ci]);

                    $retrasos = 0;
                    $omision = 0;
                    $faltas = 0;
                    $totalSegundosRetraso = 0;
                    $horaLimite = Carbon::today()->setTime(8, 30, 00);
                    $horaOmision = Carbon::today()->setTime(10, 00, 00);

                    // Cálculo de días laborales (optimizado)
                    $diasLaborales = $fechaInicio->diffInDaysFiltered(function ($date) {
                        return !$date->isWeekend();
                    }, $fechaFin);

                    Log::debug('Días laborales en período', ['count' => $diasLaborales]);

                    foreach ($uniqueDates as $date) {
                        $carbonDate = Carbon::parse($date);
                        if ($carbonDate->isWeekend())
                            continue;

                        $asistencias = $record->asistencias->filter(function ($asistencia) use ($date) {
                            return $asistencia->fecha == $date;
                        });

                        if ($asistencias->isEmpty()) {
                            $faltas++;
                            Log::debug('Falta registrada', ['fecha' => $date]);
                        } else {
                            $primeraMarcacion = Carbon::parse($asistencias->first()->hora);

                            if ($primeraMarcacion->greaterThan($horaOmision)) {
                                $omision++;
                                Log::debug('Omisión registrada', [
                                    'fecha' => $date,
                                    'hora' => $primeraMarcacion->format('H:i:s')
                                ]);
                            } elseif ($primeraMarcacion->greaterThan($horaLimite)) {
                                if ($primeraMarcacion->greaterThan(Carbon::today()->setTime(8, 35, 59))) {
                                    $retrasos++;
                                    $diferencia = $horaLimite->diff($primeraMarcacion);
                                    $segundosRetraso = $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
                                    $totalSegundosRetraso += $segundosRetraso;
                                }
                            }
                        }
                    }

                    $horasTotal = floor($totalSegundosRetraso / 3600);
                    $minutosTotal = floor(($totalSegundosRetraso % 3600) / 60);
                    $segundosTotal = $totalSegundosRetraso % 60;
                    $tiempoTotalRetraso = $horasTotal > 0
                        ? sprintf("%02d:%02d:%02d", $horasTotal, $minutosTotal, $segundosTotal)
                        : sprintf("%02d:%02d", $minutosTotal, $segundosTotal);

                    $resultado = "
                    <div style='line-height: 1.4;'>
                        <strong>Retrasos:</strong> {$retrasos}<br>
                        <strong>Tiempo retraso:</strong> {$tiempoTotalRetraso}<br>
                        <strong>Faltas:</strong> {$faltas}<br>
                        <strong>Omisiones:</strong> {$omision}
                    </div>
                     ";

                    // Almacenar en cache
                    self::$cachedCalculations[$cacheKey] = $resultado;

                    return $resultado;
                })
                ->alignLeft()
                ->width('120px'),
        ];

        // Columnas dinámicas por fecha
        Log::debug('Generando columnas dinámicas por fecha', ['count_fechas' => count($uniqueDates)]);

        //Realiza el calculo de los retrasos, los pinta en colores segun corresponda para luego dibujar en tabla
        foreach ($uniqueDates as $date) {
            $carbonDate = Carbon::parse($date);
            $formattedDate = $carbonDate->format('d/m');
            $diaSemana = $carbonDate->translatedFormat('D');

            Log::debug('Creando columna para fecha', [
                'date' => $date,
                'formattedDate' => $formattedDate,
                'diaSemana' => $diaSemana
            ]);

            //Realiza el calculo de los retrasos y pinta de colores
            $columns[] = ViewColumn::make("asistencias_{$date}")
                ->label("{$formattedDate}\n{$diaSemana}")
                ->view('filament.forms.components.asistencia-datafield')
                ->viewData([
                    'date' => $date,
                    'carbonDate' => $carbonDate,
                    'user' => $user,
                ])
                ->alignCenter()
                ->width('90px');
        }

        Log::debug('Finalizando construcción de tabla', [
            'total_columnas' => count($columns),
            'fechas_mostradas' => count($uniqueDates)
        ]);

        return $table
            //Constuccion de la tabla principal donde se evaluan la consuta principal (prvilegios, periodos ,etc)
            ->query(function () use ($baseQuery) {
                Log::debug('Construyendo consulta principal para tabla');
                return $baseQuery;
            })
            ->columns($columns)

            //Filtros de selleion de la tabla
            ->filters([
                // Filtro por mes primario que lista los periodos de asistencia
                SelectFilter::make('mes')
                    ->options(function () {
                        $options = [];
                        $now = now();
                        $startDate = $now->copy()->subMonths(5); // Últimos 6 meses
            
                        while ($startDate <= $now) {
                            $periodo = self::getPeriodoFechas($startDate->format('Y-m'));
                            $options[$startDate->format('Y-m')] = $periodo['label'];
                            $startDate->addMonth();
                        }

                        return array_reverse($options, true); // Ordenar de más reciente a más antiguo
                    })
                    ->label('Período')
                    ->placeholder('Seleccione un mes')
                    ->default(function () {
                        $now = now();
                        return ($now->day > 25) ? $now->copy()->addMonth()->format('Y-m') : $now->format('Y-m');
                    })
                    ->query(function (Builder $query, array $data) {
                        $mesSeleccionado = $data['value'] ?? null;

                        if (!$mesSeleccionado) {
                            $now = now();
                            $mesSeleccionado = ($now->day > 25) ?
                                $now->copy()->addMonth()->format('Y-m') :
                                $now->format('Y-m');
                        }

                        $periodo = self::getPeriodoFechas($mesSeleccionado);
                        Session::put('periodo_asistencias', $periodo);
                    }),

                //FIltro de sucursales
                SelectFilter::make('sucursal')
                    ->options(function () {
                        return Empleado::where('activo', true)
                            ->pluck('sucursal', 'sucursal')
                            ->unique()
                            ->sort();
                    })
                    ->searchable(),
            ])
            //Botonera de la Cabecera para hacer acciones adicionales
            ->headerActions([

                // Solo mostrar acción de creación si no es empleado o tiene permiso
                //  ExportAction::make()
                //     ->label('Exportar todo')
                //     ->exporter(AsistenciaExport::class)
                //     ->visible(fn() => !Auth::user()->hasRole('Empleado')),

                //Exporatacion a archivo PDF de las marcaciones
                Action::make('exportPdf')
                    // Restringir exportación si es empleado
                    ->visible(fn() => !Auth::user()->hasRole('Empleado'))
                    ->label('Exportar a PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (array $data) use ($fechaInicio, $fechaFin) {
                        $empleados = Empleado::where('activo', true)
                            ->with([
                                'asistencias' => function ($q) use ($fechaInicio, $fechaFin) {
                                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                                }
                            ])
                            ->orderBy('sucursal')
                            ->orderBy('apellidos')
                            ->orderBy('nombres')
                            ->get();

                        $uniqueDates = DB::table('rh_asistencias')
                            ->select(DB::raw('DATE(fecha) as date'))
                            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                            ->groupBy('date')
                            ->orderBy('date', 'desc')
                            ->limit(31) // Limitar a un mes máximo
                            ->pluck('date');

                        $pdf = Pdf::loadView('pdf.asistencias', [
                            'empleados' => $empleados,
                            'fechas' => $uniqueDates,
                            'fechaInicio' => $fechaInicio,
                            'fechaFin' => $fechaFin
                        ]);

                        return Response::streamDownload(function () use ($pdf) {
                            echo $pdf->stream();
                        }, 'asistencias_' . now()->format('Y-m-d') . '.pdf');
                    }),
            ])
            //->recordUrl(null)                  // Desactiva el clic en las filas
            //->deferLoading()                  // Retrasa carga de la tabla
            ->paginated([10, 25, 50, 100])    // Opciones de paginación
            ->defaultPaginationPageOption(100) // Por defecto: 100 filas
            ->striped();                       // Filas con fondo alternado
        return $table;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAsistencias::route('/'),
        ];
    }
}