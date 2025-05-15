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
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Fieldset;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;

class EditPerfilEmpleado extends EditRecord
{
    protected static string $resource = PerfilEmpleadoResource::class;

    protected static ?string $title = 'Mi Perfil';
    public ?array $ubicacion_gps;

    //Funcion para guardar el array de gps
    public function mutateFormDataBeforeSave(array $data): array
    { //dump($this->data);
        // Ver lo que contiene la propiedad para depuración
        //dump($this->ubicacion_gps);
        if (is_array($this->ubicacion_gps)) {
            $data['ubicacion_gps'] = [
                'lat' => round(floatval($this->ubicacion_gps['lat'] ?? 0), 6),
                'lng' => round(floatval($this->ubicacion_gps['lng'] ?? 0), 6),
            ];
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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                    ->hint('Busque en el mapa la ubicacion de su domicilio')
                                    ->hintIcon('heroicon-o-exclamation-triangle'),

                                // Campo para el mapa (interactivo)                                
                                Field::make('ubicacion_gps')
                                    ->label('Ubicación GPS')
                                    ->view('filament.forms.components.map-picker')
                                    ->live()
                                    ->afterStateHydrated(function ($state, $record) {                                   

                                        // Transformación garantizada
                                        if (is_string($state)) {
                                            try {
                                                $result = json_decode($state, true) ?? ['lat' => -16.504759, 'lng' => -68.119124];
                                                
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
