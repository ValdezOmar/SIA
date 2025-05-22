<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\AsistenciaResource\Pages;
use App\Filament\Exports\AsistenciaExport;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\Asistencia;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Actions\ExportBulkAction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ExportAction;

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

    public static function form(Form $form): Form
    {
        // Obtener el usuario autenticado
        $user = Auth::user();

        // Buscar el empleado correspondiente al usuario
        $empleado = Empleado::where('correo_corporativo', $user->email)->first();
        $ciEmpleado = $empleado ? $empleado->ci : null;

        return $form
            ->schema([
                Forms\Components\Fieldset::make('Verificación de Ubicación')
                    ->schema([
                        Forms\Components\View::make('filament.forms.components.gps-location')
                            ->label(' ')
                            ->extraAttributes(['class' => 'mb-4']),
                    ])
                    ->hidden(fn($get) => !empty($get('localizacion'))) // Ocultar si ya tiene ubicación
                    ->columnSpanFull(),

                // Añade esto para mostrar un mensaje cuando ya tiene ubicación
                Forms\Components\Placeholder::make('ubicacion_verificada')
                    ->content('Ubicación GPS verificada correctamente')
                    ->hidden(fn($get) => empty($get('localizacion')))
                    ->columnSpanFull()
                    ->extraAttributes([
                        'class' => 'bg-green-50 border border-green-200 rounded-lg p-4 text-sm text-green-800',
                    ]),

                Forms\Components\TextInput::make('user_id')
                    ->label('CI/Número de Identificación')
                    ->required()
                    ->numeric()
                    ->default($ciEmpleado)
                    ->disabled(true),

                Forms\Components\Textarea::make('justificacion')
                    ->label('Justificación del Registro Remoto')
                    ->required(fn($get) => $get('registro_remoto'))
                    ->hidden(fn($get) => !$get('registro_remoto'))
                    ->columnSpanFull()
                    ->maxLength(500)
                    ->disabled(fn($get) => empty($get('localizacion'))),

                Forms\Components\Hidden::make('fecha')
                    ->default(today()->format('Y-m-d')),

                Forms\Components\Hidden::make('hora')
                    ->default(now()->format('H:i:s')),

                Forms\Components\Hidden::make('registro_remoto')
                    ->default(true),

                Forms\Components\Hidden::make('localizacion')
                    ->default('')
                    ->reactive(),

                Forms\Components\Placeholder::make('¡Importante!')
                    ->content('Los registros de asistencia remotos necesitan ser validados por la ubicación del GPS. Por favor habilite el GPS de su dispositivo o de los permisos correspondientes para continuar con el registro de asistencia.')
                    ->columnSpanFull()
                    ->hidden(fn($get) => !empty($get('localizacion'))) // Ocultar si hay ubicación
                    ->extraAttributes([
                        'class' => 'bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800',
                    ]),
            ]);
    }

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
            //->with(['asistencias'])
            ->orderBy('sucursal')
            ->orderBy('apellidos')
            ->orderBy('nombres');

        // Si el usuario tiene rol "Empleado", filtrar solo su registro
        if ($user->hasRole('Empleado') && $user->can('view_r::r::h::h::asistencia')) {
            $baseQuery->where('correo_corporativo', $user->email);
        }

        Log::debug('Iniciando construcción de tabla de asistencias');

        // Filtro por mes - ahora es el controlador principal del período
        // Filtro por mes con label descriptivo
        $mesFilter = Tables\Filters\SelectFilter::make('mes')
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
            });

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

            Tables\Columns\TextColumn::make('nombre_completo')
                ->label('Datos del Empleado')
                ->html()
                ->getStateUsing(fn($record) => "
                    <div>
                        <strong>{$record->nombres}</strong><br>
                        <small>{$record->apellidos}<br>CI: {$record->ci}</small>
                    </div>
                ")
                ->searchable(['nombres', 'apellidos', 'ci']),

            Tables\Columns\TextColumn::make('empresa')
                ->label('Empresa')
                ->searchable()
                ->sortable()
                ->description((fn(Empleado $record) => $record->sucursal))
                ->searchable(['empresa', 'sucursal']),

            Tables\Columns\TextColumn::make('estado')
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

        foreach ($uniqueDates as $date) {
            $carbonDate = Carbon::parse($date);
            $formattedDate = $carbonDate->format('d/m');
            $diaSemana = $carbonDate->translatedFormat('D');

            Log::debug('Creando columna para fecha', [
                'date' => $date,
                'formattedDate' => $formattedDate,
                'diaSemana' => $diaSemana
            ]);

            $columns[] = Tables\Columns\TextColumn::make("asistencias_{$date}")
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

        //Constuccion de la tabla principal donde se evaluan los privilegios
        return $table
            ->query(function () use ($baseQuery) {
                Log::debug('Construyendo consulta principal para tabla');
                return $baseQuery;
            })
            ->columns($columns)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Registrar Asistencia en Sitio')
                    ->modalHeading('Registro de Asistencia Remota')
                    ->modalSubmitActionLabel('Confirmar Asistencia')
                    ->createAnother(false)
                    ->successNotificationTitle('Asistencia registrada correctamente')
                    ->mutateFormDataUsing(function (array $data): array {
                        if (empty($data['localizacion'])) {
                            throw new \Exception('Debe permitir el acceso a la ubicación GPS para registrar asistencia');
                        }

                        // Validar que las coordenadas sean válidas
                        $coords = explode(',', $data['localizacion']);
                        if (count($coords) !== 2 || !is_numeric($coords[0]) || !is_numeric($coords[1])) {
                            throw new \Exception('Las coordenadas GPS no son válidas');
                        }

                        if ($data['registro_remoto'] && empty($data['justificacion'])) {
                            throw new \Exception('Debe proporcionar una justificación para el registro remoto');
                        }

                        return $data;
                    })
                    ->action(function (array $data) {
                        if (empty($data['localizacion'])) {
                            throw new \Exception('Debe obtener la ubicación GPS antes de registrar');
                        }

                        $exists = Asistencia::where('user_id', $data['user_id'])
                            ->whereDate('fecha', $data['fecha'])
                            ->exists();

                        if ($exists) {
                            throw new \Exception('Ya existe un registro de asistencia para este usuario hoy.');
                        }

                        Asistencia::create([
                            'user_id' => $data['user_id'],
                            'fecha' => $data['fecha'],
                            'hora' => $data['hora'],
                            'registro_remoto' => true,
                            'localizacion' => $data['localizacion'],
                            'justificacion' => $data['justificacion'] ?? null,
                        ]);
                    }),
                Tables\Actions\Action::make('exportPdf')
                    ->label('Exportar a PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function () use ($fechaInicio, $fechaFin) {
                        // Obtener todos los filtros aplicados
                        $filtros = request()->input('filters', []);

                        // Extraer filtros específicos
                        $filtroSucursal = $filtros['sucursal'] ?? null;
                        $filtroBusqueda = $filtros['busqueda']['busqueda'] ?? null;

                        // Construir query base
                        $query = Empleado::where('activo', true);

                        // Aplicar filtros si existen
                        if ($filtroSucursal) {
                            $query->where('sucursal', $filtroSucursal);
                        }

                        if ($filtroBusqueda) {
                            $query->where(function ($q) use ($filtroBusqueda) {
                                $q->where('ci', 'like', "%{$filtroBusqueda}%")
                                    ->orWhere('nombres', 'like', "%{$filtroBusqueda}%")
                                    ->orWhere('apellidos', 'like', "%{$filtroBusqueda}%");
                            });
                        }

                        // Obtener empleados ordenados
                        $empleados = $query->orderBy('sucursal')
                            ->orderBy('apellidos')
                            ->orderBy('nombres')
                            ->get();

                        // Obtener todas las asistencias del período de una vez
                        $asistencias = Asistencia::whereBetween('fecha', [$fechaInicio, $fechaFin])
                            ->get()
                            ->groupBy('user_id');

                        // Obtener fechas únicas del período
                        $uniqueDates = DB::table('rh_asistencias')
                            ->select(DB::raw('DATE(fecha) as date'))
                            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                            ->groupBy('date')
                            ->orderBy('date', 'asc')
                            ->pluck('date');

                        // Generar PDF
                        $pdf = Pdf::loadView('pdf.asistencias', [
                            'empleados' => $empleados,
                            'fechas' => $uniqueDates,
                            'fechaInicio' => $fechaInicio,
                            'fechaFin' => $fechaFin,
                            'filtroSucursal' => $filtroSucursal,
                            'filtroBusqueda' => $filtroBusqueda,
                            'asistenciasAgrupadas' => $asistencias
                        ])->setPaper('a4', 'landscape');

                        return Response::streamDownload(
                            fn() => print($pdf->stream()),
                            'reporte_asistencias_' . now()->format('Y-m-d_H-i') . '.pdf'
                        );
                    })
            ])
            ->filters([
                $mesFilter,
                Tables\Filters\SelectFilter::make('sucursal')
                    ->options(function () {
                        return Empleado::where('activo', true)
                            ->pluck('sucursal', 'sucursal')
                            ->unique()
                            ->sort();
                    })
                    ->searchable(),

                Tables\Filters\Filter::make('busqueda')
                    ->form([
                        Forms\Components\TextInput::make('busqueda')
                            ->label('Buscar (CI, Nombre, Apellido)')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (!empty($data['busqueda'])) {
                            $busqueda = $data['busqueda'];
                            return $query->where(function ($q) use ($busqueda) {
                                $q->where('ci', 'like', "%{$busqueda}%")
                                    ->orWhere('nombres', 'like', "%{$busqueda}%")
                                    ->orWhere('apellidos', 'like', "%{$busqueda}%");
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([])
            ->bulkActions([
                ExportBulkAction::make()
                    ->label('Exportar selección')
                    ->exporter(AsistenciaExport::class),
            ])
            ->headerActions([
                // Solo mostrar acción de creación si no es empleado o tiene permiso
                //  ExportAction::make()
                //     ->label('Exportar todo')
                //     ->exporter(AsistenciaExport::class)
                //     ->visible(fn() => !Auth::user()->hasRole('Empleado')),
                Tables\Actions\Action::make('exportPdf')
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
            ->recordUrl(null)
            ->deferLoading()
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->striped();
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

    private static function getCurrentLocation(): string
    {
        if (request()->hasHeader('X-Location')) {
            return request()->header('X-Location');
        }

        return 'GPS no disponible';
    }
}
