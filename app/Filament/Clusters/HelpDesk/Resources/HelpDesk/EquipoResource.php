<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EquipoResource\Pages;
use App\Models\HelpDesk\Equipo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EquipoResource extends Resource
{
    protected static ?string $model = Equipo::class;
    protected static ?string $cluster = HelpDesk::class;
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $navigationGroup = 'Parametros';
    protected static ?string $navigationLabel = 'Equipos';

    public static function form(Form $form): Form
    {
        return $form->schema([
            FileUpload::make('foto_equipo')
                ->label('Foto del Equipo')
                ->image()
                ->directory('equipos/fotos')
                ->disk('public')
                ->visibility('public')
                ->imageEditor()
                ->loadingIndicatorPosition('center')
                ->panelAspectRatio('1:1')
                ->removeUploadedFileButtonPosition('upper-center')
                ->uploadButtonPosition('right')
                ->uploadProgressIndicatorPosition('right')
                ->extraAttributes([
                    'style' => '
            width: 300px;
            height: 300px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 0 30px rgba(0,0,0,0.75);
            display: flex;
            justify-content: center;
        ',
                    'class' => 'flex flex-col items-center hover:scale-105 transition-transform duration-300 ease-in-out',
                ])
                ->imageCropAspectRatio('1:1')
                ->default(fn($record) => $record?->foto_equipo ?? null) // <-- ruta relativa
                ->alignCenter()
                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                    $codigo = $get('codigo') ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('codigo')) : 'equipo_' . uniqid();
                    return $codigo . '.' . $file->getClientOriginalExtension();
                })
                ->imagePreviewHeight('600'),
            Toggle::make('activo')->label('Activo')->default(true),

            Section::make('Datos del Equipo')
                ->schema([
                    Grid::make(3)->schema([
                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->relationship('cliente', 'razon_social')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('empresa_id')
                            ->label('Empresa')
                            ->relationship('empresa', 'razon_social')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('sucursal_id')
                            ->label('Sucursal')
                            ->relationship('sucursalRelacion', 'nombre')
                            ->searchable()
                            ->preload()
                            ->required(),
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
                            ->required(),
                        TextInput::make('modelo')
                            ->required(),
                        TextInput::make('num_serie')
                            ->required(),
                        RichEditor::make('descripcion')
                            ->label('Descripción')
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
                            ->placeholder('Escriba una descripción detallada del equipo...')
                            ->columnSpanFull()
                            ->extraAttributes([
                                'class' => '
                                            rounded-xl
                                            shadow-sm
                                            bg-gray-50 dark:bg-gray-800
                                            text-gray-900 dark:text-gray-100
                                            border border-gray-200 dark:border-gray-700
                                            focus:ring-2 focus:ring-primary-500
                                            transition-all
                                            p-3
                                        ',
                            ])

                    ]),
                ]),

            Section::make('Venta y Garantía')
                ->schema([
                    DatePicker::make('fecha_venta')->label('Fecha de Venta'),
                    DatePicker::make('garantia_desde')->label('Garantía Desde'),
                    DatePicker::make('garantia_hasta')->label('Garantía Hasta'),

                    FileUpload::make('doc_adjunto')
                        ->label('Poliza de garantia')
                        ->directory('garantia_equipos')
                        ->disk('public')
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(5120) // máximo 5MB, ajustable
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                            $codigo = $get('codigo')
                                ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('codigo'))
                                : 'documento_' . uniqid();
                            return $codigo . '.' . $file->getClientOriginalExtension();
                        }),
                ])
                ->columns(4),

            Section::make('Frecuencia de Mantenimiento')
                ->schema([
                    TextInput::make('freq_mantenimiento.value')
                        ->label('Frecuencia')
                        ->numeric()
                        ->required(),
                    Select::make('freq_mantenimiento.key')
                        ->label('Periodo')
                        ->options([
                            'semana' => 'Semana',
                            'mes' => 'Mes',
                            'ano' => 'Año',
                        ])
                        ->required(),

                    Select::make('tecnico_asignado')
                        ->relationship('tecnico', 'nombres')
                        ->label('Técnico Asignado')
                        ->searchable(),
                    TextInput::make('tel_soporte')
                        ->label('Teléfono de Soporte')
                        ->numeric(),
                ])->columns(2),

            Section::make('Ubicación y Soporte')
                ->schema([
                    Textarea::make('direccion')->label('Dirección o Lugar'),
                    KeyValue::make('ubicacion_gps')
                        ->label('Ubicación GPS (latitud / longitud)')
                        ->keyLabel('Campo')
                        ->valueLabel('Valor')
                        ->addButtonLabel('Agregar Coordenada')
                        ->reorderable(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->sortable()->label('Código'),
                TextColumn::make('descripcion')->searchable()->label('Descripción'),
                TextColumn::make('cliente.razon_social')->label('Cliente'),
                TextColumn::make('empresa.razon_social')->label('Empresa'),
                TextColumn::make('marca'),
                TextColumn::make('modelo'),
                IconColumn::make('activo')->boolean()->label('Activo'),
            ])
            ->filters([
                TernaryFilter::make('activo')->label('Activo'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipos::route('/'),
            'create' => Pages\CreateEquipo::route('/create'),
            'edit' => Pages\EditEquipo::route('/{record}/edit'),
        ];
    }
}