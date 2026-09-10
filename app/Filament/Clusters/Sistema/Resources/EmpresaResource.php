<?php

namespace App\Filament\Clusters\Sistema\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\Pages\ListEmpresas;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\Pages\CreateEmpresa;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\Pages\EditEmpresa;
use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\Pages;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\RelationManagers\AreasRelationManager;
use App\Filament\Clusters\Sistema\Resources\EmpresaResource\RelationManagers\SucursalesRelationManager;
use App\Models\Sistema\Empresa;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmpresaResource extends Resource
{
    protected static ?string $model = Empresa::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $pluralModelLabel = 'Configuración de empresa';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $navigationLabel = 'Empresas';

    protected static string | \UnitEnum | null $navigationGroup = 'Estructura empresarial';

    protected static ?string $cluster = Sistema::class;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificación')
                    ->icon('heroicon-o-building-office-2')
                    ->description('Registre los nombres legal y comercial que identifican a la empresa.')
                    ->schema([
                        TextInput::make('razon_social')
                            ->label('Razón Social')
                            ->placeholder('Ej. Novanexa S.R.L.')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nombre legal usado en documentos y reportes.'),

                        TextInput::make('nombre_comercial')
                            ->label('Nombre Comercial')
                            ->placeholder('Ej. Novanexa')
                            ->maxLength(255)
                            ->helperText('Nombre visible para clientes y personal.'),

                        TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(50)
                            ->helperText('Número de identificación tributaria de la empresa.'),

                        FileUpload::make('logo_path')
                            ->label('Logo de la empresa')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                            ->disk('public')
                            ->directory('empresas/logos')
                            ->maxSize(2048)
                            ->helperText('PNG o JPG, máximo 2 MB. Se utiliza en los reportes PDF de inventario.'),

                        TextInput::make('nro_matricula')
                            ->label('Nro. Matrícula')
                            ->maxLength(50)
                            ->helperText('Número de matrícula de comercio, si corresponde.'),

                        Placeholder::make('estructura_organizacional')
                            ->label('Estructura organizacional')
                            ->content('Después de guardar, use las pestañas Sucursales y Áreas para completar la estructura de esta empresa.')
                            ->helperText('Las áreas contienen los cargos; las sucursales representan las ubicaciones de la empresa.')
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'lg' => 2]),

                Section::make('Datos de Contacto')
                    ->icon('heroicon-o-phone')
                    ->description('Incluya solo los canales de contacto vigentes.')
                    ->schema([
                        Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2)
                            ->helperText('Dirección legal o principal de la empresa.'),

                        TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->maxLength(150)
                            ->helperText('Ciudad de la oficina principal.'),

                        TextInput::make('pais')
                            ->label('País')
                            ->default('Bolivia')
                            ->maxLength(100)
                            ->helperText('País donde está registrada la empresa.'),

                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->maxLength(50)
                            ->helperText('Número fijo o central telefónica.'),

                        TextInput::make('celular')
                            ->label('Celular')
                            ->maxLength(50)
                            ->helperText('Número móvil principal de contacto.'),

                        TextInput::make('email')
                            ->label('Email')
                            ->placeholder('contacto@empresa.com')
                            ->email()
                            ->maxLength(150)
                            ->helperText('Correo general utilizado para comunicaciones empresariales.'),

                        TextInput::make('sitio_web')
                            ->label('Sitio Web')
                            ->placeholder('https://empresa.com')
                            ->url()
                            ->maxLength(150)
                            ->helperText('Dirección web pública de la empresa.'),
                        TextInput::make('seguro_medico')
                            ->label('Caja de Salud')
                            ->helperText('Entidad de salud que corresponde a sus empleados.')
                            ->hintIcon('heroicon-o-heart'),
                    ])
                    ->columns(['default' => 1, 'lg' => 2]),

                Section::make('Estado')
                    ->icon('heroicon-o-check-circle')
                    ->description('Desactive una empresa que ya no opera para evitar nuevas asignaciones.')
                    ->schema([
                        Toggle::make('empresa_activo')
                            ->label('Empresa activa')
                            ->default(true),
                    ])
                    ->columns(['default' => 1, 'lg' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre_comercial')
                    ->label('Empresa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->placeholder('Sin nombre comercial')
                    ->description(fn (Empresa $record): string => $record->razon_social),

                TextColumn::make('sucursales_count')
                    ->counts('sucursales')
                    ->label('Sucursales')
                    ->badge()
                    ->color('info'),

                TextColumn::make('areas_count')
                    ->counts('areas')
                    ->label('Áreas')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('nit')
                    ->label('NIT')
                    ->sortable(),

                TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->sortable(),

                IconColumn::make('empresa_activo')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('empresa_activo')
                    ->label('Activa'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Administrar')
                    ->tooltip('Editar la empresa y administrar sucursales y áreas'),
                DeleteAction::make()->label('Eliminar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nombre_comercial')
            ->emptyStateHeading('Aún no hay empresas')
            ->emptyStateDescription('Registre una empresa antes de crear sus sucursales.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }

    // Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any', // Mostrar en menú
            'view', // Ver registro
            'create', // Crear Registro
            'update', // Actualizar registro
            'delete', // Eliminar Registro
        ];
    }

    public static function getRelations(): array
    {
        return [
            SucursalesRelationManager::class,
            AreasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmpresas::route('/'),
            'create' => CreateEmpresa::route('/create'),
            'edit' => EditEmpresa::route('/{record}/edit'),
        ];
    }
}
