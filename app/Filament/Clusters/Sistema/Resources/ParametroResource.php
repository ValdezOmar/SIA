<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;
use App\Models\Sistema\Parametro;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ParametroResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Parametro::class;

    protected static ?string $cluster = Sistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?string $modelLabel = 'Parametros Generales';

    protected static ?string $pluralModelLabel = 'Parámetros generales';

    protected static ?int $navigationSort = -100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección de imágenes
                Section::make('Imágenes del sistema')
                    ->columns(3)
                    ->description('Actualice la identidad visual. Use imágenes ligeras de hasta 2 MB.')
                    ->schema([
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
                            ->default(fn () => file_exists(public_path('images/logo.png')) ? '/images/logo.png' : null),

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
                            ->default(fn () => file_exists(public_path('images/favicon.ico')) ? '/images/favicon.ico' : null),

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
                            ->default(fn () => file_exists(public_path('images/fondo.jpg')) ? '/images/fondo.jpg' : null),
                    ]),

                // Sección de configuración básica
                Section::make('Parámetros iniciales')
                    ->columns(3)
                    ->description('Defina la apariencia y la zona horaria que usarán todos los registros.')
                    ->schema([
                        ColorPicker::make('color_principal')
                            ->label('Color Principal')
                            ->required()
                            ->helperText('Color principal de botones y elementos destacados.'),

                        Select::make('timezone')
                            ->label('País / Zona Horaria')
                            ->options(function () {
                                $envTimezones = env('TIMEZONES');
                                $zones = $envTimezones ? explode(',', $envTimezones) : \DateTimeZone::listIdentifiers();

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
                            ->helperText('Afecta fechas, horas y cálculos de asistencia.'),

                        Toggle::make('login_nativo')
                            ->label('Permitir inicio de sesión con contraseña')
                            ->helperText('Mantenga activo al menos un método de inicio de sesión.')
                            ->reactive()
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                if (! $state && ! $get('google_activo')) {
                                    $set('login_nativo', true);
                                }
                            }),
                    ]),

                // Sección de integración con Google
                Section::make('Integración con Google')
                    ->description('Active solo si la organización usa cuentas corporativas de Google.')
                    ->collapsible()
                    ->columns(1)
                    ->schema([
                        Toggle::make('google_activo')
                            ->label('Permitir inicio de sesión con Google')
                            ->reactive()
                            ->required(fn ($get) => ! $get('login_nativo')),

                        TextInput::make('google_client_id')
                            ->label('Client ID')
                            ->helperText('Obtenga este valor desde Google Cloud Console.')
                            ->disabled(fn ($get) => ! $get('google_activo')) // deshabilitado si google_activo es false
                            ->required(fn ($get) => $get('google_activo'))
                            ->placeholder('Ej: 1234567890.apps.googleusercontent.com'),

                        TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->helperText('Manténgalo confidencial.')
                            ->password()
                            ->disabled(fn ($get) => ! $get('google_activo')) // deshabilitado si google_activo es false
                            ->required(fn ($get) => $get('google_activo')),

                        TextInput::make('google_redirect_uri')
                            ->label('Redirect URI')
                            ->helperText('Debe coincidir exactamente con la URL registrada en Google.')
                            ->disabled(fn ($get) => ! $get('google_activo')) // deshabilitado si google_activo es false
                            ->required(fn ($get) => $get('google_activo'))
                            ->placeholder('Ej: https://midominio.com/auth/google/callback'),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                ColorColumn::make('color_principal')->label('Color Principal'),
                IconColumn::make('google_activo')->boolean()->label('Google Login'),
                IconColumn::make('login_nativo')->boolean()->label('Login Nativo'),
                TextColumn::make('timezone')->label('Zona Horaria'),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make()->label('Configurar')->tooltip('Editar parámetros generales'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListParametros::route('/'),
            'edit' => Pages\EditParametro::route('/{record}/edit'),
        ];
    }
}
