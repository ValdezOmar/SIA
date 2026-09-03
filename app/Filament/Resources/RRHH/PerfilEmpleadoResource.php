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
use Filament\Forms\Get;
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
     * El perfil aparece cuando el correo del usuario está asociado a un
     * empleado, incluso si actualmente no tiene un historial laboral activo.
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
                Section::make(fn (Get $get): HtmlString => static::sectionHeading('Mi perfil', $get, [
                    'foto', 'nombres', 'apellidos',
                ]))
                    ->id('resumen-perfil')
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

                Section::make(fn (Get $get): HtmlString => static::sectionHeading('Información personal', $get, [
                    'nombres', 'apellidos', 'ci', 'fecha_nacimiento', 'genero',
                    'nacionalidad', 'estado_civil', 'cantidad_hijos',
                ]))
                    ->id('informacion-personal')
                    ->description('Datos personales y de identificación.')
                    ->icon('heroicon-o-user')
                    ->schema([
                        TextInput::make('nombres')
                            ->label('Nombres')
                            ->live(onBlur: true)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('apellidos')
                            ->label('Apellidos')
                            ->live(onBlur: true)
                            ->required()
                            ->maxLength(255),

                        TextInput::make('ci')
                            ->label('Cédula de identidad')
                            ->live(onBlur: true)
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),

                        DatePicker::make('fecha_nacimiento')
                            ->label('Fecha de nacimiento')
                            ->live()
                            ->native()
                            ->maxDate(now()),

                        Select::make('genero')
                            ->label('Género')
                            ->live()
                            ->options([
                                'hombre' => 'Hombre',
                                'mujer' => 'Mujer',
                                'otro' => 'Otro',
                            ])
                            ->native(),

                        TextInput::make('nacionalidad')
                            ->label('Nacionalidad')
                            ->live(onBlur: true)
                            ->maxLength(100),

                        Select::make('estado_civil')
                            ->label('Estado civil')
                            ->live()
                            ->options([
                                'soltero' => 'Soltero/a',
                                'casado' => 'Casado/a',
                                'viudo' => 'Viudo/a',
                                'divorciado' => 'Divorciado/a',
                            ])
                            ->native(),

                        TextInput::make('cantidad_hijos')
                            ->label('Cantidad de hijos')
                            ->live(onBlur: true)
                            ->numeric()
                            ->minValue(0),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ]),

                Section::make(fn (Get $get): HtmlString => static::sectionHeading('Contacto y domicilio', $get, [
                    'telefono_personal', 'correo_personal', 'direccion', 'ubicacion_gps',
                ]))
                    ->id('contacto-domicilio')
                    ->description('Información necesaria para comunicarse con usted.')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextInput::make('telefono_personal')
                            ->label('Teléfono personal')
                            ->live(onBlur: true)
                            ->tel()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-phone'),

                        TextInput::make('correo_personal')
                            ->label('Correo personal')
                            ->live(onBlur: true)
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope'),

                        TextInput::make('direccion')
                            ->label('Dirección completa')
                            ->live(onBlur: true)
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Field::make('ubicacion_gps')
                            ->label('Ubicación del domicilio')
                            ->view('filament.forms.components.map-picker')
                            ->helperText('Marque su domicilio en el mapa para facilitar su ubicación.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make(fn (Get $get): HtmlString => static::sectionHeading('Contacto de emergencia', $get, [
                    'persona_contacto', 'numero_contacto', 'persona_parentesco',
                ]))
                    ->id('contacto-emergencia')
                    ->description('Persona a quien contactar en caso de emergencia.')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->schema([
                        TextInput::make('persona_contacto')
                            ->label('Nombre del contacto')
                            ->live(onBlur: true)
                            ->maxLength(255),

                        TextInput::make('numero_contacto')
                            ->label('Teléfono del contacto')
                            ->live(onBlur: true)
                            ->tel()
                            ->maxLength(50),

                        TextInput::make('persona_parentesco')
                            ->label('Parentesco')
                            ->live(onBlur: true)
                            ->maxLength(100),
                    ])
                    ->columns(3)
                    ->collapsed(),

                Section::make(fn (?PerfilEmpleado $record): HtmlString => static::laborSectionHeading($record))
                    ->id('informacion-laboral')
                    ->description('Esta información proviene del historial laboral activo y solo puede modificarla Recursos Humanos.')
                    ->icon('heroicon-o-briefcase')
                    ->schema([
                        Placeholder::make('empresa_actual')
                            ->label('Empresa')
                            ->content(fn (?PerfilEmpleado $record): string => $record?->historialActivo?->empresa?->nombre_comercial
                                ?: $record?->historialActivo?->empresa?->razon_social
                                ?: 'Sin asignar'),

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

                Section::make(fn (Get $get): HtmlString => static::sectionHeading('Seguridad social', $get, [
                    'afp', 'nua_cua',
                ]))
                    ->id('seguridad-social')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('afp')
                            ->label('Gestora / AFP')
                            ->live(onBlur: true)
                            ->maxLength(255),

                        TextInput::make('nua_cua')
                            ->label('Número NUA/CUA')
                            ->live(onBlur: true)
                            ->maxLength(100),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }

    /**
     * Construye el puntero de completitud que permanece visible en el título de
     * cada sección, incluso cuando la sección se encuentra contraída.
     *
     * @param  array<int, string>  $fields
     */
    private static function sectionHeading(string $title, Get $get, array $fields): HtmlString
    {
        $complete = collect($fields)->every(function (string $field) use ($get): bool {
            $value = $get($field);

            if (is_array($value)) {
                return $value !== [];
            }

            return $value === 0 || $value === '0' || filled($value);
        });

        return static::headingWithStatus($title, $complete);
    }

    /**
     * La sección laboral es informativa y se evalúa desde el historial vigente,
     * porque sus valores no forman parte directa del formulario del empleado.
     */
    private static function laborSectionHeading(?PerfilEmpleado $record): HtmlString
    {
        $historial = $record?->historialActivo;
        $complete = $historial !== null && collect([
            $historial->empresa_id,
            $historial->sucursal_id,
            $historial->cargo_id,
            $historial->tipo_contrato,
            $historial->fecha_inicio,
            $historial->correo_corporativo,
        ])->every(fn ($value): bool => filled($value));

        return static::headingWithStatus('Información laboral vigente', $complete);
    }

    private static function headingWithStatus(string $title, bool $complete): HtmlString
    {
        $label = $complete ? 'Completa' : 'Incompleta';
        $color = $complete ? '#16a34a' : '#d97706';

        return new HtmlString(sprintf(
            '<span>%s <small style="margin-left:8px;color:%s;font-weight:600;white-space:nowrap;">● %s</small></span>',
            e($title),
            $color,
            $label,
        ));
    }

    /**
     * Obtiene el empleado asociado al usuario mediante cualquiera de sus
     * historiales. La asociación permanece válida aunque el vínculo laboral se
     * cierre posteriormente.
     */
    public static function getCurrentEmployeeId(): ?int
    {
        $email = Auth::user()?->email;

        if (! $email) {
            return null;
        }

        return PerfilEmpleado::query()
            ->whereHas(
                'historialLaboral',
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
