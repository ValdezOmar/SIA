<?php

namespace App\Filament\Clusters\Sistema\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use DateTimeZone;
use Filament\Actions\EditAction;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages\ListParametros;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages\EditParametro;
use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;
use App\Models\Sistema\Parametro;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ParametroResource extends Resource
{
    protected static ?string $model = Parametro::class;

    protected static ?string $cluster = Sistema::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $modelLabel = 'Parametros Generales';

    protected static ?string $pluralModelLabel = 'Parámetros generales';

    protected static string | \UnitEnum | null $navigationGroup = 'Configuración general';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Sección de imágenes
                Section::make('Identidad visual')
                    ->icon('heroicon-o-photo')
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->columnSpanFull()
                    ->description('Actualice la identidad visual. Use imágenes ligeras de hasta 2 MB.')
                    ->schema([
                        Placeholder::make('imagenes_actuales')
                            ->label('Vista previa actual')
                            ->content(fn (): HtmlString => new HtmlString(self::getCurrentImagesPreview()))
                            ->helperText('Estas son las imágenes que el sistema utiliza actualmente.')
                            ->columnSpanFull(),

                        FileUpload::make('logo_path')
                            ->label('Logo Principal')
                            ->helperText('PNG o SVG. Se mostrará en la barra lateral.')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml'])
                            ->maxSize(2048)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                $destination = public_path('images/logo.png');
                                File::ensureDirectoryExists(public_path('images'));
                                File::copy($file->getRealPath(), $destination);

                                return '/images/logo.png';
                            })
                            ->default(fn () => file_exists(public_path('images/logo.png')) ? '/images/logo.png' : null)
                            ->columnSpan(1),

                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->helperText('ICO o PNG. Se muestra en la pestaña del navegador.')
                            ->image()
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'])
                            ->maxSize(2048)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                $destination = public_path('images/favicon.ico');
                                File::ensureDirectoryExists(public_path('images'));
                                File::copy($file->getRealPath(), $destination);

                                return '/images/favicon.ico';
                            })
                            ->default(fn () => file_exists(public_path('images/favicon.ico')) ? '/images/favicon.ico' : null)
                            ->columnSpan(1),

                        FileUpload::make('fondo_path')
                            ->label('Fondo de Login')
                            ->helperText('PNG o JPG. Se utiliza en la pantalla de inicio de sesión.')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                $destination = public_path('images/fondo.jpg');
                                File::ensureDirectoryExists(public_path('images'));
                                File::copy($file->getRealPath(), $destination);

                                return '/images/fondo.jpg';
                            })
                            ->default(fn () => file_exists(public_path('images/fondo.jpg')) ? '/images/fondo.jpg' : null)
                            ->columnSpan(1),
                    ]),

                // Sección de configuración básica
                Section::make('Apariencia y operación')
                    ->icon('heroicon-o-swatch')
                    ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                    ->columnSpanFull()
                    ->description('Defina la apariencia y la zona horaria que usarán todos los registros.')
                    ->schema([
                        ColorPicker::make('color_principal')
                            ->label('Color Principal')
                            ->required()
                            ->live()
                            ->helperText('Color principal de botones y elementos destacados.'),
                        ColorPicker::make('color_secundario')
                            ->label('Color Secundario')
                            ->default('#3066BE')
                            ->live()
                            ->helperText('Color complementario para acentos, enlaces y estados activos.'),

                        Select::make('escala_interfaz')
                            ->label('Escala de interfaz')
                            ->options([
                                '88%' => 'Compacta (DashStack original)',
                                '94%' => 'Estándar',
                                '100%' => 'Amplia',
                            ])
                            ->default('88%')
                            ->required()
                            ->live()
                            ->helperText('Define el tamaño general de textos y controles del panel.'),

                        Select::make('estilo_login')
                            ->label('Estilo de acceso')
                            ->options([
                                'cristal' => 'Cristal translúcido (DashStack original)',
                                'solido' => 'Tarjeta sólida',
                            ])
                            ->default('cristal')
                            ->required()
                            ->live()
                            ->helperText('La imagen de fondo se configura en la sección superior.'),

                        Select::make('timezone')
                            ->label('País / Zona Horaria')
                            ->options(function () {
                                $envTimezones = env('TIMEZONES');
                                $zones = $envTimezones ? explode(',', $envTimezones) : DateTimeZone::listIdentifiers();

                                $options = [];
                                foreach ($zones as $tz) {
                                    $parts = explode('/', $tz);
                                    $country = $parts[0];
                                    $city = $parts[1] ?? '';
                                    $options[$tz] = $country.($city ? " ($city)" : '');
                                }

                                return $options;
                            })
                            ->searchable()
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Afecta fechas, horas y cálculos de asistencia.'),

                        Toggle::make('login_nativo')
                            ->label('Permitir inicio de sesión con contraseña')
                            ->helperText('Mantenga activo al menos un método de inicio de sesión.')
                            ->reactive()
                            ->live()
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                if (! $state && ! $get('google_activo')) {
                                    $set('login_nativo', true);
                                }
                            })
                            ->columnSpan(['default' => 1, 'md' => 2, 'xl' => 3]),
                    ]),

                // Sección de integración con Google
                Section::make('Acceso con Google')
                    ->icon('heroicon-o-key')
                    ->description('Active solo si la organización usa cuentas corporativas de Google.')
                    ->collapsible()
                    ->columns(1)
                    ->columnSpanFull()
                    ->schema([
                        Toggle::make('google_activo')
                            ->label('Permitir inicio de sesión con Google')
                            ->reactive()
                            ->live()
                            ->columnSpanFull()
                            ->required(fn ($get) => ! $get('login_nativo')),

                        TextInput::make('google_client_id')
                            ->label('Client ID')
                            ->helperText('Obtenga este valor desde Google Cloud Console.')
                            ->visible(fn ($get) => (bool) $get('google_activo'))
                            ->required(fn ($get) => $get('google_activo'))
                            ->placeholder('Ej: 1234567890.apps.googleusercontent.com'),

                        TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->helperText('Manténgalo confidencial.')
                            ->password()
                            ->visible(fn ($get) => (bool) $get('google_activo'))
                            ->required(fn ($get) => $get('google_activo')),

                        TextInput::make('google_redirect_uri')
                            ->label('Redirect URI')
                            ->helperText('Debe coincidir exactamente con la URL registrada en Google.')
                            ->visible(fn ($get) => (bool) $get('google_activo'))
                            ->required(fn ($get) => $get('google_activo'))
                            ->placeholder('Ej: https://midominio.com/auth/google/callback')
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                ColorColumn::make('color_principal')->label('Color Principal'),
                ColorColumn::make('color_secundario')->label('Color Secundario'),
                IconColumn::make('google_activo')->boolean()->label('Google Login'),
                IconColumn::make('login_nativo')->boolean()->label('Login Nativo'),
                TextColumn::make('timezone')->label('Zona Horaria'),
            ])
            ->recordActions([
                EditAction::make()->label('Configurar')->tooltip('Editar parámetros generales'),
            ])
            ->paginated(false)
            ->emptyStateHeading('Configure los parámetros generales')
            ->emptyStateDescription('Abra la configuración para definir la imagen, zona horaria y acceso al sistema.')
            ->emptyStateIcon('heroicon-o-computer-desktop');
    }

    // Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any', // Mostrar en menú
            'view', // Ver registro
            'update', // Actualizar registro
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    /**
     * Construye una vista previa usando directamente los archivos públicos.
     */
    private static function getCurrentImagesPreview(): string
    {
        $imagenes = [
            [
                'titulo' => 'Logo principal',
                'archivo' => public_path('images/logo.png'),
                'url' => asset('images/logo.png'),
                'clase' => 'max-height:4.5rem;max-width:100%;object-fit:contain',
            ],
            [
                'titulo' => 'Favicon',
                'archivo' => public_path('images/favicon.ico'),
                'url' => asset('images/favicon.ico'),
                'clase' => 'width:3.75rem;height:3.75rem;object-fit:contain',
            ],
            [
                'titulo' => 'Fondo de inicio de sesión',
                'archivo' => public_path('images/fondo.jpg'),
                'url' => asset('images/fondo.jpg'),
                'clase' => 'width:100%;height:9rem;object-fit:cover',
            ],
        ];

        $contenido = collect($imagenes)
            ->map(function (array $imagen): string {
                $titulo = e($imagen['titulo']);

                if (! file_exists($imagen['archivo'])) {
                    return <<<HTML
                        <div style="display:grid;place-items:center;min-height:12rem;padding:1rem;border:1px dashed #cbd5e1;border-radius:.75rem;background:#f8fafc;text-align:center">
                            <p style="margin:0;color:#334155;font-size:.82rem;font-weight:750">{$titulo}</p>
                            <p style="margin:.45rem 0 0;color:#64748b;font-size:.75rem">No hay una imagen cargada</p>
                        </div>
                    HTML;
                }

                $url = e($imagen['url'].'?v='.filemtime($imagen['archivo']));
                $clase = e($imagen['clase']);

                return <<<HTML
                    <div style="min-width:0;padding:1rem;border:1px solid #e2e8f0;border-radius:.75rem;background:#fff">
                        <p style="margin:0 0 .65rem;color:#334155;font-size:.8rem;font-weight:750">{$titulo}</p>
                        <div style="display:flex;align-items:center;justify-content:center;min-height:10rem;overflow:hidden;border-radius:.55rem;background:#f8fafc;padding:.5rem">
                            <img src="{$url}" alt="{$titulo}" style="{$clase}">
                        </div>
                    </div>
                HTML;
            })
            ->implode('');

        return '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(12rem,1fr));gap:.9rem">'.$contenido.'</div>';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListParametros::route('/'),
            'edit' => EditParametro::route('/{record}/edit'),
        ];
    }
}
