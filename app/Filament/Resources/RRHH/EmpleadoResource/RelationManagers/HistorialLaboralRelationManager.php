<?php


namespace App\Filament\Resources\RRHH\EmpleadoResource\RelationManagers;

use App\Models\Sistema\Cargo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class HistorialLaboralRelationManager extends RelationManager
{
    protected static string $relationship = 'historialLaboral'; // relación en el modelo Empleado
    protected static ?string $title = 'Historia Laboral';

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Información Laboral')
                ->description('Datos principales del vínculo laboral del empleado')
                ->icon('heroicon-o-briefcase')
                ->columns(2)
                ->schema([
                    Select::make('empresa_id')
                        ->label('Empresa')
                        ->prefixIcon('heroicon-o-building-office-2')
                        ->required()
                        ->relationship('empresa', 'razon_social')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                            // Limpiar campos dependientes
                            $set('cargo', null);
                            $set('sucursal_id', null);

                            // Actualizar seguro médico
                            $seguro = Empresa::find($state)?->seguro_medico;
                            $set('seguro_medico', $seguro);

                            // Limpiar y regenerar correo corporativo
                            $empleado = $this->getOwnerRecord();
                            if ($empleado) {
                                $nuevoCorreo = $this->generarCorreoCorporativoDesdeEmpleado($empleado, $state);
                                $set('correo_corporativo', $nuevoCorreo);
                            }
                        }),

                    Select::make('sucursal_id')
                        ->label('Sucursal')
                        ->prefixIcon('heroicon-o-map-pin')
                        ->required()
                        ->options(
                            fn(Get $get) =>
                            $get('empresa_id')
                                ? Sucursal::where('empresa_id', $get('empresa_id'))->pluck('nombre', 'id')->toArray()
                                : []
                        )
                        ->reactive()
                        ->searchable()
                        ->placeholder('Seleccione una empresa primero'),

                    Select::make('cargo_id')
                        ->label('Cargo')
                        ->prefixIcon('heroicon-o-user-circle')
                        ->required()
                        ->reactive() // <- recalcula cuando cambie empresa_id
                        ->options(function (Get $get) {
                            $empresaId = $get('empresa_id');

                            if (!$empresaId) {
                                return []; // sin empresa, ninguna opción
                            }

                            // Asegúrate que la relación area.empresas exista correctamente en tu modelo Cargo/Area
                            return Cargo::whereHas('area.empresas', function ($q) use ($empresaId) {
                                $q->where('conf_empresas.id', $empresaId);
                            })
                                ->orderBy('nombre')
                                ->pluck('nombre', 'id')
                                ->toArray(); // devolver siempre array
                        })
                        ->searchable()
                        ->placeholder('Seleccione una empresa primero'),

                    Select::make('tipo_contrato')
                        ->label('Tipo de Contrato')
                        //->required()
                        ->prefixIcon('heroicon-o-document-text')
                        ->options([
                            'Contrato indefinido' => 'Contrato indefinido',
                            'Contrato plazo fijo' => 'Contrato plazo fijo',
                            'Contrato por servicios' => 'Contrato por servicios',
                            'Contrato por obra' => 'Contrato por obra',
                            'Contrato por temporada' => 'Contrato por temporada',
                            'Contrato de teletrabajo' => 'Contrato de teletrabajo',
                            'Pasante' => 'Pasante',
                            'Otro' => 'Otro tipo',
                        ])

                        ->afterStateUpdated(function (Get $get, Set $set, $state) {
                            if ($state !== 'Otro') {
                                $set('tipo_contrato_personalizado', null);
                            }
                        })
                        ->default('Contrato plazo fijo')
                        ->reactive(),

                    TextInput::make('tipo_contrato_personalizado')
                        ->label('Especificar contrato')
                        ->visible(fn(Get $get) => $get('tipo_contrato') === 'Otro')
                        ->required(fn(Get $get) => $get('tipo_contrato') === 'Otro')
                        ->placeholder('Ej: Contrato eventual por proyecto')
                        ->dehydrated(fn($state) => filled($state))
                        ->afterStateHydrated(function (Set $set, Get $get) {
                            // Detectar si el contrato guardado no está en la lista y mostrarlo como personalizado
                            $contrato = $get('tipo_contrato');
                            $predefinidos = [
                                'Contrato indefinido',
                                'Contrato plazo fijo',
                                'Contrato por servicios',
                                'Contrato por obra',
                                'Contrato por temporada',
                                'Contrato de teletrabajo',
                                'Pasante',
                                'Otro tipo',
                            ];

                            if ($contrato && !in_array($contrato, $predefinidos)) {
                                $set('tipo_contrato', 'Otro');
                                $set('tipo_contrato_personalizado', $contrato);
                            }
                        })
                        ->afterStateUpdated(function (Set $set, Get $get, $state) {
                            // Guardar el valor personalizado como tipo_contrato real
                            if ($get('tipo_contrato') === 'Otro') {
                                $set('tipo_contrato', $state);
                            }
                        }),

                    DatePicker::make('fecha_inicio')
                        ->label('Inicio de contrato')
                        ->prefixIcon('heroicon-o-calendar'),
                    //->required(),

                    DatePicker::make('fecha_fin')
                        ->label('Fin de contrato')
                        //->required()
                        ->prefixIcon('heroicon-o-calendar-days')
                        ->visible(fn(Get $get) => $get('tipo_contrato') !== 'Contrato indefinido'),

                    TextInput::make('salario')
                        ->label('Salario (Bs)')
                        //->required()
                        ->numeric()
                        ->prefix('Bs')
                        ->step(0.01)
                        ->placeholder('Ej: 3500.50'),

                    TextInput::make('seguro_medico')
                        ->label('Seguro Médico')
                        ->disabled()
                        ->dehydrated()
                        ->prefixIcon('heroicon-o-heart'),

                    TextInput::make('correo_corporativo')
                        ->label('Correo Corporativo')
                        ->email()
                        ->prefixIcon('heroicon-o-envelope-open')
                        ->required()
                        ->reactive()
                        ->afterStateHydrated(function (Set $set) {
                            $empleado = $this->getOwnerRecord();
                            if ($empleado) {
                                $set('correo_corporativo', $this->generarCorreoCorporativoDesdeEmpleado($empleado));
                            }
                        })
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $empleado = $this->getOwnerRecord();
                            if ($empleado) {
                                $set('correo_corporativo', $this->generarCorreoCorporativoDesdeEmpleado($empleado, $get('empresa_id')));
                            }
                        }),

                    TextInput::make('numero_corporativo')
                        ->label('Número Corporativo')
                        ->prefixIcon('heroicon-o-phone'),
                ]),

            Section::make('Documento')
                ->description('Adjunta un PDF del contrato (max 15mb)')
                ->icon('heroicon-o-document')
                ->columns(1)
                ->schema([
                    FileUpload::make('documento')
                        ->label('Contrato en PDF')
                        ->directory('contratos')        // Carpeta dentro del disco 'public'
                        ->disk('public')                // Disco de almacenamiento
                        //->visibility('public')          // Público
                        ->openable()                    // Permite abrir en nueva pestaña
                        //->downloadable()                // Permite descargar
                        ->loadingIndicatorPosition('center')
                        ->removeUploadedFileButtonPosition('upper-center')
                        ->uploadButtonPosition('right')
                        ->uploadProgressIndicatorPosition('right')
                        ->acceptedFileTypes(['application/pdf']) // Solo PDF
                        ->maxSize(15120)                           // 15MB máximo
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                            $ci = $get('ci') ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('ci')) : 'contrato_' . uniqid();
                            return $ci . '.' . $file->getClientOriginalExtension();
                        })
                ]),

            Section::make('Observaciones')
                ->schema([
                    Textarea::make('observaciones')
                        ->label('Observaciones')

                        ->placeholder('Comentarios adicionales, motivos de contrato, condiciones, etc.')
                        ->columnSpanFull()
                        ->rows(6),
                ])->icon('heroicon-o-heart'),
            Hidden::make('activo')
                ->default(true) //siempre se crea como activo
                ->dehydrated(true) // asegura que se envíe al guardar;
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn($query) => $query->latest('id'))
            ->recordTitleAttribute('cargo_id')
            ->columns([
                TextColumn::make('empresa.razon_social')
                    ->label('Empresa')
                    ->searchable(),

                //Cargo desde la relación con la tabla cargos
                TextColumn::make('cargo.nombre')
                    ->label('Cargo')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('tipo_contrato')
                    ->label('Contrato')
                    ->badge()
                    ->color('info'),

                TextColumn::make('salario')
                    ->label('Salario')
                    ->money('BOB', true),

                TextColumn::make('fecha_inicio')
                    ->label('Inicio contrato')
                    ->date('d/m/Y'),

                TextColumn::make('fecha_fin')
                    ->label('Fin contrato')
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) {
                            return '-';
                        }

                        $fechaFin = Carbon::parse($state)->format('d/m/Y');

                        // Si no está activo, solo mostramos la fecha en gris sin cálculo
                        if (!$record->activo) {
                            return $fechaFin;
                        }

                        $hoy = Carbon::today();
                        $diasRestantes = $hoy->diffInDays(Carbon::parse($state), false);

                        if ($diasRestantes < 0) {
                            return "{$fechaFin} (Vencido)";
                        } elseif ($diasRestantes <= 15) {
                            return "{$fechaFin} (Faltan {$diasRestantes} días)";
                        }

                        return $fechaFin;
                    })
                    ->badge()
                    ->color(function ($state, $record) {
                        if (!$state || !$record->activo) {
                            return 'gray'; // siempre gris si no está activo
                        }

                        $fechaFin = Carbon::parse($state);
                        $hoy = Carbon::today();
                        $diasRestantes = $hoy->diffInDays($fechaFin, false);

                        return match (true) {
                            $diasRestantes < 0 => 'danger',
                            $diasRestantes <= 15 => 'warning',
                            default => 'gray',
                        };
                    }),

                TextColumn::make('sucursal.nombre')
                    ->label('Sucursal')
                    ->badge()
                    ->color('info'),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->icon(fn($state) => match (true) {
                        $state === true => 'heroicon-o-check-circle',
                        $state === false => 'heroicon-o-x-circle',
                        default => 'heroicon-o-minus-circle',
                    })
                    ->color(fn($state) => match (true) {
                        $state === true => 'success',
                        $state === false => 'danger',
                        default => 'gray',
                    })
                    ->tooltip(fn($state) => match (true) {
                        $state === true => 'Contrato vigente',
                        $state === false => 'Contrato inactivo',
                        default => 'Sin estado',
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar Situación Laboral')
                    ->icon('heroicon-o-folder-plus')
                    ->createAnother(false)
                    ->visible(function ($livewire) {
                        $empleado = $livewire->getOwnerRecord(); // obtiene el empleado actual
                        return $empleado && $empleado->activo == true;
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()->visible(fn($record) => $record->activo === true),
            ])
            ->searchable(false);
    }

    // Obtiene los datos del empleado y genera el correo
    protected function generarCorreoCorporativoDesdeEmpleado($empleado, $empresaId = null): ?string
    {
        $nombres = $empleado?->nombres ?? '';
        $apellidos = $empleado?->apellidos ?? '';

        if (empty($nombres) || empty($apellidos)) {
            return null;
        }

        $primerNombre = strtolower(strtok($nombres, ' '));
        $primerApellido = strtolower(strtok($apellidos, ' '));

        // Limpiar acentos y caracteres especiales
        $primerNombre = preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $primerNombre));
        $primerApellido = preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $primerApellido));

        // Si no se pasa un ID, intenta obtenerlo desde el campo empresa_id del historial laboral
        if (!$empresaId && method_exists($this, 'getMountedTableActionRecord')) {
            $registro = $this->getMountedTableActionRecord();
            $empresaId = $registro?->empresa_id;
        }

        // Si aún no se encuentra el ID, intentamos desde la relación de historial laboral
        if (!$empresaId && $empleado?->historialLaboral?->isNotEmpty()) {
            $empresaId = $empleado->historialLaboral->last()?->empresa_id;
        }

        // Buscar la empresa
        $empresa = $empresaId ? Empresa::find($empresaId) : null;

        // Obtener dominio
        $dominio = $empresa?->sitio_web ?? 'empresa.com';

        // Limpiar dominio
        $dominio = preg_replace('#^https?://#', '', trim($dominio));
        $dominio = rtrim($dominio, '/');

        // Asegurarse de que tenga algo útil
        if (!str_contains($dominio, '.')) {
            $dominio = 'empresa.com';
        }
        return "{$primerNombre}.{$primerApellido}@{$dominio}";
    }
}