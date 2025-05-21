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

class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Empleado';
    protected static ?string $pluralModelLabel = 'Listado de Empleados';
    protected static ?string $navigationLabel = 'Empleados';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 1;

    // 1. Método para ocultar el recurso del navigation
    public static function shouldRegisterNavigation(): bool
    {
        // Solo mostrar en el navigation si el usuario NO tiene el rol 'Empleado'
        return !Auth::user()->hasRole('Empleado');
    }

    // 2. Método para controlar el acceso a todas las páginas del recurso
    public static function canViewAny(): bool
    {
        // Solo permitir acceso si el usuario NO tiene el rol 'Empleado'
        return !Auth::user()->hasRole('Empleado');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección superior con foto y datos básicos
                Grid::make()
                    ->schema([
                        FileUpload::make('foto')
                            ->image()
                            ->directory('empleados')
                            ->imageEditor()
                            ->circleCropper()
                            ->imagePreviewHeight('150')
                            ->openable()
                            ->downloadable()
                            ->label('')
                            ->placeholder(function ($get) {
                                // Si hay foto cargada, no mostrar placeholder
                                if ($get('foto')) {
                                    return null;
                                }

                                $nombres = $get('nombres') ?? '';
                                $apellidos = $get('apellidos') ?? '';
                                $iniciales = substr($nombres, 0, 1) . substr($apellidos, 0, 1);

                                return view('filament.forms.components.avatar-placeholder', [
                                    'iniciales' => $iniciales ?: 'NA',
                                    'defaultImage' => asset('images/default-avatar.jpg')
                                ]);
                            })
                            ->extraAttributes(['class' => 'border-2 border-gray-200 rounded-full p-1 mx-auto'])
                            ->columnSpan(['md' => 2, 'lg' => 1]),

                        Grid::make()
                            ->schema([
                                Placeholder::make('nombre_completo')
                                    ->label('Nombre Empleado:')
                                    ->content(fn($get) => $get('nombres') . ' ' . $get('apellidos'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('ci/dni')
                                    ->label('CI/DNI:')
                                    ->content(fn($get) => ' ' . $get('ci'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold']),

                                Placeholder::make('email')
                                    ->label('Email empresa:')
                                    ->content(fn($get) => ' ' . $get('correo_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('numero_coporativo')
                                    ->label('Teléfono Corporativo:')
                                    ->content(fn($get) => ' ' . $get('numero_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold']),

                                Toggle::make('activo')
                                    ->default(true)
                                    ->label('Empleado activo')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if (!$state) {
                                            $set('fecha_desviculacion', now()->format('Y-m-d'));
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
                            ->live(),

                        TextInput::make('apellidos')
                            ->required()
                            ->maxLength(255)
                            ->hint('Apellidos completos del empleado')
                            ->hintIcon('heroicon-o-user')
                            ->live(),

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
                                'otro' => 'Otro',
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
                                    ->view('filament.forms.components.map-picker')
                                    ->live()
                                    ->afterStateHydrated(function ($state, $record) {
                                        Log::debug('Estado hidratado - Raw:', [
                                            'raw_state' => $record?->ubicacion_gps,
                                            'state_input' => $state
                                        ]);

                                        // Transformación garantizada
                                        if (is_string($state)) {
                                            try {
                                                $result = json_decode($state, true) ?? ['lat' => -16.504759, 'lng' => -68.119124];
                                                Log::debug('Transformación de string a array:', $result);
                                                return $result;
                                            } catch (\Exception $e) {
                                                Log::error('Error decodificando JSON:', ['error' => $e->getMessage()]);
                                                return ['lat' => -16.504759, 'lng' => -68.119124];
                                            }
                                        }

                                        $result = is_array($state) ? $state : ['lat' => -16.504759, 'lng' => -68.119124];
                                        Log::debug('Estado procesado final:', $result);
                                        return $result;
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        Log::debug('Deshidratando estado para guardar:', $state);
                                        return is_array($state) ? $state : (json_decode($state, true) ?? ['lat' => -16.504759, 'lng' => -68.119124]);
                                    }),
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
                            ->hint('Empresa a la que pertenece el empleado')
                            ->hintIcon('heroicon-o-building-library'),

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
                                'otro' => 'Otro tipo',
                            ])
                            ->default('otro')
                            ->label('Estado de Contrato')
                            ->hint('Situación actual del contrato laboral')
                            ->hintIcon('heroicon-o-document-text'),

                        TextInput::make('salario')
                            ->numeric()
                            ->prefix('Bs.')
                            ->label('Salario Mensual')
                            ->hint('Si el dato no es el correcto comuniquese con RRHH')
                            ->hintIcon('heroicon-o-currency-dollar'),

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
                                'Chuquisaca' => 'Chuquisaca',
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
                                    ->hintIcon('heroicon-o-envelope'),

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
        return $table
            ->columns([
                ImageColumn::make('foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(asset('images/default-avatar.jpg')),

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
                        'otro' => 'danger',
                        default => 'gray',
                    })
                    ->description((fn(Empleado $record) => $record->cargo))
                    ->searchable(['estado_contrato', 'cargo']),

                TextColumn::make('salario')
                    ->label('Salario')
                    ->money('BOB')
                    ->sortable(),

                TextColumn::make('coordenadas.texto')
                    ->label('Ubicación Domicilio')
                    ->url(fn($record) => $record->coordenadas ?
                        'https://www.google.com/maps?q=' . $record->coordenadas['lat'] . ',' . $record->coordenadas['lng'] : null)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn($state) => $state ? '📍 ' . $state : ''),

                ToggleColumn::make('activo')
                    ->label('Activo')
                    ->sortable(),
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
                        'otro' => 'Otro tipo',
                    ])
                    ->label('Tipo de Contrato'),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // En EmpleadoResource.php
    protected static function getPermissionPrefix(): string
    {
        return 'admin_empleados_'; // Prefijo administrativo
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
