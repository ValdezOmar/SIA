<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\EmpleadoResource\Pages;
use App\Filament\Resources\RRHH\EmpleadoResource\RelationManagers\HistorialLaboralRelationManager;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialLaboral;
use App\Models\Sistema\Empresa;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Carbon\Carbon;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EmpleadoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Empleado::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $modelLabel = 'Empleado';

    protected static ?string $pluralModelLabel = 'Empleados';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?string $navigationGroup = 'Recursos Humanos';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(12)
                    ->schema([
                        Section::make('Perfil del empleado')
                            ->description('Identificación rápida y estado actual.')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                FileUpload::make('foto')
                                    ->label('Fotografía')
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
                                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                                    ->helperText('JPG o PNG, máximo 5 MB. Use una fotografía frontal y reciente.')
                                    ->live()
                                    ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, Get $get, ?Empleado $record): string {
                                        if ($record?->foto && Storage::disk('public')->exists($record->foto)) {
                                            Storage::disk('public')->delete($record->foto);
                                        }

                                        $ci = Str::slug($get('ci') ?: 'empleado');
                                        $nombre = $ci.'-'.Str::uuid().'.'.Str::lower($file->getClientOriginalExtension());

                                        return $file->storePubliclyAs('empleados', $nombre, 'public');
                                    })
                                    ->alignCenter(),

                                Placeholder::make('resumen_nombre')
                                    ->label('Nombre completo')
                                    ->content(fn (Get $get): string => trim(($get('nombres') ?? '').' '.($get('apellidos') ?? '')) ?: 'Nuevo empleado')
                                    ->extraAttributes(['class' => 'text-lg font-semibold']),

                                Placeholder::make('resumen_identidad')
                                    ->label('Identificación')
                                    ->content(fn (Get $get): string => filled($get('ci')) ? 'CI: '.$get('ci') : 'CI pendiente de registro'),

                                Placeholder::make('resumen_laboral')
                                    ->label('Asignación vigente')
                                    ->content(fn (?Empleado $record): string => collect([
                                        $record?->historialActivo?->cargo?->nombre,
                                        $record?->historialActivo?->empresa?->nombre_comercial
                                            ?: $record?->historialActivo?->empresa?->razon_social,
                                    ])->filter()->implode(' · ') ?: 'Sin asignación laboral activa'),

                                Placeholder::make('resumen_estado')
                                    ->label('Estado')
                                    ->content(fn (?Empleado $record): HtmlString => new HtmlString(
                                        ($record?->activo ?? true)
                                            ? '<span class="font-semibold text-success-600">● Empleado activo</span>'
                                            : '<span class="font-semibold text-danger-600">● Empleado inactivo</span>',
                                    )),

                                Toggle::make('activo')
                                    ->label('Registrar como empleado activo')
                                    ->helperText('El estado posterior se administra mediante la desvinculación formal.')
                                    ->default(true)
                                    ->visible(fn (string $operation): bool => $operation === 'create'),

                                Actions::make([
                                    static::makeSeparationAction(),
                                ])
                                    ->alignCenter()
                                    ->visible(fn (?Empleado $record): bool => $record?->historialActivo !== null),
                            ])
                            ->columnSpan([
                                'default' => 12,
                                'lg' => 4,
                                'xl' => 3,
                            ]),

                        Tabs::make('Datos del empleado')
                            ->persistTabInQueryString()
                            ->tabs([
                                Tab::make('Información personal')
                                    ->icon('heroicon-o-user')
                                    ->badge(fn (Get $get): string => static::tabCompletionLabel($get, [
                                        'nombres',
                                        'apellidos',
                                        'ci',
                                        'fecha_nacimiento',
                                        'genero',
                                        'nacionalidad',
                                        'estado_civil',
                                        'cantidad_hijos',
                                        'afp',
                                        'nua_cua',
                                    ]))
                                    ->badgeColor(fn (Get $get): string => static::tabCompletionColor($get, [
                                        'nombres',
                                        'apellidos',
                                        'ci',
                                        'fecha_nacimiento',
                                        'genero',
                                        'nacionalidad',
                                        'estado_civil',
                                        'cantidad_hijos',
                                        'afp',
                                        'nua_cua',
                                    ]))
                                    ->schema([
                                        Section::make('Identidad')
                                            ->description('Datos principales de identificación del empleado.')
                                            ->icon('heroicon-o-finger-print')
                                            ->schema([
                                                TextInput::make('nombres')
                                                    ->label('Nombres')
                                                    ->placeholder('Ej. María Fernanda')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('nombres', static::titleCase($state)))
                                                    ->maxLength(255),

                                                TextInput::make('apellidos')
                                                    ->label('Apellidos')
                                                    ->placeholder('Ej. Pérez López')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->required()
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(fn (?string $state, Set $set) => $set('apellidos', static::titleCase($state)))
                                                    ->maxLength(255),

                                                TextInput::make('ci')
                                                    ->label('Cédula de identidad')
                                                    ->placeholder('Ej. 1234567 LP')
                                                    ->prefixIcon('heroicon-o-identification')
                                                    ->helperText('Debe ser única dentro del sistema.')
                                                    ->required()
                                                    ->unique(ignoreRecord: true)
                                                    ->maxLength(50),

                                                DatePicker::make('fecha_nacimiento')
                                                    ->label('Fecha de nacimiento')
                                                    ->prefixIcon('heroicon-o-cake')
                                                    ->displayFormat('d/m/Y')
                                                    ->native()
                                                    ->maxDate(now()),

                                                Select::make('genero')
                                                    ->label('Género')
                                                    ->prefixIcon('heroicon-o-user-circle')
                                                    ->options([
                                                        'hombre' => 'Hombre',
                                                        'mujer' => 'Mujer',
                                                        'otro' => 'Otro',
                                                    ])
                                                    ->native(),

                                                TextInput::make('nacionalidad')
                                                    ->label('Nacionalidad')
                                                    ->prefixIcon('heroicon-o-flag')
                                                    ->default('Boliviana')
                                                    ->maxLength(100),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'md' => 2,
                                                'xl' => 3,
                                            ]),

                                        Section::make('Información familiar y previsional')
                                            ->icon('heroicon-o-user-group')
                                            ->schema([
                                                Select::make('estado_civil')
                                                    ->label('Estado civil')
                                                    ->options([
                                                        'soltero' => 'Soltero/a',
                                                        'casado' => 'Casado/a',
                                                        'viudo' => 'Viudo/a',
                                                        'divorciado' => 'Divorciado/a',
                                                    ])
                                                    ->native(),

                                                TextInput::make('cantidad_hijos')
                                                    ->label('Cantidad de hijos')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->default(0),

                                                TextInput::make('afp')
                                                    ->label('Gestora / AFP')
                                                    ->prefixIcon('heroicon-o-banknotes')
                                                    ->default('Gestora Pública')
                                                    ->maxLength(255),

                                                TextInput::make('nua_cua')
                                                    ->label('Número NUA/CUA')
                                                    ->prefixIcon('heroicon-o-shield-check')
                                                    ->maxLength(100),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'md' => 2,
                                                'xl' => 4,
                                            ]),
                                    ]),

                                Tab::make('Contacto y domicilio')
                                    ->icon('heroicon-o-map-pin')
                                    ->badge(fn (Get $get): string => static::tabCompletionLabel($get, [
                                        'telefono_personal',
                                        'correo_personal',
                                        'direccion',
                                        'ubicacion_gps',
                                    ]))
                                    ->badgeColor(fn (Get $get): string => static::tabCompletionColor($get, [
                                        'telefono_personal',
                                        'correo_personal',
                                        'direccion',
                                        'ubicacion_gps',
                                    ]))
                                    ->schema([
                                        Section::make('Canales personales')
                                            ->description('Información para contactar directamente al empleado.')
                                            ->schema([
                                                TextInput::make('telefono_personal')
                                                    ->label('Teléfono personal')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->tel()
                                                    ->maxLength(50),

                                                TextInput::make('correo_personal')
                                                    ->label('Correo personal')
                                                    ->prefixIcon('heroicon-o-envelope')
                                                    ->email()
                                                    ->maxLength(255),
                                            ])
                                            ->columns(2),

                                        Section::make('Domicilio')
                                            ->description('Dirección declarada y ubicación precisa en el mapa.')
                                            ->schema([
                                                Textarea::make('direccion')
                                                    ->label('Dirección completa')
                                                    ->placeholder('Calle, número, zona y referencias')
                                                    ->helperText('Incluya referencias que permitan ubicar el domicilio.')
                                                    ->rows(3)
                                                    ->maxLength(255)
                                                    ->columnSpanFull(),

                                                Field::make('ubicacion_gps')
                                                    ->label('Croquis y ubicación GPS')
                                                    ->view('filament.forms.components.map-picker')
                                                    ->helperText('Busque la dirección o marque el punto exacto en el mapa.')
                                                    ->columnSpanFull(),
                                            ]),
                                    ]),

                                Tab::make('Emergencias')
                                    ->icon('heroicon-o-heart')
                                    ->badge(fn (Get $get): string => static::tabCompletionLabel($get, [
                                        'persona_contacto',
                                        'numero_contacto',
                                        'persona_parentesco',
                                    ]))
                                    ->badgeColor(fn (Get $get): string => static::tabCompletionColor($get, [
                                        'persona_contacto',
                                        'numero_contacto',
                                        'persona_parentesco',
                                    ]))
                                    ->schema([
                                        Section::make('Contacto de emergencia')
                                            ->description('Persona autorizada para contactar ante una situación urgente.')
                                            ->icon('heroicon-o-exclamation-triangle')
                                            ->schema([
                                                TextInput::make('persona_contacto')
                                                    ->label('Nombre completo')
                                                    ->prefixIcon('heroicon-o-user')
                                                    ->placeholder('Nombre del contacto')
                                                    ->maxLength(255),

                                                TextInput::make('numero_contacto')
                                                    ->label('Teléfono')
                                                    ->prefixIcon('heroicon-o-phone')
                                                    ->tel()
                                                    ->maxLength(50),

                                                TextInput::make('persona_parentesco')
                                                    ->label('Parentesco o relación')
                                                    ->prefixIcon('heroicon-o-heart')
                                                    ->placeholder('Ej. Madre, hermano, cónyuge')
                                                    ->maxLength(100),
                                            ])
                                            ->columns([
                                                'default' => 1,
                                                'md' => 3,
                                            ]),

                                        Placeholder::make('ayuda_emergencia')
                                            ->label('Recomendación')
                                            ->content('Mantenga estos datos actualizados y confirme periódicamente que el número siga vigente.')
                                            ->extraAttributes(['class' => 'rounded-lg bg-warning-50 p-4 dark:bg-warning-950']),
                                    ]),
                            ])
                            ->columnSpan([
                                'default' => 12,
                                'lg' => 8,
                                'xl' => 9,
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getEmployeeTableQuery())
            ->columns([
                ImageColumn::make('foto_url')
                    ->label('')
                    ->circular()
                    ->size(48)
                    ->defaultImageUrl(asset('images/default-avatar.jpg')),

                TextColumn::make('full_name')
                    ->label('Empleado')
                    ->searchable(['nombres', 'apellidos', 'ci'])
                    ->sortable(['apellidos', 'nombres'])
                    ->weight('bold')
                    ->description(fn (Empleado $record): string => 'CI: '.$record->ci)
                    ->wrap(),

                TextColumn::make('historialActivo.empresa.nombre_comercial')
                    ->label('Empresa y sucursal')
                    ->getStateUsing(fn (Empleado $record): string => $record->historialActivo?->empresa?->nombre_comercial
                        ?: $record->historialActivo?->empresa?->razon_social
                        ?: 'Sin asignación')
                    ->description(fn (Empleado $record): string => $record->historialActivo?->sucursal?->nombre ?? 'Sin sucursal')
                    ->badge()
                    ->color(fn (Empleado $record): string => $record->historialActivo ? 'primary' : 'gray')
                    ->searchable(),

                TextColumn::make('historialActivo.cargo.nombre')
                    ->label('Cargo')
                    ->placeholder('Sin cargo')
                    ->description(fn (Empleado $record): string => $record->historialActivo?->tipo_contrato ?? 'Sin contrato')
                    ->wrap(),

                TextColumn::make('telefono_personal')
                    ->label('Contacto')
                    ->icon('heroicon-o-phone')
                    ->placeholder('Sin teléfono')
                    ->description(fn (Empleado $record): string => $record->correo_personal ?: 'Sin correo personal')
                    ->toggleable(),

                TextColumn::make('historialActivo.fecha_inicio')
                    ->label('Ingreso')
                    ->date('d/m/Y')
                    ->placeholder('Sin fecha')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('historialActivo.fecha_fin')
                    ->label('Fin de contrato')
                    ->getStateUsing(fn (Empleado $record): string => static::getContractEndLabel($record))
                    ->badge()
                    ->color(fn (Empleado $record): string => static::getContractEndColor($record))
                    ->toggleable(),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn (Empleado $record): string => $record->activo ? 'Empleado activo' : 'Empleado inactivo'),

                TextColumn::make('historialActivo.salario')
                    ->label('Salario')
                    ->money('BOB')
                    ->placeholder('Sin asignar')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->options(fn (): array => Empresa::query()
                        ->where('empresa_activo', true)
                        ->orderBy('nombre_comercial')
                        ->get()
                        ->mapWithKeys(fn (Empresa $empresa): array => [
                            $empresa->id => $empresa->nombre_comercial ?: $empresa->razon_social,
                        ])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'historialActivo',
                            fn (Builder $query): Builder => $query->where('empresa_id', $data['value']),
                        ),
                    ))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tipo_contrato')
                    ->label('Tipo de contrato')
                    ->options([
                        'Contrato plazo fijo' => 'Contrato plazo fijo',
                        'Contrato indefinido' => 'Contrato indefinido',
                        'Contrato por servicios' => 'Contrato por servicios',
                        'Contrato por obra' => 'Contrato por obra',
                        'Contrato por temporada' => 'Contrato por temporada',
                        'Contrato de teletrabajo' => 'Contrato de teletrabajo',
                        'Pasante' => 'Pasante',
                        'Otro' => 'Otro',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        filled($data['value'] ?? null),
                        fn (Builder $query): Builder => $query->whereHas(
                            'historialActivo',
                            fn (Builder $query): Builder => $query->where('tipo_contrato', $data['value']),
                        ),
                    )),

                TernaryFilter::make('activo')
                    ->label('Estado del empleado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos')
                    ->default(true),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Administrar')
                    ->icon('heroicon-o-pencil-square')
                    ->tooltip('Editar datos personales e historial laboral'),
            ])
            ->recordUrl(fn (Empleado $record): string => static::getUrl('edit', ['record' => $record]))
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->paginationPageOptions([25, 50, 100, 'all'])
            ->defaultPaginationPageOption(25)
            ->searchPlaceholder('Buscar por nombre, apellido o CI...')
            ->striped()
            ->emptyStateHeading('No hay empleados registrados')
            ->emptyStateDescription('Cree el primer empleado y luego complete su historial laboral.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'historialActivo.cargo',
                'historialActivo.empresa',
                'historialActivo.sucursal',
            ]);
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',
            'create',
            'update',
            'ver_empleados_sucursal',
            'ver_empleados_todos',
        ];
    }

    public static function getRelations(): array
    {
        return [
            HistorialLaboralRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmpleados::route('/'),
            'create' => Pages\CreateEmpleado::route('/create'),
            'edit' => Pages\EditEmpleado::route('/{record}/edit'),
        ];
    }

    private static function getEmployeeTableQuery(): Builder
    {
        $query = static::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $puedeVerTodos = $user->can('ver_empleados_todos_r::r::h::h::empleado');
        $puedeVerSucursal = $user->can('ver_empleados_sucursal_r::r::h::h::empleado');

        if ($puedeVerTodos) {
            return $query;
        }

        if (! $puedeVerSucursal) {
            return $query;
        }

        $sucursalId = $user->empleado?->historialActivo?->sucursal_id;

        return $sucursalId
            ? $query->whereHas('historialActivo', fn (Builder $query): Builder => $query->where('sucursal_id', $sucursalId))
            : $query->whereRaw('1 = 0');
    }

    private static function makeSeparationAction(): Action
    {
        return Action::make('desvincular')
            ->label('Desvincular empleado')
            ->icon('heroicon-o-user-minus')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Confirmar desvinculación')
            ->modalDescription('Se cerrará el historial laboral activo y el empleado quedará inactivo. Esta acción conservará todo su historial.')
            ->modalSubmitActionLabel('Confirmar desvinculación')
            ->form([
                Textarea::make('motivo')
                    ->label('Motivo')
                    ->placeholder('Ej. Renuncia voluntaria, conclusión de contrato...')
                    ->helperText('El motivo quedará registrado en las observaciones laborales.')
                    ->required()
                    ->rows(4)
                    ->maxLength(2000),
            ])
            ->action(function (array $data, $livewire): void {
                $empleado = $livewire->getRecord();

                if (! $empleado instanceof Empleado) {
                    return;
                }

                DB::transaction(function () use ($empleado, $data): void {
                    $historial = HistorialLaboral::query()
                        ->where('empleado_id', $empleado->id)
                        ->where('activo', true)
                        ->latest('id')
                        ->first();

                    if ($historial) {
                        $auditoria = sprintf(
                            "DESVINCULACIÓN\nFecha: %s\nUsuario: %s\nMotivo: %s",
                            now()->format('d/m/Y H:i'),
                            Auth::user()?->name ?? 'Sistema',
                            $data['motivo'],
                        );

                        $historial->update([
                            'fecha_baja' => now(),
                            'fecha_fin' => $historial->fecha_fin ?? now(),
                            'activo' => false,
                            'observaciones' => trim(collect([
                                $historial->observaciones,
                                $auditoria,
                            ])->filter()->implode("\n\n")),
                        ]);
                    }

                    HistorialLaboral::query()
                        ->where('empleado_id', $empleado->id)
                        ->where('activo', true)
                        ->update(['activo' => false]);

                    $empleado->update(['activo' => false]);
                });

                Notification::make()
                    ->title('Empleado desvinculado')
                    ->body('El historial laboral fue cerrado correctamente.')
                    ->success()
                    ->send();
            });
    }

    private static function titleCase(?string $value): ?string
    {
        return filled($value)
            ? mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8')
            : $value;
    }

    /**
     * Comprueba que todos los campos relevantes de una pestaña tengan datos.
     * El valor cero (por ejemplo, cero hijos) se considera una respuesta válida.
     *
     * @param  array<int, string>  $fields
     */
    private static function isTabComplete(Get $get, array $fields): bool
    {
        return collect($fields)->every(function (string $field) use ($get): bool {
            $value = $get($field);

            if (is_array($value)) {
                return $value !== [];
            }

            return $value === 0 || $value === '0' || filled($value);
        });
    }

    /** @param array<int, string> $fields */
    private static function tabCompletionLabel(Get $get, array $fields): string
    {
        return static::isTabComplete($get, $fields) ? 'Completo' : 'Incompleto';
    }

    /** @param array<int, string> $fields */
    private static function tabCompletionColor(Get $get, array $fields): string
    {
        return static::isTabComplete($get, $fields) ? 'success' : 'warning';
    }

    private static function getContractEndLabel(Empleado $empleado): string
    {
        $fechaFin = $empleado->historialActivo?->fecha_fin;

        if (! $empleado->historialActivo) {
            return 'Sin contrato';
        }

        if (! $fechaFin) {
            return 'Indefinido';
        }

        $dias = Carbon::today()->diffInDays($fechaFin, false);
        $fecha = $fechaFin->format('d/m/Y');

        return match (true) {
            $dias < 0 => $fecha.' · Vencido',
            $dias <= 15 => $fecha.' · '.$dias.' días',
            default => $fecha,
        };
    }

    private static function getContractEndColor(Empleado $empleado): string
    {
        $fechaFin = $empleado->historialActivo?->fecha_fin;

        if (! $empleado->historialActivo) {
            return 'gray';
        }

        if (! $fechaFin) {
            return 'primary';
        }

        $dias = Carbon::today()->diffInDays($fechaFin, false);

        return match (true) {
            ! $empleado->activo => 'gray',
            $dias < 0 => 'danger',
            $dias <= 15 => 'warning',
            default => 'success',
        };
    }
}
