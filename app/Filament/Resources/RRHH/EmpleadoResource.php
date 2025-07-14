<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\EmpleadoResource\Pages;
use App\Models\RRHH\Empleado;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Actions\Action;
use Filament\Notifications\Notification;

class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Empleados'; //Seccion para configurar el nombre en Filament-Shield
    protected static ?string $pluralModelLabel = 'Listado de Empleados';
    protected static ?string $navigationLabel = 'Empleados';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 1;

    // Método para ocultar el recurso del navigation a los empelados
    public static function shouldRegisterNavigation(): bool
    {
        // Solo mostrar en el navigation si el usuario NO tiene el rol 'Empleado'
        $user = Auth::user();
        return $user && !$user->hasRole('Empleado') && $user->roles->isNotEmpty();
    }

    // Obtiene los datos del empleado y genera el correo
    protected static function generarCorreoCorporativo(Get $get): ?string
    {
        // Validar que los campos requeridos existan
        if (empty($get('nombres')) || empty($get('apellidos')) || empty($get('empresa'))) {
            return null;
        }

        $nombres = explode(' ', $get('nombres'));
        $apellidos = explode(' ', $get('apellidos'));

        // Obtener primer nombre y apellido
        $primerNombre = strtolower($nombres[0]);
        $primerApellido = strtolower($apellidos[0]);

        // Limpiar caracteres especiales y acentos
        $primerNombre = preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $primerNombre));
        $primerApellido = preg_replace('/[^a-z0-9]/', '', iconv('UTF-8', 'ASCII//TRANSLIT', $primerApellido));

        // Validar y limpiar nombre de empresa
        $empresa = strtolower($get('empresa'));
        $empresa = preg_replace('/[^a-z0-9]/', '', $empresa); // Eliminar caracteres no válidos
        $empresa = $empresa ?: 'novanexa'; // Default si está vacío

        return "{$primerNombre}.{$primerApellido}@{$empresa}.com.bo";
    }

    //Formulario de creacion edicion de empleados
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección superior (Card empleado)con foto y datos básicos
                Grid::make()
                    ->schema([
                        FileUpload::make('foto')
                            ->label('')
                            ->image()
                            ->directory('empleados')
                            ->disk('public')
                            ->visibility('public')
                            ->imageEditor()
                            ->openable()
                            ->downloadable()
                            ->loadingIndicatorPosition('center')
                            ->panelAspectRatio('1:1')
                            ->removeUploadedFileButtonPosition('upper-center')
                            ->uploadButtonPosition('right')
                            ->uploadProgressIndicatorPosition('right')
                            ->panelLayout('circle')    // Layout especial para avatares
                            ->extraAttributes([
                                'style' => '
                                    width: 300px; 
                                    height: 300px;
                                    margin: 0 auto; /* Centrado horizontal */
                                    display: flex; /* Para centrado vertical si es necesario */
                                    justify-content: center;
                                ',
                                'class' => 'flex flex-col items-center' // Clases de Tailwind para respaldo
                            ])
                            ->imageCropAspectRatio('1:1')  // Relación de aspecto cuadrada
                            ->default(fn($record) => $record?->foto)
                            ->alignCenter()
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file, Get $get): string {
                                    $ci = $get('ci') ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('ci')) : 'default_' . uniqid();
                                    return $ci . '.' . $file->getClientOriginalExtension();
                                }
                            )
                            ->placeholder(function ($get) {
                                // Si no hay foto, mostrar iniciales con avatar por defecto
                                $nombres = $get('nombres') ?? '';
                                $apellidos = $get('apellidos') ?? '';
                                $iniciales = substr($nombres, 0, 1) . substr($apellidos, 0, 1);

                                return view('filament.forms.components.avatar-placeholder', [
                                    'iniciales' => $iniciales ?: 'NA',
                                    'defaultImage' => asset('images/default-avatar.jpg')
                                ]);
                            }),

                        Grid::make()
                            ->schema([
                                Placeholder::make('nombre_completo')
                                    ->label('👤Nombre:')
                                    ->content(fn($get) => $get('nombres') . ' ' . $get('apellidos'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('ci/dni')
                                    ->label('🪪CI/DNI:')
                                    ->content(fn($get) => ' ' . $get('ci'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('email')
                                    ->label('📧Email:')
                                    ->content(fn($get) => ' ' . $get('correo_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('numero_coporativo')
                                    ->label('📱Teléfono:')
                                    ->content(fn($get) => ' ' . $get('numero_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Toggle::make('activo')
                                    ->default(true)
                                    ->label(fn($state) => $state ? 'Empleado Activo' : 'Empleado Inactivo')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (!$state) {
                                            $set('fecha_desvinculacion', now()->format('Y-m-d'));
                                            Notification::make()
                                                ->title('Desvinculación registrada')
                                                ->success()
                                                ->send();
                                        } else {
                                            $set('fecha_desvinculacion', null);
                                        }
                                    })
                                    ->columnSpanFull()
                                    ->extraAttributes(['class' => 'flex justify-center']),
                            ])
                            ->columnSpan(['md' => 2, 'lg' => 1])
                            ->extraAttributes(['class' => 'flex flex-col justify-center']),
                    ])
                    ->columns(['md' => 2, 'lg' => 2])
                    ->columnSpan('full'),

                // Sección de información básica
                Section::make('Información Básica')
                    ->schema([
                        TextInput::make('nombres')
                            ->required()
                            ->maxLength(255)
                            ->hint('Nombres completos del empleado')
                            ->hintIcon('heroicon-o-user')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('correo_corporativo', static::generarCorreoCorporativo($get));
                            })
                            ->dehydrateStateUsing(fn($state) => ucwords(strtolower($state))),

                        TextInput::make('apellidos')
                            ->required()
                            ->maxLength(255)
                            ->hint('Apellidos completos del empleado')
                            ->hintIcon('heroicon-o-user')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('correo_corporativo', static::generarCorreoCorporativo($get));
                            })
                            ->dehydrateStateUsing(fn($state) => ucwords(strtolower($state))),

                        TextInput::make('ci')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->label('Cédula de Identidad')
                            ->hint('Número único de identificación')
                            ->hintIcon('heroicon-o-identification'),

                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de Nacimiento')
                            ->hint('Fecha de nacimiento del empleado')
                            ->hintIcon('heroicon-o-cake'),

                        Select::make('genero')
                            ->options([
                                'hombre' => 'Hombre',
                                'mujer' => 'Mujer',
                                'Otro' => 'Otro',
                            ])
                            ->hint('Género del empleado')
                            ->hintIcon('heroicon-o-user-circle'),

                        TextInput::make('nacionalidad')
                            ->required()
                            ->default('Boliviana')
                            ->hint('Nacionalidad del empleado')
                            ->hintIcon('heroicon-o-flag'),

                        Fieldset::make('Direccion y croquis de domicilio')
                            ->schema([
                                TextInput::make('direccion')
                                    ->maxLength(255)
                                    ->label('Dirección completa')
                                    ->hint('Busque en el mapa la ubicacion de su domicilio')
                                    ->hintIcon('heroicon-o-exclamation-triangle'),

                                // Campo para el mapa (interactivo)                                
                                Field::make('ubicacion_gps')
                                    ->label('Ubicación GPS')

                                    ->view('filament.forms.components.map-picker'),
                            ])
                            ->columns(1),
                    ])
                    ->columns(2),

                // Sección de datos personales adicionales
                Section::make('Datos Personales Adicionales')
                    ->schema([
                        Select::make('estado_civil')
                            ->options([
                                'soltero' => 'Soltero/a',
                                'casado' => 'Casado/a',
                                'viudo' => 'Viudo/a',
                                'divorciado' => 'Divorciado/a',
                            ])
                            ->label('Estado Civil')
                            ->hint('Estado civil actual del empleado')
                            ->hintIcon('heroicon-o-heart'),

                        TextInput::make('cantidad_hijos')
                            ->default(0)
                            ->numeric()
                            ->label('Número de Hijos')
                            ->hint('Cantidad de hijos del empleado')
                            ->hintIcon('heroicon-o-user-group'),

                        TextInput::make('telefono_personal')
                            ->tel()
                            ->label('Teléfono Personal')
                            ->hint('Número de contacto personal')
                            ->hintIcon('heroicon-o-phone'),

                        TextInput::make('correo_personal')
                            ->email()
                            ->label('Correo Personal')
                            ->hint('Correo electrónico personal')
                            ->hintIcon('heroicon-o-envelope'),

                        Fieldset::make('Contacto de Emergencia')

                            ->schema([
                                TextInput::make('persona_contacto')
                                    ->label('Nombre de contacto')
                                    ->hint('Persona a contactar en caso de emergencia')
                                    ->hintIcon('heroicon-o-exclamation-triangle'),

                                TextInput::make('numero_contacto')
                                    ->tel()
                                    ->label('Teléfono de contacto')
                                    ->hint('Número de la persona de emergencia')
                                    ->hintIcon('heroicon-o-phone'),

                                TextInput::make('persona_parentesco')
                                    ->label('Parentesco de contacto')
                                    ->hint('Parentesco de la persona')
                                    ->hintIcon('heroicon-o-exclamation-triangle'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(2),

                // Sección de datos laborales
                Section::make('Datos Laborales')
                    //->disabled()
                    ->schema([
                        DatePicker::make('fecha_ingreso')
                            ->required()
                            ->label('Fecha de Ingreso')
                            ->hint('Fecha en que el empleado se incorporó a la empresa')
                            ->hintIcon('heroicon-o-calendar'),

                        DatePicker::make('fecha_desviculacion')
                            ->label('Desvinculación')
                            ->hint('Fecha en que el empleado dejó la empresa')
                            ->hintIcon('heroicon-o-calendar')
                            ->hidden(fn(Get $get) => $get('activo')),

                        Select::make('empresa')
                            ->required()
                            ->options([
                                'Novanexa' => 'Novanexa',
                                'Ireilab' => 'Ireilab',
                                'Requilab' => 'Requilab',
                            ])
                            ->live()
                            ->hint('Empresa a la que pertenece el empleado')
                            ->hintIcon('heroicon-o-building-library')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $set('correo_corporativo', static::generarCorreoCorporativo($get));
                            }),

                        Select::make('estado_contrato')
                            ->required()
                            ->options([
                                'Contrato plazo fijo' => 'Contrato plazo fijo',
                                'Contrato indefinido' => 'Contrato indefinido',
                                'Contrato por servicios' => 'Contrato por servicios',
                                'Contrato por obra' => 'Contrato por obra',
                                'Planta' => 'Planta',
                                'Pasante' => 'Pasante',
                                'Periodo de prueba' => 'Periodo de prueba',
                                'Otro' => 'Otro tipo',
                            ])
                            ->default('Otro')
                            ->label('Estado de Contrato')
                            ->hint('Situación actual del contrato laboral')
                            ->hintIcon('heroicon-o-document-text'),

                        TextInput::make('salario')
                            ->numeric()
                            ->prefix('Bs.')
                            ->label('Salario Mensual')
                            ->hint('Salario sin descuentos')
                            ->hintIcon('heroicon-o-currency-dollar')
                            ->required(),

                        Select::make('cargo')
                            ->required()
                            ->label('Cargo')
                            ->hint('Puesto o función actual del empleado')
                            ->hintIcon('heroicon-o-briefcase')
                            ->options([
                                'Analista de Licitaciones' => 'Analista de Licitaciones',
                                'Aplicaciones y Asesora Bioquímica' => 'Aplicaciones y Asesora Bioquímica',
                                'Asesor Bioquímico Comercial' => 'Asesor Bioquímico Comercial',
                                'Asesor Bioquímico Aplicacionista' => 'Asesor Bioquímico Aplicacionista',
                                'Asistente Administrativo' => 'Asistente Administrativo',
                                'Asistente de Contabilidad' => 'Asistente de Contabilidad',
                                'Asistente de Licitaciones' => 'Asistente de Licitaciones',
                                'Auxiliar Administrativo y Comercial' => 'Auxiliar Administrativo y Comercial',
                                'Auxiliar Contable' => 'Auxiliar Contable',
                                'Auxiliar de Almacén' => 'Auxiliar de Almacén',
                                'Auxiliar Técnico' => 'Auxiliar Técnico',
                                'Contador' => 'Contador',
                                'Encargado Nacional de Almacén' => 'Encargado Nacional de Almacén',
                                'Encargado de Almacén' => 'Encargado de Almacén',
                                'Encargado de Licitaciones' => 'Encargado de Licitaciones',
                                'Encargado Regional' => 'Encargado Regional',
                                'Encargado de Contabilidad' => 'Encargado de Contabilidad',
                                'Encargado de Recursos Humanos' => 'Encargado de Recursos Humanos',
                                'Encargado de Logistica e Importaciones' => 'Encargado de Logistica e Importaciones',
                                'Encargado de Tecnologías de la Información' => 'Encargado de Tecnologías de la Información',
                                'Ejecutivo de Ventas' => 'Ejecutiva de Ventas',
                                'Gerente Administrativo Financiero' => 'Gerente Administrativo Financiero',
                                'Gerente Ejecutivo' => 'Gerente Ejecutivo',
                                'Gerente General' => 'Gerente General',
                                'Gerente de Importaciones' => 'Gerente de Importaciones',
                                'Gerente Operativa' => 'Gerente Operativa',
                                'Mensajería' => 'Mensajería',
                                'Regente Farmacéutico' => 'Regente Farmacéutico',
                                'Auxiliar Técnico' => 'Auxiliar Técnico'
                            ])
                            ->searchable()
                            ->native(false),

                        Select::make('sucursal')
                            ->label('Sucursal/Departamento')
                            ->required()
                            ->options([
                                'La Paz' => 'La Paz',
                                'Santa Cruz' => 'Santa Cruz',
                                'Cochabamba' => 'Cochabamba',
                                'Oruro' => 'Oruro',
                                'Potosí' => 'Potosí',
                                'Tarija' => 'Tarija',
                                'Sucre' => 'Sucre',
                                'Beni' => 'Beni',
                                'Pando' => 'Pando',
                            ])
                            ->hint('Ubicación donde trabaja el empleado')
                            ->hintIcon('heroicon-o-building-office')
                            ->searchable()  //  permite buscar entre las opciones
                            ->native(false), // Opcional: mejora la UI en algunos navegadores

                        Fieldset::make('Contacto empresarial')

                            ->schema([
                                TextInput::make('correo_corporativo')
                                    ->required()
                                    ->email()
                                    ->label('Correo Corporativo')
                                    ->hint('Correo electrónico asignado por la empresa')
                                    ->hintIcon('heroicon-o-envelope')
                                    ->default(function (Get $get) {
                                        return static::generarCorreoCorporativo($get);
                                    })
                                    ->dehydrated()

                                    ->afterStateUpdated(function (Get $get, Set $set) {
                                        $set('correo_corporativo', static::generarCorreoCorporativo($get));
                                    }),

                                TextInput::make('numero_corporativo')
                                    ->tel()
                                    ->label('Teléfono Corporativo')
                                    ->hint('Teléfono proporcionado por la empresa')
                                    ->hintIcon('heroicon-o-phone'),
                            ])
                            ->columns(2),

                        Fieldset::make('Datos adicionales')

                            ->schema([
                                TextInput::make('afp')
                                    ->label('Nombre de Gestora')
                                    ->hint('Nombre de afiliación AFP')
                                    ->default('Gestora Pública')
                                    ->hintIcon('heroicon-o-banknotes'),

                                TextInput::make('nua_cua')
                                    ->label('Numero NUA/CUA')
                                    ->hint('Afiliación al seguro social')
                                    ->hintIcon('heroicon-o-shield-check'),

                                Select::make('caja_salud')
                                    ->options([
                                        'Caja Caminos' => 'Caja Caminos',
                                        'Caja Petrolera' => 'Caja Petrolera',
                                        'Caja Seguro Universitario' => 'Caja Cordes',
                                    ])
                                    ->label('Caja de Salud')
                                    ->hint('Caja de salud a la que está afiliado')
                                    ->hintIcon('heroicon-o-heart'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        // Construir la consulta base
        $baseQuery = Empleado::query()
            ->orderBy('sucursal')
            ->orderBy('apellidos')
            ->orderBy('nombres');

        // Si el usuario tiene rol "administracion regional", filtrar por su sucursal
        if ($user->hasRole('Administracion Regional')) {
            // Obtener la sucursal del usuario actual (asumiendo que está asociado a un empleado)
            $empleadoUsuario = Empleado::where('correo_corporativo', $user->email)->first();

            if ($empleadoUsuario && $empleadoUsuario->sucursal) {
                $baseQuery->where('sucursal', $empleadoUsuario->sucursal);
                Log::debug('Filtrando por sucursal para administración regional', [
                    'sucursal' => $empleadoUsuario->sucursal
                ]);
            }
        }
        //Contruccion de la tabla principal que muetre a los empleados
        return $table
            ->query($baseQuery)
            ->columns([
                ImageColumn::make('foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(function ($record) {
                        // Mostrar la foto del empleado si existe, de lo contrario el avatar por defecto
                        return $record->foto ? asset('storage/' . $record->foto) : asset('images/default-avatar.jpg');
                    }),

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
                    ->searchable()
                    ->sortable()
                    ->description((fn(Empleado $record) => $record->sucursal))
                    ->searchable(['empresa', 'sucursal']),


                TextColumn::make('estado_contrato')
                    ->label('Contrato')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Contrato plazo fijo' => 'info',
                        'Contrato indefinido' => 'success',
                        'Contrato por servicios' => 'warning',
                        'Contrato por obra' => 'warning',
                        'Planta' => 'success',
                        'Pasante' => 'gray',
                        'Periodo de prueba' => 'danger',
                        'Otro' => 'danger',
                        default => 'gray',
                    })
                    ->description((fn(Empleado $record) => $record->cargo))
                    ->searchable(['estado_contrato', 'cargo']),

                TextColumn::make('fecha_ingreso')
                    ->label('Fechas')
                    ->formatStateUsing(function ($record) {
                        $textoFechaIngreso = $record->fecha_ingreso?->format('d/m/Y') ?? 'Sin fecha';

                        if ($record->fecha_desvinculacion) {
                            $textoFechaDesvinculacion = $record->fecha_desvinculacion->format('d/m/Y');
                            return "<div>{$textoFechaIngreso}<br><span class='text-red-500 text-xs'>Desvinculado: {$textoFechaDesvinculacion}</span></div>";
                        }

                        return $textoFechaIngreso;
                    })
                    ->html()
                    ->sortable(),

                TextColumn::make('salario')
                    ->label('Salario')
                    ->money('BOB')
                    ->sortable(),

                TextColumn::make('coordenadas.texto')
                    ->label('Ubicación Domicilio')
                    ->url(fn($record) => isset($record->coordenadas['lat'], $record->coordenadas['lng'])
                        ? 'https://www.google.com/maps?q=' . $record->coordenadas['lat'] . ',' . $record->coordenadas['lng']
                        : null)
                    ->openUrlInNewTab()
                    ->getStateUsing(fn($record) => isset($record->coordenadas['lat'], $record->coordenadas['lng'])
                        ? '🗺️ Ver Croquis'
                        : '❌ No registro Croquis'),

                ToggleColumn::make('activo')
                    ->label('Estado')
                    ->sortable()
                    ->afterStateUpdated(function ($record, $state) {
                        if (!$state) {
                            // Establecer fecha de desvinculación si se desactiva
                            $record->fecha_desvinculacion = now();
                            $record->save();

                            // Mostrar notificación
                            Notification::make()
                                ->title('Empleado desvinculado')
                                ->body("Se registró la desvinculación el " . now()->format('d/m/Y'))
                                ->success()
                                ->send();
                        } else {
                            // Limpiar fecha si se reactiva
                            $record->fecha_desvinculacion = null;
                            $record->save();

                            Notification::make()
                                ->title('Empleado reactivado')
                                ->success()
                                ->send();
                        }
                    })
                    ->tooltip(fn($record) => $record->activo
                        ? 'Empleado activo'
                        : 'Desvinculado el ' . ($record->fecha_desvinculacion?->format('d/m/Y') ?? 'sin fecha'))
                    ->updateStateUsing(function ($record, $state) {
                        // Actualizar el estado sin guardar aún
                        $record->activo = $state;
                        return $state;
                    }),
            ])

            //Filtros de Busqueda
            ->filters([
                Tables\Filters\SelectFilter::make('empresa')
                    ->options([
                        'Novanexa' => 'Novanexa',
                        'Ireilab' => 'Ireilab',
                        'Requilab' => 'Requilab',
                    ]),

                Tables\Filters\SelectFilter::make('estado_contrato')
                    ->options([
                        'Contrato plazo fijo' => 'Contrato plazo fijo',
                        'Contrato indefinido' => 'Contrato indefinido',
                        'Contrato por servicios' => 'Contrato por servicios',
                        'Contrato por obra' => 'Contrato por obra',
                        'Planta' => 'Planta',
                        'Pasante' => 'Pasante',
                        'Periodo de prueba' => 'Periodo de prueba',
                        'Otro' => 'Otro tipo',
                    ])
                    ->label('Tipo de Contrato'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo')
                    ->default(true),
            ])
            ->actions([])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 'all']) // Opciones de paginación disponibles
            ->defaultPaginationPageOption(100)
            ->striped();
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpleados::route('/'),
            'create' => Pages\CreateEmpleado::route('/create'),
            'edit' => Pages\EditEmpleado::route('/{record}/edit'),
        ];
    }
}