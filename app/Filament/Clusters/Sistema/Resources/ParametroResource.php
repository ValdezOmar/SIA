<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\ParametroResource\Pages;
use App\Models\Sistema\Parametro;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class ParametroResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Parametro::class;
    protected static ?string $cluster = Sistema::class;
    protected static ?string $navigationIcon = 'heroicon-o-computer-desktop';
    protected static ?string $modelLabel = 'Parametros Generales';
    protected static ?int $navigationSort = -100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Sección de imágenes
                Section::make('Imágenes del sistema')
                    ->columns(3)
                    ->description('Sube los logos y el fondo de login del sistema. Formatos recomendados y tamaño máximo 2MB.')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo Principal')
                            ->hint('Formatos aceptados: PNG, SVG.')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/svg+xml'])
                            ->maxSize(2048)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                $destination = public_path('images/logo.png');
                                File::ensureDirectoryExists(public_path('images'));
                                File::copy($file->getRealPath(), $destination);
                                return '/images/logo.png';
                            })
                            ->default(fn() => file_exists(public_path('images/logo.png')) ? '/images/logo.png' : null),

                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->hint('Formatos aceptados: .ICO o PNG.')
                            ->image()
                            ->acceptedFileTypes(['image/x-icon', 'image/vnd.microsoft.icon', 'image/png'])
                            ->maxSize(2048)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                $destination = public_path('images/favicon.ico');
                                File::ensureDirectoryExists(public_path('images'));
                                File::copy($file->getRealPath(), $destination);
                                return '/images/favicon.ico';
                            })
                            ->default(fn() => file_exists(public_path('images/favicon.ico')) ? '/images/favicon.ico' : null),

                        FileUpload::make('fondo_path')
                            ->label('Fondo de Login')
                            ->hint('Formatos aceptados: PNG o JPEG. ')
                            ->image()
                            ->acceptedFileTypes(['image/jpeg', 'image/png'])
                            ->maxSize(2048)
                            ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                                $destination = public_path('images/fondo.jpg');
                                File::ensureDirectoryExists(public_path('images'));
                                File::copy($file->getRealPath(), $destination);
                                return '/images/fondo.jpg';
                            })
                            ->default(fn() => file_exists(public_path('images/fondo.jpg')) ? '/images/fondo.jpg' : null),
                    ]),

                // Sección de configuración básica
                Section::make('Parámetros iniciales')
                    ->columns(3)
                    ->description('Configura los colores principales y la zona horaria del sistema.')
                    ->schema([
                        ColorPicker::make('color_principal')
                            ->label('Color Principal')
                            ->required()
                            ->helperText('Color principal que se aplicará en la interfaz del sistema.'),

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
                                    $options[$tz] = $country . ($city ? " ($city)" : '');
                                }
                                return $options;
                            })
                            ->searchable()
                            ->required()
                            ->helperText('Selecciona la zona horaria según el país para compatibilidad con el sistema.'),

                        Toggle::make('login_nativo')
                            ->label('Activar Login Nativo')
                            ->hint('¡Manipular con cuidado!')
                            ->reactive()
                            ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                if (!$state && !$get('google_activo')) {
                                    $set('login_nativo', true);
                                }
                            }),
                    ]),

                // Sección de integración con Google
                Section::make('Integración con Google')
                    ->description('Solo se recomienda activar Google Login con cuentas Google Corporativo.')
                    ->collapsible()
                    ->columns(1)
                    ->schema([
                        Toggle::make('google_activo')
                            ->label('Activar Google Login')
                            ->reactive()
                            ->required(fn($get) => !$get('login_nativo')),

                        TextInput::make('google_client_id')
                            ->label('Client ID')
                            ->disabled(fn($get) => !$get('google_activo')) // deshabilitado si google_activo es false
                            ->required(fn($get) => $get('google_activo'))
                            ->placeholder('Ej: 1234567890.apps.googleusercontent.com'),

                        TextInput::make('google_client_secret')
                            ->label('Client Secret')
                            ->password()
                            ->disabled(fn($get) => !$get('google_activo')) // deshabilitado si google_activo es false
                            ->required(fn($get) => $get('google_activo')),

                        TextInput::make('google_redirect_uri')
                            ->label('Redirect URI')
                            ->disabled(fn($get) => !$get('google_activo')) // deshabilitado si google_activo es false
                            ->required(fn($get) => $get('google_activo'))
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
                \Filament\Tables\Actions\ViewAction::make(),
            ])
            ->paginated(false);
    }
    //Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any', //Mostrar en menú
            'view', //Ver registro
            'update', //Actualizar registro            
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