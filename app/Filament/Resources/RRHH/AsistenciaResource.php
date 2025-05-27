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
use Filament\Forms\Components\TextArea;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\View;

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
                Fieldset::make('Verificación de Ubicación')
                    ->schema([
                         View::make('filament.forms.components.gps-location')
                            ->label(' ')
                            ->extraAttributes(['class' => 'mb-4']),
                    ])
                    ->hidden(fn($get) => !empty($get('localizacion')))
                    ->columnSpanFull(),

                Placeholder::make('ubicacion_verificada')
                    ->content('Ubicación GPS verificada correctamente')
                    ->hidden(fn($get) => empty($get('localizacion')))
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800',
                    ]),

                TextInput::make('user_id')
                    ->label('CI/Número de Identificación')
                    ->required()
                    ->numeric()
                    ->default($ciEmpleado)
                    ->disabled(true),

                TextArea::make('justificacion')
                    ->label('Justificación del Registro Remoto')
                    ->required(fn($get) => $get('registro_remoto'))
                    ->hidden(fn($get) => !$get('registro_remoto'))
                    ->columnSpanFull()
                    ->maxLength(255)
                    ->disabled(function ($get, $livewire) {
                        // Deshabilitar si no hay localización en el componente ListAsistencias
                        return empty($livewire->localizacion);
                    }),

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

                Placeholder::make('¡Importante!')
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
                        if ($carbonDate->isWeekend()) continue;

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
                                if ($primeraMarcacion->greaterThan(Carbon::today()->setTime(8, 35, 0))) {
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

            $columns[] =   TextColumn::make("asistencias_{$date}")
                ->label("{$formattedDate}\n{$diaSemana}")
                ->html()
                ->getStateUsing(function ($record) use ($date, $carbonDate, $user) {
                    Log::debug('Obteniendo asistencias para fecha', [
                        'user_id' => $record->ci,
                        'date' => $date
                    ]);
                    //solo muestra los resultados del rol empleado 
                    if ($user->hasRole('Empleado') && $user->email !== $record->correo_corporativo) {
                        return '';
                    }

                    $asistencias = Asistencia::where('user_id', $record->ci)
                        ->whereDate('fecha', $date)
                        ->orderBy('hora')
                        ->get();

                    Log::debug('Asistencias encontradas', [
                        'count' => $asistencias->count(),
                        'asistencias' => $asistencias->toArray()
                    ]);
                    // Se evalua fin de semana y se pinta de color
                    if ($asistencias->isEmpty()) {
                        $result = $carbonDate->isWeekend() ?
                            '<div style="color:rgb(7, 236, 57); padding: 5px;">F/S</div>' :
                            '-';
                        Log::debug('No hay asistencias', ['result' => $result]);
                        return $result;
                    }
                    // Se evalua retrasos y se hace las opraciones matematicas en el front
                    $result = [];
                    $horaLimite = Carbon::today()->setTime(8, 35, 0); // Cambiado a 8:30
                    $horaOmision = Carbon::today()->setTime(10, 0, 0);
                    $primeraMarcacion = Carbon::parse($asistencias->first()->hora);
                    Log::debug('Evaluando primera marcación', [
                        'hora' => $primeraMarcacion->format('H:i:s'),
                        'horaLimite' => $horaLimite->format('H:i:s'),
                        'horaOmision' => $horaOmision->format('H:i:s')
                    ]);

                    // Se evalua las omisiones y se pinta de color 
                    if ($primeraMarcacion->greaterThan($horaOmision)) {
                        Log::debug('Marcación es omisión');
                        $result[] = "<span style='color: orange; font-weight: bold;'>Omisión</span>";
                    }

                    // Se evalua los retrasos y se pinta de color 
                    $marcaciones = $asistencias->map(function ($asistencia, $index) use ($horaLimite) {
                        $horaCompleta = Carbon::parse($asistencia->hora)->format('H:i:s');
                        $horaOmision = Carbon::today()->setTime(10, 0, 0);
                        if ($index === 0 && Carbon::parse($asistencia->hora)->greaterThan($horaLimite) && Carbon::parse($asistencia->hora)->lessThan($horaOmision)) {
                            Log::debug('Primera marcación con retraso', ['hora' => $horaCompleta]);
                            return "<span style='color: red; font-weight: bold;'>$horaCompleta</span>";
                        }
                        return $horaCompleta;
                    })->toArray();

                    // Se evalua si hay marcaciones en fin de semana y se pinta de color
                    $content = implode('<br>', array_merge($result, $marcaciones));
                    if ($carbonDate->isWeekend()) {
                        $content = "<div style='color:rgb(60, 218, 20); padding: 5px;'>{$content}</div>";
                    }

                    Log::debug('Contenido final para columna', ['content' => $content]);
                    return $content;
                })
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
                            ->with(['asistencias' => function ($q) use ($fechaInicio, $fechaFin) {
                                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                            }])
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
