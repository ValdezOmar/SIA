<?php

namespace App\Filament\Resources\RRHH\EmpleadoResource\Pages;

use App\Models\RRHH\Empleado;
use Filament\Forms\Form;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\RRHH\EmpleadoResource;

class PerfilEmpleado extends EditRecord
{
    protected static string $resource = EmpleadoResource::class;
    //protected static ?string $model = Empleado::class;
    //protected static string $view = 'filament.resources.empleado-resource.pages.perfil';
    protected static ?string $navigationLabel = 'Mi Perfil';
    protected static ?string $title = 'Mi Perfil de Empleado';

    //public ?array $data = [];

    //  protected function authorizeAccess(): void
    // {
    //     $user = Auth::user();
    //     $empleado = $this->getRecord();
        
    //     if ($user->email !== $empleado->correo_corporativo) {
    //         abort(403, 'No tienes permiso para acceder a este perfil.');
    //     }
    // }

    // public function mount(int|string $record): void
    // {
    //     $user = Auth::user();
    //     $empleado = Empleado::where('correo_corporativo', $user->email)->first();
        
    //     if (!$empleado) {
    //         abort(403, 'No tienes un empleado asociado a tu cuenta.');
    //     }

    //     if ($empleado->id != $record) {
    //         abort(403, 'No tienes permiso para acceder a este perfil.');
    //     }

    //     parent::mount($record);
    // }

    public function form(Form $form): Form
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
                            ->live(),

                        TextInput::make('apellidos')
                            ->required()
                            ->maxLength(255)
                            ->live(),

                        TextInput::make('ci')
                            ->required()
                            ->label('Cédula de Identidad')
                            ->disabled(),

                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de Nacimiento'),

                        Select::make('genero')
                            ->options([
                                'hombre' => 'Hombre',
                                'mujer' => 'Mujer',
                                'otro' => 'Otro',
                            ]),

                        TextInput::make('nacionalidad')
                            ->required()
                            ->default('Boliviana'),

                        Fieldset::make('Direccion y croquis de domicilio')
                            ->schema([
                                TextInput::make('direccion')
                                    ->maxLength(255)
                                    ->label('Dirección completa'),

                                Textarea::make('ubicacion_gps')
                                    ->label('Ubicación GPS')
                                    ->columnSpanFull(),
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
                            ->label('Estado Civil'),

                        TextInput::make('cantidad_hijos')
                            ->default(0)
                            ->numeric()
                            ->label('Número de Hijos'),

                        TextInput::make('telefono_personal')
                            ->required()
                            ->tel()
                            ->label('Teléfono Personal'),

                        TextInput::make('correo_personal')
                            ->required()
                            ->email()
                            ->label('Correo Personal'),

                        Fieldset::make('Contacto de Emergencia')
                            ->schema([
                                TextInput::make('persona_contacto')
                                    ->required()
                                    ->label('Nombre de contacto'),

                                TextInput::make('numero_contacto')
                                    ->required()
                                    ->tel()
                                    ->label('Teléfono de contacto'),

                                TextInput::make('persona_parentesco')
                                    ->required()
                                    ->label('Parentesco de contacto'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(2),
            ])
            ->statePath('data')
            ->model(Empleado::class);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar cambios')
                ->submit('save'),
        ];
    }
    //permisos del formulario
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('perfil', ['record' => $this->getRecord()]);
    }
protected function authorizeAccess(): void
{
    // Permitir acceso sin validaciones
}
public static function canAccess(array $parameters = []): bool
{
    return true;
}

    public static function canViewAny(): bool
    {
        return true;
    }
    public static function canView(): bool
    {
        return true;
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        $empleado = Empleado::find($record);        
        return $empleado && $user->email === $empleado->correo_corporativo;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}