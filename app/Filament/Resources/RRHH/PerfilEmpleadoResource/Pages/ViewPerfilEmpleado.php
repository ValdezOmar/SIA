<?php

namespace App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;

use App\Models\RRHH\PerfilEmpleado;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\RRHH\PerfilEmpleadoResource;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Log;

class ViewPerfilEmpleado extends ViewRecord
{
    protected static string $resource = PerfilEmpleadoResource::class;

    protected static ?string $title = 'Mi Perfil';
    public ?array $ubicacion_gps;

    public function mount(int|string $record): void
    {
        $user = Auth::user();
        $empleado = PerfilEmpleado::where('correo_corporativo', $user->email)->first();

        abort_unless($empleado && $empleado->id == $record, 403);

        parent::mount($record);
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        $empleado = PerfilEmpleado::where('correo_corporativo', $user->email)->first();

        return $empleado !== null;
    }

    // Sobrescribe el método can() para ignorar los permisos
    public static function can(\Filament\Resources\Resource | string $resource): bool
    {
        return true;
    }

    //Este formulario es el que se muestra en la seccion derecha superior en MI PERFIL
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([
                        // Sección superior con foto y datos básicos
                        FileUpload::make('foto')
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

                            ->hint('Nombres completos del empleado')
                            ->hintIcon('heroicon-o-user')
                            ->live(),

                        TextInput::make('apellidos')

                            ->hint('Apellidos completos del empleado')
                            ->hintIcon('heroicon-o-user')
                            ->live(),

                        TextInput::make('ci')
                            ->label('Cédula de Identidad')
                            ->hint('Número único de identificación')
                            ->hintIcon('heroicon-o-identification'),

                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de Nacimiento')
                            ->hint('Fecha de nacimiento del empleado')
                            ->hintIcon('heroicon-o-cake'),

                        TextInput::make('genero')
                            ->hint('Género del empleado')
                            ->hintIcon('heroicon-o-user-circle'),

                        TextInput::make('nacionalidad')
                            ->hint('Nacionalidad del empleado')
                            ->hintIcon('heroicon-o-flag'),

                        //croquis
                        Fieldset::make('Direccion y croquis de domicilio')
                            ->schema([
                                TextInput::make('direccion')
                                    ->label('Dirección completa'),
                                // Campo para el mapa (interactivo)                                
                                Field::make('ubicacion_gps')
                                    ->label('Ubicación GPS')
                                    ->view('filament.forms.components.map-picker')
                                    ->live()
                                    ->afterStateHydrated(function ($state, $record) {
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
                                        return $result;
                                    })
                                    ->dehydrateStateUsing(function ($state) {
                                        return is_array($state) ? $state : (json_decode($state, true) ?? ['lat' => -16.504759, 'lng' => -68.119124]);
                                    }),
                            ])
                            ->columns(1),
                    ])
                    ->columns(2),
                // Sección de datos personales adicionales
                Section::make('Datos Personales Adicionales')
                    ->schema([
                        TextInput::make('estado_civil')
                            ->label('Estado Civil')
                            ->hint('Estado civil actual del empleado')
                            ->hintIcon('heroicon-o-heart'),

                        TextInput::make('cantidad_hijos')
                            ->label('Número de Hijos')
                            ->hint('Cantidad de hijos del empleado')
                            ->hintIcon('heroicon-o-user-group'),

                        TextInput::make('telefono_personal')
                            ->label('Teléfono Personal')
                            ->hint('Número de contacto personal')
                            ->hintIcon('heroicon-o-phone'),

                        TextInput::make('correo_personal')
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
                    ->schema([
                        DatePicker::make('fecha_ingreso')
                            ->label('Fecha de Ingreso')
                            ->hint('Fecha en que el empleado se incorporó a la empresa')
                            ->hintIcon('heroicon-o-calendar'),

                        TextInput::make('empresa')
                            ->hint('Empresa a la que pertenece el empleado')
                            ->hintIcon('heroicon-o-building-library'),

                        TextInput::make('cargo')
                            ->label('Cargo')
                            ->hint('Puesto o función actual del empleado')
                            ->hintIcon('heroicon-o-briefcase'),

                        TextInput::make('sucursal')
                            ->label('Sucursal/Departamento'),

                        Fieldset::make('Contacto empresarial')
                            ->schema([
                                TextInput::make('correo_corporativo')
                                    ->label('Correo Corporativo')
                                    ->hint('Correo electrónico asignado por la empresa')
                                    ->hintIcon('heroicon-o-envelope'),

                                TextInput::make('numero_corporativo')
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

                                TextInput::make('caja_salud')
                                    ->label('Caja de Salud')
                                    ->hint('Caja de salud a la que está afiliado')
                                    ->hintIcon('heroicon-o-heart'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(2),

            ]);
    }
}