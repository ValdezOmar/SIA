<?php

namespace App\Filament\Resources\RRHH;

use App\Filament\Resources\RRHH\EmpleadoResource\Pages;
use App\Filament\Resources\RRHH\EmpleadoResource\RelationManagers\HistorialLaboralRelationManager;
use App\Models\RRHH\Empleado;
use App\Models\RRHH\HistorialLaboral;
use App\Models\Sistema\Cargo;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
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
use Filament\Notifications\Notification;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;

class EmpleadoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Empleado::class;
    protected static array $tempEmpleadoData = [];

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Empleados'; //Seccion para configurar el nombre en Filament-Shield
    protected static ?string $pluralModelLabel = 'Listado de Empleados';
    protected static ?string $navigationLabel = 'Empleados';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 1;

    //Formulario de creacion edicion de empleados
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Card superior con foto y datos básicos
                Grid::make()
                    ->schema([
                        FileUpload::make('foto')
                            ->label('')
                            ->image()
                            ->disk('public')
                            ->directory('empleados')
                            ->visibility('public')

                            // UI solamente (NO toca el archivo)
                            ->openable()
                            ->downloadable()
                            ->panelLayout('circle')
                            ->panelAspectRatio('1:1')
                            ->alignCenter()

                            // Control visual del preview
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
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                $ci = $get('ci')
                                    ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('ci'))
                                    : 'default_' . uniqid();

                                return $ci . '.jpg';
                            })
                            ->placeholder(function ($get) {
                                // Si no hay foto, mostrar iniciales con avatar por defecto
                                $nombres = $get('nombres') ?? '';
                                $apellidos = $get('apellidos') ?? '';
                                $iniciales = substr($nombres, 0, 1) . substr($apellidos, 0, 1);

                                return view('filament.forms.components.avatar-placeholder', [
                                    'iniciales' => $iniciales ?: 'NA',
                                    'defaultImage' => asset('images/default-avatar.jpg')
                                ]);
                            })
                            ->rules([
                                'image',
                                'mimes:jpg,jpeg,png',
                                'max:5120', // 5MB
                            ]),

                        Grid::make()
                            ->schema([
                                Placeholder::make('nombre_completo')
                                    ->label('Nombre:')
                                    ->content(fn($get) => $get('nombres') . ' ' . $get('apellidos'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('ci/dni')
                                    ->label('CI/DNI:')
                                    ->content(fn($get) => ' ' . $get('ci'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('email')
                                    ->label('Email:')
                                    ->content(fn($get) => ' ' . $get('correo_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('numero_corporativo')
                                    ->label('Teléfono:')
                                    ->content(fn($get) => ' ' . $get('numero_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Toggle::make('activo')
                                    ->label(fn($state) => $state ? 'Empleado Activo' : 'Empleado Inactivo')
                                    ->live()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, $set, $get, $record) {
                                        if (!$record) return;
                                        $record->activo = $state;
                                        $record->save();
                                    })
                                    ->columnSpanFull(),
                                Actions::make([
                                    Action::make('desvincular')
                                        ->label('Confirmar desvinculación')
                                        ->visible(function ($record) {
                                            // Verifica que el empleado esté inactivo
                                            if (! $record || $record->activo != false) {
                                                return false;
                                            }
                                            // Verifica que exista al menos un historial laboral activo
                                            $tieneHistorialActivo = HistorialLaboral::where('empleado_id', $record->id)
                                                ->where('activo', true)
                                                ->exists();
                                            return $tieneHistorialActivo;
                                        })
                                        ->color('danger')
                                        ->icon('heroicon-o-archive-box-x-mark')
                                        ->requiresConfirmation()
                                        ->modalHeading('Desvincular empleado')
                                        ->modalDescription('Esta acción marcará al empleado como inactivo, cerrará todos los contratos y registrará el motivo.')
                                        ->form([
                                            Textarea::make('motivo')
                                                ->label('Motivo de desvinculación')
                                                ->required()
                                                ->placeholder('Ejemplo: Renuncia voluntaria, fin de contrato, etc.'),
                                        ])
                                        ->action(function (array $data, $livewire) {
                                            /** @var \App\Models\Empleado $empleado */
                                            $empleado = $livewire->getRecord();

                                            if (! $empleado) {
                                                return;
                                            }

                                            DB::transaction(function () use ($empleado, $data) {
                                                $usuario = Auth::user()?->name ?? 'Sistema';
                                                $fecha = now()->format('d/m/Y H:i');

                                                // 🔹 Inactivar todos los contratos del empleado
                                                HistorialLaboral::where('empleado_id', $empleado->id)
                                                    ->update(['activo' => false]);

                                                // 🔹 Actualizar el último contrato con información detallada
                                                $ultimo = HistorialLaboral::where('empleado_id', $empleado->id)
                                                    ->latest('id')
                                                    ->first();

                                                if ($ultimo) {
                                                    $observacionesAnteriores = trim($ultimo->observaciones ?? '');
                                                    $nuevoTexto = <<<TXT
                                                        OBSERVACION:
                                                        {$observacionesAnteriores}
                                                        _________________________________________
                                                        - FECHA DE DESVINCULACIÓN: {$fecha}
                                                        - USUARIO: {$usuario}
                                                        - MOTIVO: {$data['motivo']}
                                                        _________________________________________
                                                        TXT;

                                                    $ultimo->update([
                                                        'observaciones' => trim($nuevoTexto),
                                                        'fecha_baja' => now(),
                                                        'activo' => false,
                                                    ]);
                                                }

                                                // 🔹 Marcar empleado como inactivo
                                                $empleado->update([
                                                    'activo' => false,
                                                    'fecha_desvinculacion' => now(),
                                                ]);
                                            });

                                            Notification::make()
                                                ->title('Empleado desvinculado correctamente')
                                                ->success()
                                                ->send();
                                        }),
                                ])
                                    ->columnSpanFull(),
                            ])
                            ->columnSpan(['md' => 2, 'lg' => 1])
                            ->extraAttributes(['class' => 'flex flex-col justify-center']),
                    ])
                    ->columns(['md' => 2, 'lg' => 2])
                    ->columnSpan('full'),

                //Secciones organizadas en Tabs
                Tabs::make('Información del Empleado')
                    ->tabs([
                        //TAB PERSONAL
                        Tab::make('Personal')
                            ->icon('heroicon-o-user')
                            ->schema([
                                Fieldset::make('Información Básica')
                                    ->schema([
                                        TextInput::make('nombres')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('apellidos')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('ci')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->label('Cédula de Identidad')
                                            //->hint('Número único de identificación')
                                            ->hintIcon('heroicon-o-identification'),

                                        DatePicker::make('fecha_nacimiento')
                                            ->label('Fecha de Nacimiento')
                                            ->hintIcon('heroicon-o-cake'),

                                        Select::make('genero')
                                            ->options([
                                                'hombre' => 'Hombre',
                                                'mujer' => 'Mujer',
                                            ])
                                            ->hint('Género del empleado')
                                            ->hintIcon('heroicon-o-user-circle'),

                                        TextInput::make('nacionalidad')
                                            ->required()
                                            ->default('Boliviana')
                                            ->hint('Nacionalidad')
                                            ->hintIcon('heroicon-o-flag'),


                                    ])
                                    ->columns(3),
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

                                Fieldset::make('Información Adicional')
                                    ->schema([
                                        Select::make('estado_civil')
                                            ->options([
                                                'soltero' => 'Soltero/a',
                                                'casado' => 'Casado/a',
                                                'viudo' => 'Viudo/a',
                                                'divorciado' => 'Divorciado/a',
                                            ])
                                            ->label('Estado Civil')
                                            ->hintIcon('heroicon-o-heart'),

                                        TextInput::make('cantidad_hijos')
                                            ->numeric()
                                            ->default(0)
                                            ->label('Número de Hijos')
                                            ->hintIcon('heroicon-o-user-group'),

                                        TextInput::make('telefono_personal')
                                            ->tel()
                                            ->label('Teléfono Personal')
                                            ->hintIcon('heroicon-o-phone'),

                                        TextInput::make('correo_personal')
                                            ->email()
                                            ->label('Correo Personal')
                                            ->hintIcon('heroicon-o-envelope'),
                                        TextInput::make('nua_cua')
                                            ->label('Número NUA/CUA')
                                            ->hintIcon('heroicon-o-shield-check'),
                                        TextInput::make('afp')
                                            ->label('Gestora')
                                            ->default('Gestora Pública')
                                            ->hintIcon('heroicon-o-banknotes'),


                                    ])
                                    ->columns(3),
                                Fieldset::make('Contacto de Emergencia')
                                    ->schema([
                                        TextInput::make('persona_contacto')
                                            ->label('Nombre de Contacto')
                                            ->hintIcon('heroicon-o-exclamation-triangle'),

                                        TextInput::make('numero_contacto')
                                            ->tel()
                                            ->label('Teléfono de Contacto')
                                            ->hintIcon('heroicon-o-phone'),

                                        TextInput::make('persona_parentesco')
                                            ->label('Parentesco')
                                            ->hintIcon('heroicon-o-heart'),
                                    ])
                                    ->columns(3),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $user = Auth::user();

        $baseQuery = Empleado::query()
            ->with(['empresa', 'sucursal'])
            ->orderBy('rh_empleados.apellidos')
            ->orderBy('rh_empleados.nombres');
        // Verificar si el usuario tiene el permiso específico
        if ($user->can('ver_empleados_sucursal_r::r::h::h::empleado')) {
            if (!$user->can('ver_empleados_todos_r::r::h::h::empleado')) {
                // Buscar el empleado que corresponde al usuario actual
                $empleadoUsuario = Empleado::whereRaw('LOWER(correo_corporativo) = ?', [strtolower($user->email)])->first();
                if ($empleadoUsuario && $empleadoUsuario->sucursal) {
                    // Filtrar por la sucursal del empleado-usuario
                    $baseQuery->where('sucursal', $empleadoUsuario->sucursal);
                } else {
                    // Si no encuentra empleado o no tiene sucursal, no mostrar nada
                    $baseQuery->whereRaw('0 = 1');
                }
            }
        } elseif ($user->can('ver_empleados_todos_r::r::h::h::empleado')) {
            Log::info(" mostrando todos los empleados");
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
                        : '❌ Sin Croquis'),

                ToggleColumn::make('activo')
                    ->label('Estado')
                    ->disabled()
                    ->beforeStateUpdated(function ($state, $record) {
                        // Guardar cambios en tiempo real
                        $record->update([
                            'activo' => $state,
                        ]);

                        // Notificación opcional
                        Notification::make()
                            ->title($state ? 'Empleado activado' : 'Empleado marcado como inactivo')
                            ->success()
                            ->send();
                    }),
            ])

            //Filtros de Busqueda
            ->filters([
                SelectFilter::make('empresa')
                    ->options(function () {
                        return Empresa::where('empresa_activo', true)
                            ->pluck('razon_social', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->label('Empresa'),

                SelectFilter::make('estado_contrato')
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

                TernaryFilter::make('rh_empleados.activo')
                    ->label('Estado Activo')
                    ->default(true),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50, 100, 'all']) // Opciones de paginación disponibles
            ->defaultPaginationPageOption(100)
            ->striped();
    }

    public static function getTempEmpleadoData(): array
    {
        return static::$tempEmpleadoData;
    }

    //Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales       
            'create',
            'update',
            'ver_empleados_sucursal',
            'ver_empleados_todos'
        ];
    }
    //Relaciones 
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
}