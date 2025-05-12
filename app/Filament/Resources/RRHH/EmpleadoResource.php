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
use Filament\Forms\Components\Textarea;
use Illuminate\Support\Facades\Log;


class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $modelLabel = 'Empleado';
    protected static ?string $pluralModelLabel = 'Listado de Empleados';
    protected static ?string $navigationLabel = 'Empleados';
    protected static ?string $navigationGroup = 'Recursos Humanos';
    protected static ?int $navigationSort = 1;

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
                                //->columnSpanFull(),

                                Placeholder::make('email')
                                    ->label('Email empresa:')
                                    ->content(fn($get) => ' ' . $get('correo_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold'])
                                    ->columnSpanFull(),

                                Placeholder::make('numero_coporativo')
                                    ->label('Teléfono Corporativo:')
                                    ->content(fn($get) => ' ' . $get('numero_corporativo'))
                                    ->extraAttributes(['class' => 'text-center text-lg font-bold']),
                                //->columnSpanFull(),

                                Toggle::make('activo')
                                    ->default(true)
                                    ->label('Empleado activo')
                                    //->hint('Indica si está actualmente en la empresa')
                                    //->hintIcon('heroicon-o-question-mark-circle')
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

                        // En tu formulario (EmpleadoResource.php)
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
                                'contrato_plazo_fijo' => 'Contrato plazo fijo',
                                'contrato_indefinido' => 'Contrato indefinido',
                                'Contrato_servicios' => 'Contrato por servicios',
                                'contrato_obra' => 'Contrato por obra',
                                'planta' => 'Planta',
                                'pasante' => 'Pasante',
                                'periodo_prueba' => 'Periodo de prueba',
                                'otro' => 'Otro tipo',
                            ])

                            ->label('Estado de Contrato')
                            ->hint('Situación actual del contrato laboral')
                            ->hintIcon('heroicon-o-document-text'),

                        TextInput::make('salario')
                            ->numeric()
                            ->prefix('Bs.')
                            ->label('Salario Mensual')
                            ->hint('Si el dato no es el correcto comuniquese con RRHH')
                            ->hintIcon('heroicon-o-currency-dollar'),

                        TextInput::make('cargo')
                            ->disabled()
                            ->label('Cargo')
                            ->hint('Puesto o función actual del empleado')
                            ->hintIcon('heroicon-o-briefcase'),

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
                //->extraAttributes(['class' => 'border-2 border-gray-100']),

                TextColumn::make('nombres')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Empleado $record) => $record->apellidos),

                TextColumn::make('ci')
                    ->label('CI')
                    ->searchable()
                    ->sortable(),

                // TextColumn::make('cargo')
                //     ->searchable()
                //     ->sortable(),

                TextColumn::make('empresa')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('estado_contrato')
                    ->label('Contrato')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pasante' => 'info',
                        'contrato_plazo_fijo' => 'warning',
                        'contrato_indefinido' => 'success',
                        'Contrato_servicios' => 'danger',
                        'Practicante' => 'danger',
                        default => 'gray',
                    }),

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
                        'contrato_plazo_fijo' => 'Contrato plazo fijo',
                        'contrato_indefinido' => 'Contrato indefinido',
                        'Contrato_servicios' => 'Contrato por servicios',
                        'contrato_obra' => 'Contrato por obra',
                        'planta' => 'Planta',
                        'pasante' => 'Pasante',
                        'periodo_prueba' => 'Periodo de prueba',
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

    public static function getRelations(): array
    {
        return [
            //
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
