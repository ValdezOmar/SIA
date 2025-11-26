<?php

namespace App\Filament\Resources\Comercial;

use App\Filament\Resources\Comercial\CatalogoResource\Pages;
use App\Models\Comercial\Catalogo;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CatalogoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Catalogo::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    protected static ?string $navigationGroup = 'Comercial';
    protected static ?string $navigationLabel = 'Catálogo';
    protected static ?string $modelLabel = 'Catálogo';
    protected static ?string $pluralModelLabel = 'Catálogo  de productos';

    public static function form(Form $form): Form
    {
        return $form->schema(function (Form $form) {

            //SI NO ESTÁ EN MODO VISTA (crear o editar), muestra el formulario completo
            return [
                Section::make('Información del Catálogo')
                    ->schema([
                        FileUpload::make('foto_catalogo')
                            ->label('')
                            ->image()
                            ->directory('catalogos')
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
                            ->default(fn($record) => $record?->foto_catalogo ?? 'images/default-product.jpg')
                            ->alignCenter()
                            ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, Get $get): string {
                                $codigo = $get('codigo_articulo')
                                    ? preg_replace('/[^a-zA-Z0-9]/', '_', $get('codigo_articulo'))
                                    : 'catalogo_' . uniqid();
                                return $codigo . '.' . $file->getClientOriginalExtension();
                            })
                            ->imagePreviewHeight('600'),

                        Section::make('Información del Artículo')
                            ->description('Complete cuidadosamente los datos del producto antes de guardar.')
                            ->icon('heroicon-o-archive-box-x-mark')
                            ->collapsible()
                            ->schema([
                                Toggle::make('activo')
                                    ->label('Activo')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false)
                                    ->helperText('Indica si el artículo está disponible en el catálogo.')
                                    ->extraAttributes(['class' => 'mt-4']),

                                Grid::make(3)->schema([
                                    TextInput::make('codigo_articulo')
                                        ->disabled()
                                        ->label('Código')
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(100)
                                        ->prefixIcon('heroicon-o-archive-box-x-mark')
                                        ->placeholder('Ej: PROD-001')
                                        ->hint('Código único del artículo')
                                        ->hintIcon('heroicon-o-archive-box-x-mark'),

                                    Select::make('categoria')
                                        ->label('Categoría')
                                        ->options([
                                            'Accesorios de centrifugado' => 'Accesorios de centrifugado',
                                            'Accesorios para extracción de muestras' => 'Accesorios para extracción de muestras',
                                            'Accesorios para microscopía' => 'Accesorios para microscopía',
                                            'Accesorios para PCR / qPCR' => 'Accesorios para PCR / qPCR',
                                            'Accesorios para pipetas' => 'Accesorios para pipetas',
                                            'Aerosoles y desinfectantes' => 'Aerosoles y desinfectantes',
                                            'Agujas de seguridad / lancetas' => 'Agujas de seguridad / lancetas',
                                            'Agujas Vacutainer' => 'Agujas Vacutainer',
                                            'Almacenamiento criogénico' => 'Almacenamiento criogénico',
                                            'Análisis bioquímico' => 'Análisis bioquímico',
                                            'Análisis clínico – hematología' => 'Análisis clínico – hematología',
                                            'Análisis clínico – inmunología' => 'Análisis clínico – inmunología',
                                            'Análisis clínico – microbiología' => 'Análisis clínico – microbiología',
                                            'Análisis clínico – química clínica' => 'Análisis clínico – química clínica',
                                            'Artículos de seguridad y protección' => 'Artículos de seguridad y protección',
                                            'Biobancos / preservación de muestras' => 'Biobancos / preservación de muestras',
                                            'Blísteres / placas de reacción' => 'Blísteres / placas de reacción',
                                            'Calibradores y estándares' => 'Calibradores y estándares',
                                            'Consumibles de laboratorio' => 'Consumibles de laboratorio',
                                            'Consumibles esterilizados' => 'Consumibles esterilizados',
                                            'Consumibles generales de laboratorio' => 'Consumibles generales de laboratorio',
                                            'Control de calidad' => 'Control de calidad',
                                            'Crio-plásticos de laboratorio' => 'Crio-plásticos de laboratorio',
                                            'Cubetas y celdas' => 'Cubetas y celdas',
                                            'Dispositivos de muestreo clínico' => 'Dispositivos de muestreo clínico',
                                            'Equipamiento de laboratorio' => 'Equipamiento de laboratorio',
                                            'Equipos' => 'Equipos',
                                            'Filtros y material de filtración' => 'Filtros y material de filtración',
                                            'Gradillas / Rack' => 'Gradillas / Rack',
                                            'Herramientas de laboratorio' => 'Herramientas de laboratorio',
                                            'Kits de pruebas rápidas (test rápidos)' => 'Kits de pruebas rápidas (test rápidos)',
                                            'Material de vidrio de laboratorio' => 'Material de vidrio de laboratorio',
                                            'Material desechable médico' => 'Material desechable médico',
                                            'Medios de cultivo' => 'Medios de cultivo',
                                            'Microplacas (plates)' => 'Microplacas (plates)',
                                            'Micropipetas' => 'Micropipetas',
                                            'Microtainer' => 'Microtainer',
                                            'Microtubos / Eppendorf' => 'Microtubos / Eppendorf',
                                            'Partes y piezas de equipos' => 'Partes y piezas de equipos',
                                            'Pipetas Pasteur' => 'Pipetas Pasteur',
                                            'Placas de cultivo / placas de células' => 'Placas de cultivo / placas de células',
                                            'Plásticos de laboratorio desechables' => 'Plásticos de laboratorio desechables',
                                            'Portapuntas / Holder' => 'Portapuntas / Holder',
                                            'Productos para diagnóstico in vitro (IVD)' => 'Productos para diagnóstico in vitro (IVD)',
                                            'Productos para microscopía' => 'Productos para microscopía',
                                            'Productos para reactivos especializados' => 'Productos para reactivos especializados',
                                            'Puntas de pipeta (Tips)' => 'Puntas de pipeta (Tips)',
                                            'Reactivo médico' => 'Reactivo médico',
                                            'Reactivos para diagnóstico molecular' => 'Reactivos para diagnóstico molecular',
                                            'Reactivos para inmunología' => 'Reactivos para inmunología',
                                            'Reactivos para microbiología' => 'Reactivos para microbiología',
                                            'Reactivos para química clínica' => 'Reactivos para química clínica',
                                            'Reactivos y soluciones estándar' => 'Reactivos y soluciones estándar',
                                            'Seguridad biológica / descontaminación' => 'Seguridad biológica / descontaminación',
                                            'Soportes de micropipetas' => 'Soportes de micropipetas',
                                            'Tubos de centrifuga' => 'Tubos de centrifuga',
                                            'Tubos de extracción de sangre (Vacutainer)' => 'Tubos de extracción de sangre (Vacutainer)',
                                            'Tubos criogénicos' => 'Tubos criogénicos',
                                            'Tubos graduados y de medida' => 'Tubos graduados y de medida',
                                            'Utillaje para laboratorio' => 'Utillaje para laboratorio',
                                            'Vidriería especial' => 'Vidriería especial',
                                        ])
                                        ->placeholder('Seleccione una categoría')
                                        ->searchable()
                                        ->required(),

                                    TextInput::make('stock_minimo')
                                        ->label('Stock mínimo')
                                        ->numeric()
                                        ->prefixIcon('heroicon-o-archive-box-x-mark')
                                        ->placeholder('Ej: 10')
                                        ->suffix('unidades'),
                                ]),

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
                                    ->placeholder('Escriba una descripción detallada del producto...')
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
                    ])
                    ->columnSpanFull(),

                View::make('filament.forms.components.product-card')
                    ->label('Vista previa del producto')
                    ->viewData([
                        'record' => fn() => request()->route('record')
                            ? Catalogo::find(request()->route('record'))
                            : null,
                    ])
                    ->columnSpanFull(),
            ];
        });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('foto_catalogo')
                    ->label('Foto')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $path = $record->foto_catalogo ?? null;
                        $url = $path
                            ? Storage::disk('public')->url($path)
                            : asset('images/default-product.jpg');

                        return <<<HTML
                        <div class="flex items-center justify-center">
                            <img 
                                src="{$url}" 
                                alt="" 
                                class="w-32 h-32 sm:w-36 sm:h-36 md:w-40 md:h-40 lg:w-44 lg:h-44 object-cover rounded-md border border-gray-200 dark:border-gray-700 shadow-sm" 
                            />
                        </div>
                    HTML;
                    })
                    ->searchable(false)
                    ->sortable(false),

                TextColumn::make('codigo_articulo')
                    ->label('Item Almacén')
                    ->html()
                    ->getStateUsing(function ($record) {
                        $articulo = DB::table('alm_articulos')
                            ->where('codigo', $record->codigo_articulo)
                            ->first();

                        if (!$articulo) {
                            return "<div><strong class='text-gray-500 dark:text-gray-400'>No encontrado</strong></div>";
                        }

                        return "
                        <div class='text-gray-800 dark:text-gray-100'>
                            <strong>{$articulo->descripcion}</strong><br>
                            <small>
                                Código: <span class='text-blue-600 dark:text-blue-400 font-semibold text-xs'>{$articulo->codigo}</span><br>
                                Cod. Alterno: <span class='text-sm'>{$articulo->codigo_alterno}</span>
                            </small>
                        </div>
                    ";
                    })
                    ->wrap()
                    ->searchable(['descripcion', 'codigo_articulo']),

                TextColumn::make('categoria')
                    ->label('Categoría')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('stock_minimo')
                    ->label('Stock Mínimo')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color(fn(string $state): string => match (true) {
                        $state <= 5 => 'danger',
                        $state <= 20 => 'warning',
                        default => 'success',
                    }),

                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')->label('Estado'),
            ])
            ->headerActions([
                Action::make('Generar catalogo')
                    ->color('success')
                    ->icon('heroicon-o-plus')
                    ->requiresConfirmation()
                    ->visible(fn() => Auth::user()?->can('generar_catalogo_comercial::catalogo'))
                    ->action(function () {
                        // Ejecutar la SQL y capturar cuántos registros fueron insertados
                        $count = DB::affectingStatement("
            INSERT INTO com_catalogo (codigo_articulo, descripcion, created_at, updated_at)
            SELECT DISTINCT a.codigo, NULL, NOW(), NOW()
            FROM alm_articulos a
            LEFT JOIN com_catalogo c ON c.codigo_articulo = a.codigo
            WHERE c.codigo_articulo IS NULL
              AND a.codigo IS NOT NULL
              AND a.codigo <> ''
              AND a.saldo_actual > 0
        ");

                        // Notificación solo con los nuevos registros
                        if ($count > 0) {
                            Notification::make()
                                ->title('Operación exitosa')
                                ->body("{$count} códigos de catálogo fueron creados correctamente.")
                                ->success()
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Sin cambios')
                                ->body('No se crearon nuevos códigos, todos ya existen en el catálogo.')
                                ->info()
                                ->send();
                        }

                        return $count;
                    }),

            ])
            ->actions([
                Action::make('ver_producto')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->modalHeading(fn($record) => "Vista del producto: {$record->codigo_articulo}")
                    ->modalContent(fn($record) => view('filament.forms.components.product-card', [
                        'record' => $record,
                    ]))
                    ->button(),
                EditAction::make()
            ])
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(50) //Filas mostradas
            ->striped();                      //Filas con fondo alternado;
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales
            //'view',
            //'create',
            'update',
            //'delete',
            //permisos personalizados: 
            'generar_catalogo'
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCatalogos::route('/'),
            'create' => Pages\CreateCatalogo::route('/create'),
            'edit' => Pages\EditCatalogo::route('/{record}/edit'),
        ];
    }
}