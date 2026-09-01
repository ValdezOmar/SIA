<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;
use App\Models\RRHH\PerfilEmpleado;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PerfilEmpleadoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = PerfilEmpleado::class;

    protected static ?string $modelLabel = 'Perfil del empleado';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Recursos Humanos';

    protected static ?string $navigationLabel = 'Mi Perfil';

    protected static ?int $navigationSort = -1;

    /**
     * El perfil aparece únicamente cuando el correo del usuario está asociado
     * a un historial laboral activo.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::getCurrentEmployeeId() !== null;
    }

    /**
     * Envía directamente al usuario a su perfil, sin mostrar un listado.
     */
    public static function getNavigationUrl(): string
    {
        $empleadoId = static::getCurrentEmployeeId();

        return $empleadoId
            ? static::getUrl('edit', ['record' => $empleadoId])
            : '#';
    }

    /**
     * Restringe cualquier consulta del recurso al empleado autenticado.
     * Esto evita que se pueda consultar otro perfil cambiando la URL.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with([
                'historialActivo.empresa',
                'historialActivo.sucursal',
                'historialActivo.cargo',
            ]);

        $empleadoId = static::getCurrentEmployeeId();

        return $empleadoId
            ? $query->whereKey($empleadoId)
            : $query->whereRaw('1 = 0');
    }

    public static function canViewAny(): bool
    {
        return static::getCurrentEmployeeId() !== null;
    }

    public static function canView(Model $record): bool
    {
        return (int) $record->getKey() === static::getCurrentEmployeeId();
    }

    public static function canEdit(Model $record): bool
    {
        return (int) $record->getKey() === static::getCurrentEmployeeId();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Mi perfil')
                    ->description('Información principal asociada a su cuenta empresarial.')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        FileUpload::make('foto')
                            ->label('Foto de perfil')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->imageCropAspectRatio('1:1')
                            ->disk('public')
                            ->directory('empleados')
                            ->visibility('public')
                            ->openable()
                            ->downloadable()
                            ->maxSize(5120)
                            ->helperText('Imagen JPG o PNG de hasta 5 MB.')
                            ->live()
                            ->getUploadedFileNameForStorageUsing(
                                function (TemporaryUploadedFile $file, $record): string {
                                    $ci = Str::slug($record?->ci ?? 'empleado');

                                    return $ci.'-'.Str::uuid().'.'.Str::lower($file->getClientOriginalExtension());
                                },
                            )
                            ->columnSpan(1),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('nombre_resumen')
                                    ->label('Nombre completo')
                                    ->content(fn (?PerfilEmpleado $record): string => $record?->full_name ?? 'Sin registrar'),

                                Placeholder::make('estado_resumen')
                                    ->label('Estado')
                                    ->content(fn (?PerfilEmpleado $record): HtmlString => new HtmlString(
                                        $record?->activo
                                            ? '<span class="font-medium text-success-600">Empleado activo</span>'
                                            : '<span class="font-medium text-danger-600">Empleado inactivo</span>',
                                    )),

                                Placeholder::make('correo_resumen')
                                    ->label('Correo corporativo')
                                    ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->correo_corporativo ?? 'Sin asignar'),

                                Placeholder::make('telefono_resumen')
                                    ->label('Teléfono corporativo')
                                    ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->numero_corporativo ?? 'Sin asignar'),
                            ])
                            ->columnSpan(2),
                    ])
                    ->columns(3),

                Section::make('Información personal')
                    ->description('Datos personales y de identificación.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('ci')
                            ->label('Cédula de identidad')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->native(false)
                            ->maxDate(now()),

                        Select::make('genero')
                            ->label('Género')
                            ->options([
                                'hombre' => 'Hombre',
                                'mujer' => 'Mujer',
                                'otro' => 'Otro',
                            ])
                            ->native(false),

                        TextInput::make('nacionalidad')
                            ->label('Nacionalidad')
                            ->maxLength(100),

                        Select::make('estado_civil')
                            ->label('Estado civil')
                            ->options([
                                'soltero' => 'Soltero/a',
                                'casado' => 'Casado/a',
                                'viudo' => 'Viudo/a',
                                'divorciado' => 'Divorciado/a',
                            ])
                            ->native(false),

                        TextInput::make('cantidad_hijos')
                            ->label('Cantidad de hijos')
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make('Contacto y domicilio')
                    ->description('Información necesaria para comunicarse con usted.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('telefono_personal')
                            ->label('Teléfono personal')
                            ->tel()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-phone'),

                        TextInput::make('correo_personal')
                            ->label('Correo personal')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope'),

                        TextInput::make('direccion')
                            ->label('Dirección completa')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Field::make('ubicacion_gps')
                            ->label('Ubicación del domicilio')
                            ->view('filament.forms.components.map-picker')
                            ->helperText('Marque su domicilio en el mapa para facilitar su ubicación.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Contacto de emergencia')
                    ->description('Persona a quien contactar en caso de emergencia.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        TextInput::make('persona_contacto')
                            ->label('Nombre del contacto')
                            ->maxLength(255),

                        TextInput::make('numero_contacto')
                            ->label('Teléfono del contacto')
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('persona_parentesco')
                            ->label('Parentesco')
                            ->maxLength(100),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Section::make('Información laboral vigente')
                    ->description('Esta información proviene del historial laboral activo y solo puede modificarla Recursos Humanos.')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Placeholder::make('empresa_actual')
                            ->label('Empresa')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->empresa?->razon_social ?? 'Sin asignar'),

                        Placeholder::make('sucursal_actual')
                            ->label('Sucursal')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->sucursal?->nombre ?? 'Sin asignar'),

                        Placeholder::make('cargo_actual')
                            ->label('Cargo')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->cargo?->nombre ?? 'Sin asignar'),

                        Placeholder::make('contrato_actual')
                            ->label('Tipo de contrato')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->tipo_contrato ?? 'Sin asignar'),

                        Placeholder::make('fecha_inicio_actual')
                            ->label('Fecha de ingreso')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->fecha_inicio?->format('d/m/Y') ?? 'Sin asignar'),

                        Placeholder::make('fecha_fin_actual')
                            ->label('Fin de contrato')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->fecha_fin?->format('d/m/Y') ?? 'Indefinido'),

                        Placeholder::make('seguro_actual')
                            ->label('Seguro médico')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->seguro_medico ?? 'Sin asignar'),

                        Placeholder::make('salario_actual')
                            ->label('Salario')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->salario !== null
                                ? 'Bs '.number_format($record->historialActivo->salario, 2)
                                : 'Sin asignar'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make('Seguridad social')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('afp')
                            ->label('Gestora / AFP')
                            ->maxLength(255),

                        TextInput::make('nua_cua')
                            ->label('Número NUA/CUA')
                            ->maxLength(100),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    /**
     * Obtiene el empleado vinculado al correo corporativo activo del usuario.
     */
    public static function getCurrentEmployeeId(): ?int
    {
        $email = Auth::user()?->email;

        if (! $email) {
            return null;
        }

        return PerfilEmpleado::query()
            ->whereHas(
                'historialActivo',
                fn (Builder $query) => $query->whereRaw(
                    'LOWER(correo_corporativo) = ?',
                    [mb_strtolower($email)],
                ),
            )
            ->value('id');
    }

    protected static function getPermissionPrefix(): string
    {
        return 'mi_perfil_';
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'update',
        ];
    }

    public static function getPages(): array
    {
        return [
            'view' => Pages\ViewPerfilEmpleado::route('/{record}'),
            'edit' => Pages\EditPerfilEmpleado::route('/{record}/edit'),
        ];
    }
}
