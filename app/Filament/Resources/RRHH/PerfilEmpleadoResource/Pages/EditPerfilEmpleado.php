<?php

namespace App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;

use App\Models\RRHH\Empleado;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\RRHH\PerfilEmpleadoResource;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Forms\Components\View;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditPerfilEmpleado extends EditRecord
{
    protected static string $resource = PerfilEmpleadoResource::class;

    protected static ?string $title = 'Mi Perfil';
    public ?array $ubicacion_gps = null;    

    //Funcion para guardar el array de gps
    public function mutateFormDataBeforeSave(array $data): array
    {
        if (is_array($this->ubicacion_gps)) {
            $lat = round(floatval($this->ubicacion_gps['lat'] ?? 0), 6);
            $lng = round(floatval($this->ubicacion_gps['lng'] ?? 0), 6);

            // Si la ubicación es la predeterminada, guarda como null
            if ($lat == -16.500000 && $lng == -68.150000) {
                $data['ubicacion_gps'] = null;
            } else {
                $data['ubicacion_gps'] = [
                    'lat' => $lat,
                    'lng' => $lng,
                ];
            }
        } else {
            $data['ubicacion_gps'] = null;
        }

        return $data;
    }

    public function mount(int|string $record): void
    {
        $user = Auth::user();
        $empleado = Empleado::where('correo_corporativo', $user->email)->first();

        abort_unless($empleado && $empleado->id == $record, 403);

        parent::mount($record);
    }

    public static function canAccess(array $parameters = []): bool
    {
        $user = Auth::user();
        $empleado = Empleado::where('correo_corporativo', $user->email)->first();

        return $empleado !== null;
    }

    //formulario de edicion de empleados que es mostrado solamente cuando el usuario tiene el rol empleado, este formulario es distinto al de rrhh
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
                            ->required()
                            ->label('Fecha de Nacimiento')
                            ->hint('Fecha de nacimiento del empleado')
                            ->hintIcon('heroicon-o-cake'),

                        Select::make('genero')
                            ->required()
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

                        //croquis
                        Fieldset::make('Direccion y croquis de domicilio')
                            ->schema([
                                TextInput::make('direccion')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('Dirección completa')
                                    ->hint('Escriba la dirección completa y detallada de su domicilio')
                                    ->hintIcon('heroicon-o-exclamation-triangle'),



                                // Campo para el mapa (interactivo) 
                                TextInput::make('titulo')
                                    ->label('Ubicacion de domicilio')
                                    ->hintIcon('heroicon-o-exclamation-triangle')
                                    ->hint('Busque en el mapa la ubicacion de su domicilio')
                                    ->required()
                                    ->visible(function ($get, $livewire) {
                                        // Ocultar cuando ubicacion_gps es null o es la ubicación por defecto
                                        return (empty($livewire->ubicacion_gps) ||
                                            ($livewire->ubicacion_gps['lat'] == -16.500000 &&
                                                $livewire->ubicacion_gps['lng'] == -68.150000));
                                    })
                                    ->extraAttributes(['class' => 'hidden'])
                                    ->disabled(true),
                                Field::make('ubicacion_gps')
                                    ->live()
                                    ->view('filament.forms.components.map-picker'),


                            ])
                            ->columns(1),
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
                                    ->required()
                                    ->hintIcon('heroicon-o-heart'),

                                TextInput::make('cantidad_hijos')
                                    ->default(0)
                                    ->numeric()
                                    ->label('Número de Hijos')
                                    ->hint('Cantidad de hijos del empleado')
                                    ->hintIcon('heroicon-o-user-group'),

                                TextInput::make('telefono_personal')
                                    ->required()
                                    ->tel()
                                    ->label('Teléfono Personal')
                                    ->hint('Número de contacto personal')
                                    ->hintIcon('heroicon-o-phone'),

                                TextInput::make('correo_personal')
                                    ->required()
                                    ->email()
                                    ->label('Correo Personal')
                                    ->hint('Correo electrónico personal')
                                    ->hintIcon('heroicon-o-envelope'),

                                Fieldset::make('Contacto de Emergencia')

                                    ->schema([
                                        TextInput::make('persona_contacto')
                                            ->required()
                                            ->label('Nombre de contacto')
                                            ->hint('Persona a contactar en caso de emergencia')
                                            ->hintIcon('heroicon-o-exclamation-triangle'),

                                        TextInput::make('numero_contacto')
                                            ->required()
                                            ->tel()
                                            ->label('Teléfono de contacto')
                                            ->hint('Número de la persona de emergencia')
                                            ->hintIcon('heroicon-o-phone'),

                                        TextInput::make('persona_parentesco')
                                            ->required()
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


                                Fieldset::make('Contacto empresarial')
                                    ->schema([
                                        TextInput::make('correo_corporativo')
                                            ->disabled()
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
                                        TextInput::make('nua_cua')
                                            ->label('Numero NUA/CUA')
                                            ->hint('Afiliación al seguro social')
                                            ->hintIcon('heroicon-o-shield-check'),
                                    ])
                                    ->columns(2),
                            ])
                            ->columns(2),
                    ])
                    ->columns(2),

            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar cambios')
                ->submit('save'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->getRecord()->id]);
    }
}