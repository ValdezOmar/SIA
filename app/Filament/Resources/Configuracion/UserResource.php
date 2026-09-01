<?php

namespace App\Filament\Resources\Configuracion;

use App\Filament\Resources\Configuracion\UserResource\Pages;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialLaboral;
use App\Models\Sistema\Cargo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) User::query()
            ->where('email', '!=', 'admin@admin.com')
            ->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Usuarios registrados';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de acceso')
                ->description('Estos datos permiten identificar e iniciar sesión en el sistema.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre completo')
                        ->placeholder('Ej. María Pérez')
                        ->autofocus()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo corporativo')
                        ->placeholder('nombre@empresa.com')
                        ->email()
                        ->helperText('Debe coincidir con el correo corporativo del empleado para enlazar su perfil.')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->helperText(fn (string $operation): string => $operation === 'create'
                            ? 'Mínimo 8 caracteres. El usuario podrá cambiarla después.'
                            : 'Déjela vacía para conservar la contraseña actual.')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(8)
                        ->maxLength(255)
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ])->columns(2),

            Forms\Components\Section::make('Nivel de acceso')
                ->description('El rol define a qué módulos y acciones puede acceder esta persona.')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rol del usuario')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->minItems(1)
                        ->maxItems(1)
                        ->default(fn (): array => self::getDefaultRoleId())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->helperText('Seleccione un único rol. Por defecto se asigna Empleado.')
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('role_description')
                        ->label('Acceso seleccionado')
                        ->content(fn (Get $get): string => self::getRoleDescription($get('roles')))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('empleado.foto_url')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (User $record): ?string => $record->empleado?->foto_url)
                    ->defaultImageUrl(asset('images/default-avatar.jpg'))
                    ->width(40)
                    ->height(40)
                    ->tooltip(fn (User $record): string => $record->empleado ? 'Perfil de empleado vinculado' : 'Sin perfil de empleado vinculado'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email)
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Nivel de acceso')
                    ->badge()
                    ->color(fn (?string $state) => match (true) {
                        in_array($state, ['Super Admin', 'Administrador De Sistema', 'super_admin']) => 'danger',
                        in_array($state, ['Gerencia', 'Directiva', 'Administrador', 'Encargado Regional', 'Recursos Humanos']) => 'warning',
                        $state === 'Administracion Regional' => 'info',
                        $state === 'Empleado' => 'success',
                        in_array($state, ['Almacenes', 'Comercial', 'Licitaciones', 'Soporte Técnico', 'Operativo']) => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('empleado.full_name')
                    ->label('Perfil de empleado')
                    ->getStateUsing(fn (User $record): string => $record->empleado?->full_name ?? 'Sin vincular')
                    ->description(fn (User $record): string => $record->empleado?->historialActivo?->cargo?->nombre ?? 'El correo aún no coincide con un empleado')
                    ->icon(fn (User $record): string => $record->empleado ? 'heroicon-o-link' : 'heroicon-o-exclamation-circle')
                    ->iconColor(fn (User $record): string => $record->empleado ? 'success' : 'warning')
                    ->color(fn (User $record): string => $record->empleado ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Último cambio')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Nivel de acceso')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('perfil_empleado')
                    ->label('Perfil de empleado')
                    ->placeholder('Todos')
                    ->trueLabel('Vinculado')
                    ->falseLabel('Sin vincular')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('empleado'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('empleado'),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('vincular_empleado')
                    ->label('Vincular empleado')
                    ->tooltip('Vincular esta cuenta con un empleado existente')
                    ->icon('heroicon-o-link')
                    ->color('info')
                    ->modalHeading('Vincular empleado existente')
                    ->modalDescription('Seleccione el empleado que corresponde a esta cuenta. Su correo corporativo se actualizará con el correo del usuario.')
                    ->modalSubmitActionLabel('Vincular empleado')
                    ->form([
                        Forms\Components\Placeholder::make('usuario_vinculo')
                            ->label('Cuenta de usuario')
                            ->content(fn (User $record): string => $record->name.' — '.$record->email),

                        Forms\Components\Select::make('empleado_id')
                            ->label('Empleado existente')
                            ->options(fn (): array => self::getEmployeeOptions())
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disableOptionWhen(fn (string $value): bool => self::employeeCannotBeLinked((int) $value))
                            ->helperText('Los empleados vinculados aparecen en gris y no pueden seleccionarse.'),
                    ])
                    ->action(fn (User $record, array $data) => self::linkExistingEmployee($record, (int) $data['empleado_id']))
                    ->visible(fn (User $record): bool => $record->empleado === null),

                Tables\Actions\Action::make('crear_perfil_empleado')
                    ->label('Crear perfil')
                    ->tooltip('Registrar y vincular los datos básicos del empleado')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading('Crear perfil de empleado')
                    ->modalDescription('Registre los datos básicos. El perfil quedará vinculado automáticamente mediante el correo corporativo del usuario.')
                    ->modalSubmitActionLabel('Crear y vincular perfil')
                    ->modalWidth('4xl')
                    ->fillForm(fn (User $record): array => self::getInitialEmployeeData($record))
                    ->form([
                        Forms\Components\Section::make('Datos personales básicos')
                            ->description('Información necesaria para identificar al empleado.')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Forms\Components\TextInput::make('nombres')
                                    ->label('Nombres')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('apellidos')
                                    ->label('Apellidos')
                                    ->required()
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('ci')
                                    ->label('Cédula de identidad')
                                    ->required()
                                    ->unique(table: 'rh_empleados', column: 'ci')
                                    ->maxLength(50),

                                Forms\Components\DatePicker::make('fecha_nacimiento')
                                    ->label('Fecha de nacimiento')
                                    ->native(false)
                                    ->maxDate(now()),

                                Forms\Components\Select::make('genero')
                                    ->label('Género')
                                    ->options([
                                        'hombre' => 'Hombre',
                                        'mujer' => 'Mujer',
                                        'otro' => 'Otro',
                                    ])
                                    ->native(false),

                                Forms\Components\TextInput::make('telefono_personal')
                                    ->label('Teléfono personal')
                                    ->tel()
                                    ->maxLength(50),
                            ])
                            ->columns(3),

                        Forms\Components\Section::make('Asignación laboral')
                            ->description('Crea el historial laboral activo que enlaza al empleado con esta cuenta.')
                            ->icon('heroicon-o-briefcase')
                            ->schema([
                                Forms\Components\Placeholder::make('correo_corporativo_info')
                                    ->label('Correo corporativo')
                                    ->content(fn (User $record): string => $record->email)
                                    ->helperText('Este correo se usará para vincular la cuenta y el perfil.'),

                                Forms\Components\Select::make('empresa_id')
                                    ->label('Empresa')
                                    ->options(fn (): array => Empresa::query()
                                        ->where('empresa_activo', true)
                                        ->orderBy('nombre_comercial')
                                        ->pluck('nombre_comercial', 'id')
                                        ->all())
                                    ->default(fn () => Empresa::query()
                                        ->where('empresa_activo', true)
                                        ->orderBy('id')
                                        ->value('id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function (Set $set): void {
                                        $set('sucursal_id', null);
                                        $set('cargo_id', null);
                                    }),

                                Forms\Components\Select::make('sucursal_id')
                                    ->label('Sucursal')
                                    ->options(fn (Get $get): array => filled($get('empresa_id'))
                                        ? Sucursal::query()
                                            ->where('empresa_id', $get('empresa_id'))
                                            ->where('activo', true)
                                            ->orderBy('nombre')
                                            ->pluck('nombre', 'id')
                                            ->all()
                                        : [])
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (Get $get): bool => blank($get('empresa_id'))),

                                Forms\Components\Select::make('cargo_id')
                                    ->label('Cargo')
                                    ->options(fn (Get $get): array => filled($get('empresa_id'))
                                        ? Cargo::query()
                                            ->whereHas('area.empresas', fn (Builder $query) => $query->where('conf_empresas.id', $get('empresa_id')))
                                            ->orderBy('nombre')
                                            ->pluck('nombre', 'id')
                                            ->all()
                                        : [])
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (Get $get): bool => blank($get('empresa_id'))),

                                Forms\Components\DatePicker::make('fecha_inicio')
                                    ->label('Fecha de ingreso')
                                    ->default(now()->toDateString())
                                    ->displayFormat('d/m/Y')
                                    ->required()
                                    ->native(false),

                                Forms\Components\Select::make('tipo_contrato')
                                    ->label('Tipo de contrato')
                                    ->options([
                                        'Contrato indefinido' => 'Contrato indefinido',
                                        'Contrato plazo fijo' => 'Contrato plazo fijo',
                                        'Contrato por servicios' => 'Contrato por servicios',
                                        'Contrato por obra' => 'Contrato por obra',
                                        'Contrato por temporada' => 'Contrato por temporada',
                                        'Contrato de teletrabajo' => 'Contrato de teletrabajo',
                                        'Pasante' => 'Pasante',
                                        'Otro' => 'Otro',
                                    ])
                                    ->default('Contrato indefinido')
                                    ->placeholder('Sin especificar')
                                    ->native(false),
                            ])
                            ->columns(3),
                    ])
                    ->action(fn (User $record, array $data) => self::createAndLinkEmployee($record, $data))
                    ->visible(fn (User $record): bool => $record->empleado === null),

                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->tooltip('Editar usuario'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Aún no hay usuarios')
            ->emptyStateDescription('Cree un usuario para darle acceso al sistema.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'roles',
            'empleado.historialActivo.cargo',
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    protected static function getDefaultRoleId(): array
    {
        $roleId = Role::query()->where('name', 'Empleado')->value('id');

        return $roleId ? [(string) $roleId] : [];
    }

    /** @param array<int, string>|string|null $roleIds */
    protected static function getRoleDescription(array|string|null $roleIds): string
    {
        $roleId = is_array($roleIds) ? reset($roleIds) : $roleIds;
        $roleName = $roleId ? Role::find($roleId)?->name : null;

        return match ($roleName) {
            'super_admin' => 'Acceso total al sistema, incluida su configuración.',
            'Administrador' => 'Administra usuarios, configuración y operaciones del sistema.',
            'Directiva' => 'Consulta información ejecutiva y gestiona decisiones de alto nivel.',
            'Gerencia' => 'Supervisa su área, aprobaciones y reportes departamentales.',
            'Administracion Regional' => 'Gestiona la operación y reportes de su región.',
            'Jefatura' => 'Coordina equipos y las operaciones diarias de su área.',
            'Operativo' => 'Registra y consulta la información necesaria para sus tareas.',
            'Empleado' => 'Acceso básico para consultar y realizar las tareas autorizadas.',
            null => 'Seleccione un rol para ver el nivel de acceso que se asignará.',
            default => "Acceso definido por el rol {$roleName}.",
        };
    }

    /**
     * Separa el nombre de la cuenta para facilitar el llenado inicial del modal.
     * El administrador puede corregir ambos campos antes de guardar.
     *
     * @return array<string, mixed>
     */
    private static function getInitialEmployeeData(User $user): array
    {
        $partes = preg_split('/\s+/', trim($user->name)) ?: [];
        $apellido = count($partes) > 1 ? array_pop($partes) : '';

        return [
            'nombres' => implode(' ', $partes) ?: $user->name,
            'apellidos' => $apellido,
            'empresa_id' => Empresa::query()
                ->where('empresa_activo', true)
                ->orderBy('id')
                ->value('id'),
            'fecha_inicio' => now()->toDateString(),
            'tipo_contrato' => 'Contrato indefinido',
        ];
    }

    /**
     * Crea los datos personales y laborales en una única transacción.
     * El correo corporativo constituye el enlace entre User y Empleado.
     */
    private static function createAndLinkEmployee(User $user, array $data): void
    {
        $correoYaAsignado = HistorialLaboral::query()
            ->whereRaw('LOWER(correo_corporativo) = ?', [mb_strtolower($user->email)])
            ->exists();

        if ($correoYaAsignado) {
            Notification::make()
                ->title('El correo ya está asociado a un empleado')
                ->body('Revise el historial laboral existente antes de crear otro perfil.')
                ->warning()
                ->persistent()
                ->send();

            return;
        }

        DB::transaction(function () use ($user, $data): void {
            $empleado = Empleado::query()->create([
                'nombres' => $data['nombres'],
                'apellidos' => $data['apellidos'],
                'ci' => $data['ci'],
                'fecha_nacimiento' => $data['fecha_nacimiento'] ?? null,
                'genero' => $data['genero'] ?? null,
                'telefono_personal' => $data['telefono_personal'] ?? null,
                'nacionalidad' => 'Boliviana',
                'activo' => true,
            ]);

            $empresa = Empresa::query()->find($data['empresa_id']);

            $empleado->historialLaboral()->create([
                'empresa_id' => $data['empresa_id'],
                'sucursal_id' => $data['sucursal_id'] ?? null,
                'cargo_id' => $data['cargo_id'] ?? null,
                'fecha_inicio' => $data['fecha_inicio'],
                'tipo_contrato' => $data['tipo_contrato'] ?? null,
                'seguro_medico' => $empresa?->seguro_medico,
                'correo_corporativo' => mb_strtolower($user->email),
                'activo' => true,
            ]);
        });

        $user->unsetRelation('empleado');

        Notification::make()
            ->title('Perfil de empleado creado')
            ->body('El usuario ya puede acceder a Mi Perfil desde su cuenta.')
            ->success()
            ->send();
    }

    /**
     * Devuelve todos los empleados e identifica en la etiqueta cuáles ya están
     * vinculados o no tienen un historial laboral activo.
     *
     * @return array<int, string>
     */
    private static function getEmployeeOptions(): array
    {
        $usuariosPorCorreo = User::query()
            ->whereNotNull('email')
            ->get(['name', 'email'])
            ->keyBy(fn (User $user): string => mb_strtolower($user->email));

        return Empleado::query()
            ->with('historialActivo')
            ->orderBy('apellidos')
            ->orderBy('nombres')
            ->get()
            ->mapWithKeys(function (Empleado $empleado) use ($usuariosPorCorreo): array {
                $etiqueta = trim($empleado->nombres.' '.$empleado->apellidos).' — CI: '.$empleado->ci;
                $historial = $empleado->historialActivo;

                if (! $historial) {
                    return [$empleado->id => $etiqueta.' — DISPONIBLE (SE CREARÁ HISTORIAL ACTIVO)'];
                }

                $correo = $historial->correo_corporativo;
                $usuarioVinculado = filled($correo)
                    ? $usuariosPorCorreo->get(mb_strtolower($correo))
                    : null;

                if ($usuarioVinculado) {
                    return [$empleado->id => $etiqueta.' — VINCULADO A '.$usuarioVinculado->name];
                }

                return [$empleado->id => $etiqueta.' — DISPONIBLE'];
            })
            ->all();
    }

    /**
     * Deshabilita únicamente empleados cuyo correo corporativo ya corresponde
     * a una cuenta. Los empleados sin historial activo pueden seleccionarse.
     */
    private static function employeeCannotBeLinked(int $empleadoId): bool
    {
        static $empleadosNoDisponibles;

        if ($empleadosNoDisponibles === null) {
            $correosDeUsuarios = User::query()
                ->whereNotNull('email')
                ->pluck('email')
                ->map(fn (string $email): string => mb_strtolower($email))
                ->all();

            $empleadosNoDisponibles = Empleado::query()
                ->with('historialActivo')
                ->get()
                ->filter(function (Empleado $empleado) use ($correosDeUsuarios): bool {
                    $historial = $empleado->historialActivo;

                    return $historial
                        && filled($historial->correo_corporativo)
                        && in_array(mb_strtolower($historial->correo_corporativo), $correosDeUsuarios, true);
                })
                ->pluck('id')
                ->map(fn ($id): int => (int) $id)
                ->all();
        }

        return in_array($empleadoId, $empleadosNoDisponibles, true);
    }

    /**
     * Vincula un empleado existente reemplazando el correo corporativo de su
     * historial activo por el correo de la cuenta seleccionada.
     */
    private static function linkExistingEmployee(User $user, int $empleadoId): void
    {
        $vinculado = DB::transaction(function () use ($user, $empleadoId): bool {
            $empleado = Empleado::query()
                ->with('historialActivo')
                ->lockForUpdate()
                ->find($empleadoId);

            if (! $empleado) {
                return false;
            }

            $historial = $empleado->historialActivo;
            $correoActual = $historial?->correo_corporativo;
            $perteneceAOtraCuenta = filled($correoActual)
                && User::query()
                    ->whereKeyNot($user->getKey())
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($correoActual)])
                    ->exists();

            $correoUsuarioAsignadoAOtroEmpleado = HistorialLaboral::query()
                ->where('empleado_id', '!=', $empleado->id)
                ->whereRaw('LOWER(correo_corporativo) = ?', [mb_strtolower($user->email)])
                ->exists();

            if ($perteneceAOtraCuenta || $correoUsuarioAsignadoAOtroEmpleado) {
                return false;
            }

            if (! $historial) {
                $historial = $empleado->historialLaboral()->create([
                    'correo_corporativo' => mb_strtolower($user->email),
                    'fecha_inicio' => now()->toDateString(),
                    'activo' => true,
                ]);
            }

            $historial->update([
                'correo_corporativo' => mb_strtolower($user->email),
            ]);

            return true;
        });

        if (! $vinculado) {
            Notification::make()
                ->title('No se pudo vincular el empleado')
                ->body('El empleado ya está asociado a otra cuenta o el registro dejó de estar disponible.')
                ->danger()
                ->persistent()
                ->send();

            return;
        }

        $user->unsetRelation('empleado');

        Notification::make()
            ->title('Empleado vinculado correctamente')
            ->body('El usuario ya puede acceder a Mi Perfil desde su cuenta.')
            ->success()
            ->send();
    }
}
