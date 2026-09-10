<?php

namespace App\Filament\Clusters\Sistema\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages\ListSucursals;
use App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages\CreateSucursal;
use App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages\EditSucursal;
use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\SucursalResource\Pages;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SucursalResource extends Resource
{
    protected static ?string $model = Sucursal::class;

    protected static ?string $cluster = Sistema::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $modelLabel = 'Sucursal';

    protected static ?string $pluralModelLabel = 'Sucursales';

    protected static ?string $navigationLabel = 'Sucursales';

    protected static string | \UnitEnum | null $navigationGroup = 'Estructura empresarial';

    protected static ?int $navigationSort = 2;

    // Las sucursales se administran desde la empresa para evitar duplicar el flujo.
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->description('Datos principales de la sucursal.')
                    ->schema([
                        Select::make('empresa_id')
                            ->label('Empresa')
                            ->relationship('empresa', 'nombre_comercial')
                            ->getOptionLabelFromRecordUsing(fn (Empresa $record): string => $record->nombre_comercial ?: $record->razon_social)
                            ->required()
                            ->searchable(['nombre_comercial', 'razon_social', 'nit'])
                            ->preload()
                            ->placeholder('Busque por nombre comercial, razón social o NIT')
                            ->helperText('La sucursal quedará disponible únicamente para la empresa seleccionada.'),

                        TextInput::make('nombre')
                            ->label('Nombre de la sucursal')
                            ->required()
                            ->maxLength(150)
                            ->placeholder('Ej: Sucursal Central La Paz')
                            ->helperText('Use una ciudad o referencia que facilite identificarla.'),
                    ])
                    ->columns(2),

                Section::make('Ubicación y Contacto')
                    ->description('Dirección y datos de contacto de la sucursal.')
                    ->schema([
                        Textarea::make('direccion')
                            ->label('Dirección')
                            ->rows(2)
                            ->placeholder('Ej: Av. Mariscal Santa Cruz #123')
                            ->helperText('Incluya calle, número y referencias útiles.'),

                        TextInput::make('ciudad')
                            ->label('Ciudad')
                            ->maxLength(150)
                            ->placeholder('Ej: La Paz')
                            ->helperText('Ciudad donde opera la sucursal.'),

                        TextInput::make('pais')
                            ->label('País')
                            ->maxLength(100)
                            ->default('Bolivia')
                            ->placeholder('Ej: Bolivia')
                            ->helperText('País de ubicación.'),

                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->maxLength(50)
                            ->placeholder('Ej: (2) 2456789')
                            ->helperText('Teléfono de contacto de la sucursal.'),
                    ])
                    ->columns(2),

                Section::make('Configuración')
                    ->description('Estado de la sucursal en el sistema.')
                    ->schema([
                        Toggle::make('activo')
                            ->label('Sucursal activa')
                            ->default(true)
                            ->helperText('Desactive si ya no opera; conservará su historial.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('empresa.nombre_comercial')
                    ->label('Empresa')
                    ->placeholder('Sin nombre comercial')
                    ->description(fn (Sucursal $record): string => $record->empresa?->razon_social ?? 'Empresa no disponible')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('nombre')
                    ->label('Nombre de sucursal')
                    ->sortable()
                    ->searchable()
                    ->description(fn (Sucursal $record): string => $record->direccion ?: 'Sin dirección registrada'),

                TextColumn::make('ciudad')
                    ->label('Ciudad')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('telefono')
                    ->label('Teléfono'),

                IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('empresa_id')
                    ->label('Empresa')
                    ->relationship('empresa', 'nombre_comercial')
                    ->getOptionLabelFromRecordUsing(fn (Empresa $record): string => $record->nombre_comercial ?: $record->razon_social)
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('activo')
                    ->label('Activa')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas')
                    ->placeholder('Todas'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar'),

                DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas'),
                ]),
            ])
            ->defaultSort('nombre')
            ->emptyStateHeading('Aún no hay sucursales')
            ->emptyStateDescription('Registre una sucursal para asignar empleados y operaciones.')
            ->emptyStateIcon('heroicon-o-building-storefront');
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

    public static function getPages(): array
    {
        return [
            'index' => ListSucursals::route('/'),
            'create' => CreateSucursal::route('/create'),
            'edit' => EditSucursal::route('/{record}/edit'),
        ];
    }
}
