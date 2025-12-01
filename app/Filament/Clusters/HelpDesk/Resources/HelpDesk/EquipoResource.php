<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;
use App\Models\HelpDesk\Equipo;
use App\Models\RRHH\Empleado;
use App\Models\Sistema\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Get;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Tables\Actions\BulkAction;

class EquipoResource extends Resource
{
    protected static ?string $model = Equipo::class;
    protected static ?string $cluster = HelpDesk::class;
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationGroup = 'Parámetros';
    protected static ?string $navigationLabel = 'Equipos';
    protected static ?string $modelLabel = 'Equipo';
    protected static ?string $pluralModelLabel = 'Equipos';
    protected static ?string $navigationDescription = 'Gestiona el inventario de equipos médicos y tecnológicos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Primera fila: Foto + Información básica lado a lado
                Group::make()
                    ->schema([
                        Grid::make()
                            ->schema([
                                // Columna de la foto
                                Group::make()
                                    ->schema([
                                        Section::make('Imagen del Equipo')
                                            ->description('Suba una foto clara del equipo')
                                            ->icon('heroicon-o-camera')
                                            ->schema([
                                                TextInput::make('codigo')
                                                    ->disabled()
                                                    ->label(' ')
                                                    ->placeholder('Codigo del equipo')
                                                    ->prefixIcon('heroicon-o-qr-code'),
                                                FileUpload::make('foto_equipo')
                                                    ->label(' ')
                                                    ->image()
                                                    ->directory('equipos/fotos')
                                                    ->disk('public')
                                                    ->visibility('public')
                                                    ->imageEditor()
                                                    ->imageEditorAspectRatios([
                                                        '1:1',
                                                        '4:3',
                                                        '16:9',
                                                    ])
                                                    ->loadingIndicatorPosition('center')
                                                    ->panelAspectRatio('1:1')
                                                    ->removeUploadedFileButtonPosition('center')
                                                    ->uploadButtonPosition('center')
                                                    ->uploadProgressIndicatorPosition('center')
                                                    ->imageCropAspectRatio('1:1')
                                                    ->default(fn($record) => $record?->foto_equipo ?? null)
                                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                                        $codigo = $get('codigo') ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('codigo')) : 'equipo_' . uniqid();
                                                        return $codigo . '.' . $file->getClientOriginalExtension();
                                                    })
                                                    ->imagePreviewHeight('200')
                                                    ->previewable(true)
                                                    ->placeholder(fn($state): string => $state ? '' : 'No hay imagen cargada')
                                                    ->extraAttributes([
                                                        'class' => 'rounded-2xl shadow-lg hover:shadow-xl transition-shadow duration-300',
                                                    ])
                                                    ->helperText('Formatos: JPG, PNG, WEBP. Máx: 5MB'),
                                            ])
                                            ->compact(),

                                        Section::make('Estado del Equipo')
                                            ->description(' ')
                                            ->icon('heroicon-o-power')
                                            ->schema([
                                                Toggle::make('activo')
                                                    ->label('Equipo Activo')
                                                    ->default(true)
                                                    ->onColor('success')
                                                    ->offColor('danger')
                                                    ->helperText('Desactive para dar de baja del sistema'),
                                            ])
                                            ->compact(),
                                    ])
                                    ->columnSpan([
                                        'sm' => 1,
                                        'lg' => 1,
                                    ]),

                                // Columna de información básica
                                Group::make()
                                    ->schema([
                                        Section::make('Información del Equipo')
                                            ->description('Datos técnicos y de identificación')
                                            ->icon('heroicon-o-information-circle')
                                            ->schema([
                                                Grid::make()
                                                    ->schema([
                                                        Select::make('cliente_id')
                                                            ->label('Cliente')
                                                            ->relationship('cliente', 'razon_social')
                                                            ->searchable()
                                                            ->preload()
                                                            ->required()
                                                            ->placeholder('Seleccione el cliente')
                                                            ->helperText('Institución dueña del equipo')
                                                            ->prefixIcon('heroicon-o-user-group'),

                                                        Select::make('empresa_id')
                                                            ->label('Empresa Vendedora')
                                                            ->relationship('empresa', 'razon_social')
                                                            ->searchable()
                                                            ->preload()
                                                            ->required()
                                                            ->placeholder('Seleccione la empresa')
                                                            ->helperText('Proveedor o fabricante')
                                                            ->prefixIcon('heroicon-o-building-storefront'),

                                                        Select::make('sucursal_id')
                                                            ->label('Sucursal')

                                                            ->required()
                                                            ->options(
                                                                fn(Get $get) =>
                                                                $get('empresa_id')
                                                                    ? Sucursal::where('empresa_id', $get('empresa_id'))->pluck('nombre', 'id')->toArray()
                                                                    : []
                                                            )
                                                            ->reactive()
                                                            ->searchable()
                                                            ->placeholder('Seleccione sucursal')
                                                            ->helperText('Ubicación física del equipo')
                                                            ->prefixIcon('heroicon-o-map-pin'),

                                                    ])
                                                    ->columns([
                                                        'sm' => 1,
                                                        'lg' => 3,
                                                    ]),

                                                Grid::make()
                                                    ->schema([

                                                        Select::make('marca')
                                                            ->label('Fabricante')
                                                            ->options([
                                                                'ACCUMAX LAB DEVICES PRIVATE LIMITED' => 'ACCUMAX LAB DEVICES PRIVATE LIMITED',
                                                                'ANGELATONI LIFESCIENCE' => 'ANGELATONI LIFESCIENCE',
                                                                'BV OPTICAL' => 'BV OPTICAL',
                                                                'CARETIUM' => 'CARETIUM',
                                                                'CYPRESS DIAGNOSTICS' => 'CYPRESS DIAGNOSTICS',
                                                                'DIATRON' => 'DIATRON',
                                                                'DYMIND' => 'DYMIND',
                                                                'DX GEN' => 'DX GEN',
                                                                'ELGA' => 'ELGA',
                                                                'ERBA LACHEMA' => 'ERBA LACHEMA',
                                                                'FORTRESS' => 'FORTRESS',
                                                                'GLOBE SCIENTIFIC' => 'GLOBE SCIENTIFIC',
                                                                'GELOPAR' => 'GELOPAR',
                                                                'HERASCIENTIFIC' => 'HERASCIENTIFIC',
                                                                'INTERBIOLAB' => 'INTERBIOLAB',
                                                                'MEDICA' => 'MEDICA',
                                                                'MSE' => 'MSE',
                                                                'ORTHO CLINICAL DIAGNOSTIC' => 'ORTHO CLINICAL DIAGNOSTIC',
                                                                'PARAMEDICAL' => 'PARAMEDICAL',
                                                                'QUALMEDI TECHNOLOGY CO., LTD' => 'QUALMEDI TECHNOLOGY CO., LTD',
                                                                'SHENZEIN BESTMAN INSTRUMENT CO.' => 'SHENZEIN BESTMAN INSTRUMENT CO.',
                                                                'UNICO' => 'UNICO',
                                                                'UNITED PRODUCTS INSTRUMENTS INC' => 'UNITED PRODUCTS INSTRUMENTS INC',
                                                                'URIT' => 'URIT',
                                                                'EUROINMUN' => 'EUROINMUN',
                                                                'EUROIMMUN' => 'EUROIMMUN',
                                                                'ZYBIO INC' => 'ZYBIO INC',
                                                                'OTRO' => 'OTRO',
                                                            ])
                                                            ->searchable()
                                                            ->required()
                                                            ->placeholder('Seleccione fabricante')
                                                            ->helperText('Marca del equipo')
                                                            ->prefixIcon('heroicon-o-tag'),

                                                        TextInput::make('modelo')
                                                            ->required()
                                                            ->placeholder('Ej: XT-2000i')
                                                            ->helperText('Modelo específico del equipo')
                                                            ->prefixIcon('heroicon-o-cog'),

                                                        TextInput::make('num_serie')
                                                            ->required()
                                                            ->placeholder('Ej: SN123456789')
                                                            ->helperText('Número de serie único')
                                                            ->prefixIcon('heroicon-o-qr-code'),


                                                    ])
                                                    ->columns([
                                                        'sm' => 1,
                                                        'lg' => 3,
                                                    ]),

                                                RichEditor::make('descripcion')
                                                    ->required()
                                                    ->label('Descripción Detallada')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'italic',
                                                        'underline',
                                                        'bulletList',
                                                        'orderedList',
                                                        'link',
                                                        'redo',
                                                        'undo',
                                                    ])
                                                    ->placeholder('Describa las características técnicas, funciones y especificaciones del equipo...')
                                                    ->columnSpanFull()
                                                    ->extraInputAttributes([
                                                        'style' => 'min-height: 200px;',
                                                    ]),
                                            ]),
                                    ])
                                    ->columnSpan([
                                        'sm' => 1,
                                        'lg' => 2,
                                    ]),
                            ])
                            ->columns([
                                'sm' => 1,
                                'lg' => 3,
                            ]),
                    ])
                    ->columnSpanFull(),

                // Segunda fila: Información de Compra y Garantía (full width)
                Section::make('Información de Compra y Garantía')
                    ->description('Datos de adquisición y cobertura de garantía')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Grid::make()
                            ->schema([
                                // Placeholder para mostrar qué campos son visibles
                                Forms\Components\Placeholder::make('info_campos')
                                    ->content(function (Get $get): string {
                                        $tipo = $get('tipo_venta');
                                        if (!$tipo) return 'Seleccione un tipo de venta para ver los campos y una descripción corta';

                                        $descripcion = match ($tipo) {
                                            'Venta' => '📋 Equipo vendido con factura al cliente, fecha de entrega comercial y documentos de garantía',
                                            'Comodato' => '📋 Equipo en préstamo al cliente con condiciones de compra de reactivos',
                                            'Alquiler' => '📋 Equipo en préstamo al cliente con condiciones de pago por uso',
                                            'Prestamo' => '📋 Equipo entregado temporalmente por negociaciones comerciales',
                                            'Devolucion' => '📋 Equipo devuelto por el cliente por fallas técnicas de fábrica',
                                            'Repocicion' => '📋 Equipo entregado al cliente por repocicion del equipo devuelto',

                                            default => '📋 Campos no definidos para este tipo'
                                        };

                                        return $descripcion;
                                    })
                                    ->columnSpanFull(),
                                //->extraAttributes(['class' => 'bg-blue-50 border border-blue-200 rounded-lg p-4']),
                                Select::make('tipo_venta')
                                    ->label('Tipo de Venta')
                                    ->options([
                                        'Comodato' => 'Comodato',
                                        'Venta' => 'Venta',
                                        'Alquiler' => 'Alquiler',
                                        'Prestamo' => 'Préstamo',
                                        'Devolucion' => 'Devolución',
                                        'Repocicion' => 'Repocición',
                                        'Otro' => 'Otro',
                                    ])
                                    ->placeholder('Seleccione el tipo de venta')
                                    ->helperText('Modalidad de la transacción')
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->required()
                                    ->prefixIcon('heroicon-o-shopping-bag'),

                                // Campos que siempre se muestran
                                DatePicker::make('fecha_entrega')
                                    ->label('Fecha de Entrega')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Fecha de entrega del equipo')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->visible(function (Get $get): bool {
                                        $tipo = $get('tipo_venta');
                                        return in_array($tipo, ['Comodato', 'Alquiler', 'Prestamo', 'Devolucion', 'Venta', 'Repocicion', 'Otro']);
                                    }),

                                DatePicker::make('fecha_instalacion')
                                    ->label('Fecha de Instalación')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Fecha de instalación del equipo')
                                    ->prefixIcon('heroicon-o-wrench-screwdriver')
                                    ->visible(function (Get $get): bool {
                                        $tipo = $get('tipo_venta');
                                        return in_array($tipo, ['Comodato', 'Alquiler', 'Prestamo', 'Devolucion', 'Venta', 'Repocicion', 'Otro']);
                                    }),

                                DatePicker::make('fecha_devolucion')
                                    ->label('Fecha de Devolución')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Fecha de devolución (si aplica)')
                                    ->prefixIcon('heroicon-o-arrow-left')
                                    ->visible(function (Get $get): bool {
                                        $tipo = $get('tipo_venta');
                                        return in_array($tipo, ['Comodato', 'Alquiler', 'Prestamo', 'Devolucion', 'Otro']);
                                    }),

                                DatePicker::make('garantia_desde')
                                    ->label('Inicio de Garantía')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Fecha inicial de la garantía')
                                    ->prefixIcon('heroicon-o-shield-check')
                                    ->visible(function (Get $get): bool {
                                        $tipo = $get('tipo_venta');
                                        return in_array($tipo, ['Venta', 'Devolucion', 'Repocicion', 'Otro']);
                                    }),

                                DatePicker::make('garantia_hasta')
                                    ->label('Fin de Garantía')
                                    ->displayFormat('d/m/Y')
                                    ->helperText('Fecha de vencimiento de garantía')
                                    ->prefixIcon('heroicon-o-clock')
                                    ->visible(function (Get $get): bool {
                                        $tipo = $get('tipo_venta');
                                        return in_array($tipo, ['Venta', 'Devolucion', 'Repocicion', 'Otro']);
                                    }),

                                FileUpload::make('doc_adjunto')
                                    ->label('Documento Adjunto')
                                    ->directory('garantia_equipos')
                                    ->disk('public')
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(5120)
                                    ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                        $codigo = $get('codigo')
                                            ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('codigo'))
                                            : 'documento_' . uniqid();
                                        return $codigo . '.pdf';
                                    })
                                    ->helperText('PDF de garantía o contrato (máx. 5MB)')
                                    ->downloadable()
                                    ->openable()
                                    ->visible(function (Get $get): bool {
                                        $tipo = $get('tipo_venta');
                                        return in_array($tipo, ['Comodato', 'Alquiler', 'Prestamo', 'Devolucion', 'Venta', 'Repocicion', 'Otro']);
                                    }),
                            ])
                            ->columns([
                                'sm' => 1,
                                'md' => 2,
                                'lg' => 3,
                            ]),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->placeholder('Observaciones durante la entrega, instalación o funcionamiento...')
                            ->helperText('Notas relevantes sobre el equipo')
                            ->rows(3)
                            ->visible(function (Get $get): bool {
                                $tipo = $get('tipo_venta');
                                return in_array($tipo, ['Comodato', 'Alquiler', 'Prestamo', 'Devolucion', 'Venta', 'Repocicion', 'Otro']);
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Tercera fila: Mantenimiento y Ubicación lado a lado
                Grid::make()
                    ->schema([
                        // Mantenimiento y Soporte
                        Section::make('Mantenimiento y Soporte')
                            ->description('Programación de mantenimiento y contacto técnico')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Grid::make()
                                    ->schema([
                                        Group::make()
                                            ->schema([
                                                Forms\Components\Placeholder::make('frecuencia_mantenimiento')
                                                    ->label('Frecuencia de Mantenimiento')
                                                    ->content(
                                                        fn(Get $get): string =>
                                                        $get('freq_mantenimiento.value') && $get('freq_mantenimiento.key')
                                                            ? "{$get('freq_mantenimiento.value')} veces por {$get('freq_mantenimiento.key')}"
                                                            : 'No definida'
                                                    )
                                                    ->extraAttributes(['class' => 'text-lg font-semibold']),

                                                Grid::make()
                                                    ->schema([
                                                        TextInput::make('freq_mantenimiento.value')
                                                            ->required()
                                                            ->label('Frecuencia')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->placeholder('Ej: 6')
                                                            ->helperText('Número')
                                                            ->suffixIcon('heroicon-o-clock'),

                                                        Select::make('freq_mantenimiento.key')
                                                            ->required()
                                                            ->label('Periodo')
                                                            ->options([
                                                                'semana' => 'Semana',
                                                                'mes' => 'Mes',
                                                                'bimestre' => 'Bimestre',
                                                                'trimestre' => 'Trimestre',
                                                                'cuatrimestre' => 'Cuatrimestre',
                                                                'semestre' => 'Semestre',
                                                                'año' => 'Año',
                                                            ])
                                                            ->placeholder('Seleccione')
                                                            ->helperText('Unidad de tiempo'),
                                                    ])
                                                    ->columns(2),
                                            ])
                                            ->columnSpan([
                                                'sm' => 1,
                                                'lg' => 1,
                                            ]),

                                        Group::make()
                                            ->schema([
                                                Select::make('tecnico_asignado')
                                                    ->required()
                                                    ->relationship('tecnico', 'nombres') // Campo base para la relación
                                                    ->getOptionLabelFromRecordUsing(fn(Empleado $record) => $record->full_name)
                                                    ->label('Técnico Responsable')
                                                    ->searchable()
                                                    ->preload()
                                                    ->placeholder('Asigne un técnico')
                                                    ->helperText('Personal asignado para mantenimiento')
                                                    ->prefixIcon('heroicon-o-user'),

                                                TextInput::make('tel_soporte')
                                                    ->required()
                                                    ->label('Teléfono de Soporte')
                                                    ->tel()
                                                    ->placeholder('Ej: +591 12345678')
                                                    ->helperText('Contacto directo')
                                                    ->prefixIcon('heroicon-o-phone'),
                                            ])
                                            ->columnSpan([
                                                'sm' => 1,
                                                'lg' => 1,
                                            ]),
                                    ])
                                    ->columns([
                                        'sm' => 1,
                                        'lg' => 1,
                                    ]),
                            ])
                            ->columnSpan([
                                'sm' => 1,
                                'lg' => 1,
                            ]),

                        // Ubicación
                        Section::make('Ubicación del Equipo')
                            ->description('Localización física y coordenadas')
                            ->icon('heroicon-o-map')
                            ->schema([
                                Textarea::make('direccion')
                                    ->label('Dirección Exacta')
                                    ->required()
                                    ->placeholder('Describa la ubicación específica dentro de la institución...')
                                    ->helperText('Ej: Piso 2, Laboratorio Central, Ala Norte')
                                    ->rows(6)
                                    ->columnSpanFull(),

                                Field::make('ubicacion_gps')
                                    ->view('filament.forms.components.map-picker'),
                            ])
                            ->columnSpan([
                                'sm' => 1,
                                'lg' => 1,
                            ]),
                    ])
                    ->columns([
                        'sm' => 1,
                        'lg' => 2,
                    ])
                    ->columnSpanFull(),
            ])
            ->columns(1); // Layout principal de una columna para mejor responsividad
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('foto_equipo')
                    ->label('')
                    ->square()
                    ->defaultImageUrl(url('/images/default-product.jpg'))
                    ->size(50),

                TextColumn::make('codigo')
                    ->label('Código')
                    ->sortable()
                    ->searchable()
                    ->weight('semibold')
                    ->color('primary')
                    ->description(fn(Equipo $record): string => $record->marca ?? 'Sin marca'),

                TextColumn::make('modelo')
                    ->label('Modelo')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('num_serie')
                    ->label('N° Serie')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Número de serie copiado')
                    ->copyMessageDuration(1500),

                TextColumn::make('cliente.razon_social')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->description(fn(Equipo $record): string => $record->empresa?->razon_social ?? 'Sin empresa'),

                TextColumn::make('garantia_hasta')
                    ->label('Garantía')
                    ->date('d/m/Y')
                    ->color(
                        fn(Equipo $record): string =>
                        $record->garantia_hasta && $record->garantia_hasta->isFuture()
                            ? ($record->garantia_hasta->diffInDays(now()) < 30 ? 'warning' : 'success')
                            : 'danger'
                    )
                    ->description(
                        fn(Equipo $record): string =>
                        $record->garantia_hasta
                            ? ($record->garantia_hasta->isFuture()
                                ? 'Vence en ' . $record->garantia_hasta->diffForHumans()
                                : 'Vencida')
                            : 'Sin garantía'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ubicacion_gps')
                    ->label('Ubicación')
                    ->icon('heroicon-o-map-pin')
                    ->color('danger')
                    ->tooltip('Google Maps')
                    ->url(
                        fn(Equipo $record): ?string =>
                        $record->ubicacion_gps &&
                            isset($record->ubicacion_gps['lat']) &&
                            isset($record->ubicacion_gps['lng'])
                            ? "https://www.google.com/maps?q={$record->ubicacion_gps['lat']},{$record->ubicacion_gps['lng']}"
                            : 'Sin datos'
                    )
                    ->openUrlInNewTab()
                    ->disabled(
                        fn(Equipo $record): bool =>
                        !($record->ubicacion_gps &&
                            isset($record->ubicacion_gps['lat']) &&
                            isset($record->ubicacion_gps['lng']))
                    ),

                IconColumn::make('doc_adjunto')
                    ->label('PDF')
                    ->icon(fn($state): string => $state ? 'heroicon-o-document-check' : 'heroicon-o-x-circle')
                    ->color(fn($state): string => $state ? 'primary' : 'danger')
                    ->tooltip(fn($state): string => $state ? 'Descargar PDF' : 'Sin documento')
                    ->url(fn($state) => $state ? Storage::url($state) : null)
                    ->openUrlInNewTab()
                    ->disabled(fn($state): bool => !$state),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->sortable(),

            ])
            ->filters([
                TernaryFilter::make('activo')
                    ->label('Estado Activo')
                    ->placeholder('Todos los equipos')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                SelectFilter::make('cliente_id')
                    ->label('Cliente')
                    ->relationship('cliente', 'razon_social')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('tecnico_asignado')
                    ->label('Técnico Asignado')
                    ->relationship('tecnico', 'nombres') // Campo base para la relación
                    ->getOptionLabelFromRecordUsing(fn(Empleado $record) => $record->full_name)
                    ->label('Técnico Responsable')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make()
                    ->color('blue')
                    ->icon('heroicon-o-eye'),
            ])
            ->bulkActions([
                BulkAction::make('generar_qr_pdf')
                    ->label('Generar Códigos QR (PDF)')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function ($records) {
                        // Generar QR codes y datos para cada equipo
                        $equiposData = [];

                        foreach ($records as $equipo) {
                            // URL del equipo
                            $url = url('/equipos/' . $equipo->codigo);

                            // Usar formato SVG (no necesita Imagick)
                            $qrCode = QrCode::format('svg')
                                ->size(200)
                                ->generate($url);

                            // Para SVG, lo guardamos como texto
                            $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrCode);                           

                            $equiposData[] = [
                                'codigo' => $equipo->codigo,
                                'marca' => $equipo->marca ?? 'N/A',
                                'modelo' => $equipo->modelo ?? 'N/A',
                                'num_serie' => $equipo->num_serie ?? 'N/A',
                                'empresa' => $equipo->empresa->razon_social ?? 'N/A',
                                'cliente' => $equipo->cliente->razon_social ?? 'N/A',
                                'url' => $url,
                                'qr_code' => $qrCodeBase64,
                            ];
                        }

                        // Verificar si hay datos
                        if (empty($equiposData)) {
                            throw new \Exception('No se seleccionaron equipos para generar códigos QR.');
                        }

                        $pdf = Pdf::loadView('exports.equipos-qr', [
                            'equipos' => $equiposData,
                            'fecha' => now()->format('d/m/Y H:i:s'),
                            'total' => count($equiposData),
                        ]);

                        // Configurar el PDF
                        $pdf->setPaper('A4', 'portrait');
                        $pdf->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'sans-serif',
                        ]);

                        // Descargar el PDF
                        return response()->streamDownload(
                            fn() => print($pdf->output()),
                            'codigos-qr-equipos-' . now()->format('Y-m-d-H-i-s') . '.pdf'
                        );
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Generar Códigos QR en PDF')
                    ->modalDescription('¿Está seguro de generar códigos QR para los equipos seleccionados? Se generará un PDF descargable.')
                    ->modalSubmitActionLabel('Generar PDF')
                    ->deselectRecordsAfterCompletion(),
              
            ])
            ->emptyStateHeading('No hay equipos registrados')
            ->emptyStateDescription('Comienza agregando el primer equipo al sistema.')
            ->emptyStateIcon('heroicon-o-cpu-chip')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Equipo')
                    ->icon('heroicon-o-plus'),
            ])
            ->deferLoading()
            ->striped()
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipos::route('/'),
            'create' => Pages\CreateEquipo::route('/create'),
            'edit' => Pages\EditEquipo::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'primary';
    }
}