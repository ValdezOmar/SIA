<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\AsistenciaResource\Pages;
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
use Filament\Tables\Columns\ImageColumn;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\CarbonPeriod;
use Filament\Notifications\Notification;

class AsistenciaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Asistencia::class;
    protected static ?string $modelLabel = 'Registros de Asistencia'; //Seccion para configurar el nombre en Filament-Shield
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $pluralModelLabel = 'Asistencias';
    protected static ?string $navigationLabel = 'Registro de Asistencias';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 2;

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

                Hidden::make('visible')
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
                        if (!empty($state)) {
                            try {
                                // Validar JSON
                                json_decode($state);

                                // Truncar a 255 caracteres si excede
                                if (strlen($state) > 255) {
                                    $set('id_equipo', substr($state, 0, 255));
                                }
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
                    ->hint('Solo los registros realizados con teléfonos moviles son válidos')
                    ->hintIcon('heroicon-m-map-pin')
                    ->columnSpanFull()
                    ->hidden(function ($get, $livewire) {
                        return empty(!($livewire->localizacion));
                    })
                    ->extraAttributes([
                        'class' => 'bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800',
                    ]),
            ]);
    } // Fin Formulario de registro de asistencias remotas

    //Obtiene periodo de fechas de marcaciones
    public static function getPeriodoFechas(?string $mesSeleccionado = null): array
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

        // Obtenemos el período de la sesión (o calculamos el actual si no hay filtro)        
        $mesSeleccionado = request()->get('tableFilters')['mes']['value'] ?? null;
        $periodo = self::getPeriodoFechas($mesSeleccionado);
        $fechaInicio = $periodo['inicio'];
        $fechaFin = $periodo['fin'];

        // Obtener fechas únicas con marcaciones del período actual    
        $period = CarbonPeriod::create($fechaInicio, $fechaFin);

        $uniqueDates = collect();
        // Recolectar fechas y luego invertir el orden (más recientes primero)
        foreach ($period as $date) {
            $uniqueDates->push($date->format('Y-m-d'));
        }
        $uniqueDates = $uniqueDates->reverse(); // Esto invierte el orden


        // Construir la consulta base
        $baseQuery = Empleado::query()
            ->where('activo', true)
            ->with([
                'asistencias' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin])
                        ->where('visible', true);
                },
                // CARGAR HISTORIAL LABORAL ACTIVO con sus relaciones
                'historialActivo' => function ($q) {
                    $q->with(['empresa', 'cargo', 'sucursal']);
                }
            ])
            ->orderBy('sucursal')
            ->orderBy('nombres');


        // Verificar permisos en orden de prioridad
        if ($user->can('ver_marcacion_todos_r::r::h::h::asistencia')) {
            // Puede ver todas las marcaciones → no se filtra nada más
        } elseif ($user->can('ver_marcacion_sucursal_r::r::h::h::asistencia')) {
            // Puede ver solo su sucursal
            $empleadoUsuario = Empleado::where('correo_corporativo', $user->email)->first();
            if ($empleadoUsuario && $empleadoUsuario->sucursal) {
                $baseQuery->where('sucursal', $empleadoUsuario->sucursal);
            }
        } elseif ($user->can('ver_marcacion_propia_r::r::h::h::asistencia')) {
            // Puede ver solo sus propias marcaciones
            $baseQuery->where('correo_corporativo', $user->email);
        } else {
            // No tiene permisos → retornar tabla vacía
            Log::debug('Sin permiso de acceso a asistencias!', [
                'Usuario' => $user->name
            ]);
            return $table
                ->query(Empleado::query()->whereRaw('0 = 1')) // siempre vacío
                ->columns([])
                ->filters([])
                ->actions([])
                ->emptyStateHeading('No hay marcaciones disponibles')
                ->emptyStateDescription('Actualmente no cuenta con permisos asociados a su cuenta')
                ->emptyStateIcon('heroicon-o-exclamation-circle');
        }

        Log::debug('Iniciando construcción de tabla de asistencias', [
            'Usuario' => $user->name,
            'Rol' => $user->getRoleNames(),
        ]);

        // Columnas base optimizadas para espacio
        $columns = [
            ImageColumn::make('foto')
                ->label('')
                ->circular()
                ->width(50)
                ->height(50)
                ->defaultImageUrl(function ($record) {
                    // Mostrar la foto del empleado si existe, de lo contrario el avatar por defecto
                    return $record->foto ? asset('storage/' . $record->foto) : asset('images/default-avatar.jpg');
                }),

            TextColumn::make('nombre_completo')
                ->label('Datos del Empleado')
                ->html()
                ->getStateUsing(function ($record) {
                    $cargoTexto = 'Sin cargo';

                    // Obtener cargo del historial laboral activo
                    if ($record->historialActivo && $record->historialActivo->cargo) {
                        $cargoTexto = $record->historialActivo->cargo->nombre;
                    }

                    return "
            <div>
                <strong>{$record->nombres}</strong><br>
                <small>{$record->apellidos}<br>CI: {$record->ci}</small><br>
                <span style='font-size: 0.6rem'>{$cargoTexto}</span>
            </div>
        ";
                })
                ->searchable(['nombres', 'apellidos', 'ci']),

            TextColumn::make('empresa')
                ->label('Empresa')
                ->searchable()
                ->sortable()
                ->description(function (Empleado $record) {
                    // Obtener sucursal del historial laboral activo
                    if ($record->historialActivo && $record->historialActivo->sucursal) {
                        return $record->historialActivo->sucursal->nombre;
                    }
                    return 'Sin sucursal';
                })
                ->formatStateUsing(function (Empleado $record) {
                    // Obtener empresa del historial laboral activo
                    if ($record->historialActivo && $record->historialActivo->empresa) {
                        return $record->historialActivo->empresa->razon_social;
                    }
                    return 'Sin empresa';
                })
                ->searchable(query: function (Builder $query, string $search): Builder {
                    return $query->where(function ($q) use ($search) {
                        $q->whereHas('historialActivo.empresa', function ($subq) use ($search) {
                            $subq->where('razon_social', 'like', "%{$search}%");
                        })
                            ->orWhereHas('historialActivo.sucursal', function ($subq) use ($search) {
                                $subq->where('nombre', 'like', "%{$search}%");
                            })
                            ->orWhereHas('historialActivo.cargo', function ($subq) use ($search) {
                                $subq->where('nombre', 'like', "%{$search}%");
                            });
                    });
                }),

            //Realiza el calculo de los retrasos, los cuenta y los inserta en la tabla de resumen Estado
            TextColumn::make('estado')
                ->label('Estado')
                ->html()
                ->getStateUsing(function ($record) use ($uniqueDates, $fechaInicio, $fechaFin) {
                    $retrasos = 0;
                    $omision = 0;
                    $faltas = 0;
                    $totalSegundosRetraso = 0;

                    // CORRECCIÓN: Usar la fecha de cada registro para las comparaciones
                    foreach ($uniqueDates as $date) {
                        $carbonDate = Carbon::parse($date);

                        // Saltar fines de semana
                        if ($carbonDate->isWeekend()) {
                            continue;
                        }

                        // Filtrar asistencias visibles para esta fecha
                        $asistenciasVisibles = $record->asistencias->filter(function ($asistencia) use ($date) {
                            return $asistencia->fecha == $date && $asistencia->visible !== false;
                        });

                        if ($asistenciasVisibles->isEmpty()) {
                            $faltas++;
                            continue;
                        }

                        // Obtener primera marcación del día
                        $primeraMarcacion = $asistenciasVisibles->sortBy('hora')->first();

                        // CORRECCIÓN: Crear objetos Carbon con la fecha específica
                        $horaMarcacion = Carbon::parse($date . ' ' . $primeraMarcacion->hora);

                        // Definir horas límite para esta fecha específica
                        $horaEntrada = Carbon::parse($date . ' 08:30:00');
                        $horaLimiteRetraso = Carbon::parse($date . ' 08:35:59');
                        $horaOmision = Carbon::parse($date . ' 10:00:00');

                        // Clasificar la marcación
                        if ($horaMarcacion->greaterThan($horaOmision)) {
                            $omision++;
                        } elseif ($horaMarcacion->greaterThan($horaLimiteRetraso)) {
                            $retrasos++;

                            // CORRECCIÓN: Calcular diferencia desde las 08:30
                            $diferencia = $horaEntrada->diff($horaMarcacion);
                            $segundosRetraso = $diferencia->h * 3600 + $diferencia->i * 60 + $diferencia->s;
                            $totalSegundosRetraso += $segundosRetraso;
                        }
                        // Si llega antes de las 08:35:59, no es retraso ni omisión
                    }

                    // Formatear tiempo total de retraso
                    $horasTotal = floor($totalSegundosRetraso / 3600);
                    $minutosTotal = floor(($totalSegundosRetraso % 3600) / 60);
                    $segundosTotal = $totalSegundosRetraso % 60;

                    if ($horasTotal > 0) {
                        $tiempoTotalRetraso = sprintf("%02d:%02d:%02d", $horasTotal, $minutosTotal, $segundosTotal);
                    } else {
                        $tiempoTotalRetraso = sprintf("%02d:%02d", $minutosTotal, $segundosTotal);
                    }

                    $resultado = "
        <div style='line-height: 1.4;'>
            <strong>Retrasos:</strong> {$retrasos}<br>
            <strong>Tiempo retraso:</strong> {$tiempoTotalRetraso}<br>
            <strong>Faltas:</strong> {$faltas}<br>
            <strong>Omisiones:</strong> {$omision}
        </div>
        ";

                    return $resultado;
                })
                ->alignLeft()
                ->width('120px'),
        ];

        //Realiza el calculo de los retrasos, los pinta en colores segun corresponda para luego dibujar en tabla
        foreach ($uniqueDates as $date) {
            $carbonDate = Carbon::parse($date);
            $formattedDate = $carbonDate->format('d/m');
            $diaSemana = $carbonDate->translatedFormat('D');

            //Realiza el calculo de los retrasos y pinta de colores
            $columns[] = ViewColumn::make("{$date}")
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

        return $table
            //Constuccion de la tabla principal donde se evaluan la consuta principal (prvilegios, periodos ,etc)
            ->query(function () use ($baseQuery) {
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
                        $startDate = $now->copy()->subMonths(5);

                        while ($startDate <= $now) {
                            $periodo = AsistenciaResource::getPeriodoFechas($startDate->format('Y-m'));
                            $options[$startDate->format('Y-m')] = $periodo['label'];
                            $startDate->addMonth();
                        }

                        return array_reverse($options, true);
                    })
                    ->label('Período')
                    ->placeholder('Seleccione un periodo')
                    ->query(function (Builder $query, array $data): Builder {

                        $mesSeleccionado = $data['value'] ?? null;
                        $periodo = AsistenciaResource::getPeriodoFechas($mesSeleccionado);

                        return $query->with([
                            'asistencias' => function ($q) use ($periodo) {
                                $q->whereBetween('fecha', [$periodo['inicio'], $periodo['fin']])
                                    ->where('visible', true);
                            }
                        ]);
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! $data['value']) return null;

                        $periodo = AsistenciaResource::getPeriodoFechas($data['value']);
                        return 'Período: ' . $periodo['label'];
                    })

            ])
            //Botonera de la Cabecera para hacer acciones adicionales
            ->headerActions([

                //Exporatacion a archivo PDF de las marcaciones
                Action::make('exportPdf')
                    // Restringir exportación si es empleado
                    ->visible(fn() => Auth::user()->can('exportar_pdf_r::r::h::h::asistencia'))
                    ->label('Exportar a PDF')
                    ->color('danger')
                    ->icon('heroicon-o-document-arrow-down')
                    ->action(function (array $data, $livewire) {
                        try {
                            // Obtener los filtros actuales para el período
                            $filters = $livewire->tableFilters;
                            $mesSeleccionado = $filters['mes']['value'] ?? null;
                            $periodo = self::getPeriodoFechas($mesSeleccionado);

                            // Obtener los empleados CON LOS MISMOS DATOS que muestra la tabla
                            // Usamos el query de la tabla pero sin paginación
                            $empleados = $livewire->getFilteredTableQuery()->get();

                            // Obtener las fechas del período (igual que en la tabla)
                            $period = CarbonPeriod::create($periodo['inicio'], $periodo['fin']);
                            $uniqueDates = collect();
                            foreach ($period as $date) {
                                $uniqueDates->push($date->format('Y-m-d'));
                            }

                            // Cargar las asistencias para cada empleado si no están ya cargadas
                            // Esto asegura que tengamos todos los datos necesarios
                            $empleados->load([
                                'asistencias' => function ($q) use ($periodo) {
                                    $q->whereBetween('fecha', [$periodo['inicio'], $periodo['fin']])
                                        ->where('visible', true)
                                        ->orderBy('hora');
                                },
                                'historialActivo.empresa',
                                'historialActivo.cargo',
                                'historialActivo.sucursal'
                            ]);

                            // Preparar datos para la vista
                            $filtroSucursal = null;
                            $user = Auth::user();

                            if ($user->can('ver_marcacion_sucursal_r::r::h::h::asistencia')) {
                                $empleadoUsuario = Empleado::where('correo_corporativo', $user->email)->first();
                                $filtroSucursal = $empleadoUsuario->sucursal ?? null;
                            }

                            // Generar PDF con los mismos datos que la vista
                            $pdf = Pdf::loadView('exports.asistencias-pdf', [
                                'empleados' => $empleados,
                                'fechas' => $uniqueDates,
                                'fechaInicio' => $periodo['inicio'],
                                'fechaFin' => $periodo['fin'],
                                'filtroSucursal' => $filtroSucursal,
                                'filtroBusqueda' => $filters['search'] ?? null,
                                'titulo' => 'Reporte de Asistencias - ' . $periodo['label']
                            ]);

                            // Configurar el PDF para mejor visualización
                            $pdf->setPaper('A4', 'landscape'); // Cambiar a landscape para mejor visualización de muchas columnas

                            return Response::streamDownload(function () use ($pdf) {
                                echo $pdf->stream();
                            }, 'asistencias_' . now()->format('Y-m-d') . '.pdf');
                        } catch (\Exception $e) {
                            Log::error('Error generando PDF de asistencias: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString()
                            ]);

                            // Mostrar error al usuario
                            Notification::make()
                                ->title('Error al generar PDF')
                                ->body('Ocurrió un error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->paginated([10, 25, 50, 100])    // Opciones de paginación
            ->defaultPaginationPageOption(100) // Por defecto: 100 filas
            ->striped();                       // Filas con fondo alternado
        return $table;
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales       
            'create',
            'ver_marcacion_propia',
            'ver_marcacion_sucursal',
            'ver_marcacion_todos',
            'exportar_pdf',

        ];
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