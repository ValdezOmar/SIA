<?php

namespace App\Filament\Clusters\Sistema\Resources;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages\ListHorarioAsistencias;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages\CreateHorarioAsistencia;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages\EditHorarioAsistencia;
use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\RelationManagers\AsignacionesRelationManager;
use App\Models\RRHH\HorarioAsistencia;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HorarioAsistenciaResource extends Resource
{
    protected static ?string $model = HorarioAsistencia::class;

    protected static ?string $cluster = Sistema::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Horarios de asistencia';

    protected static string | \UnitEnum | null $navigationGroup = 'Personal y asistencia';

    protected static ?string $modelLabel = 'Horario de asistencia';

    protected static ?string $pluralModelLabel = 'Horarios de asistencia';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificación del turno')
                ->icon('heroicon-o-identification')
                ->columnSpanFull()
                ->description('Defina un turno reutilizable y asígnelo a los empleados desde la pestaña Asignaciones.')
                ->schema([
                    TextInput::make('nombre')
                        ->label('Nombre del turno')
                        ->placeholder('Ej. Administrativo')
                        ->required()
                        ->maxLength(100),
                    TextInput::make('codigo')
                        ->label('Código')
                        ->placeholder('Ej. ADMINISTRATIVO')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(50)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase;']),
                    CheckboxList::make('dias_laborales')
                        ->label('Días laborables')
                        ->options([
                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
                            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
                        ])
                        ->columns(['default' => 2, 'md' => 4, 'xl' => 7])
                        ->required()
                        ->minItems(1)
                        ->default([1, 2, 3, 4, 5])
                        ->helperText('Seleccione los días en que este turno espera una marcación de entrada.')
                        ->columnSpanFull(),
                ])->columns(['default' => 1, 'lg' => 2]),
            Section::make('Marcaciones esperadas')
                ->icon('heroicon-o-clock')
                ->columnSpanFull()
                ->description('Las horas se usan para calcular puntualidad. La primera marcación del día se contrasta con la entrada y su tolerancia.')
                ->schema([
                    TimePicker::make('hora_entrada')->label('Entrada')->seconds(false)->required(),
                    TextInput::make('tolerancia_minutos')->label('Tolerancia (minutos)')->numeric()->minValue(0)->maxValue(240)->default(0)->required()->helperText('Minutos permitidos antes de registrar retraso.'),
                    TimePicker::make('hora_omision')->label('Desde qué hora es omisión')->seconds(false)->helperText('Opcional. Después de esta hora la primera marcación se clasifica como omisión.'),
                    TimePicker::make('hora_inicio_almuerzo')->label('Inicio de almuerzo')->seconds(false),
                    TimePicker::make('hora_fin_almuerzo')->label('Fin de almuerzo')->seconds(false),
                    TimePicker::make('hora_salida')->label('Salida')->seconds(false),
                    Toggle::make('requiere_marcacion_almuerzo')->label('Exigir marcación de almuerzo')->helperText('Úselo cuando el personal debe marcar salida y retorno de almuerzo.'),
                ])->columns(['default' => 1, 'md' => 2, 'xl' => 3]),
            Section::make('Estado')
                ->icon('heroicon-o-adjustments-horizontal')
                ->columnSpanFull()
                ->schema([
                    Toggle::make('activo')->label('Turno activo')->default(true),
                    Toggle::make('predeterminado')->label('Usar cuando un empleado no tiene un turno asignado')->helperText('Solo puede existir un turno predeterminado; los turnos específicos siempre tienen prioridad.')->default(false),
                    Textarea::make('observaciones')->label('Observaciones')->maxLength(1000)->columnSpanFull(),
                ])->columns(['default' => 1, 'lg' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Turno')->searchable()->sortable()->description(fn (HorarioAsistencia $record): string => $record->codigo),
                TextColumn::make('hora_entrada')->label('Entrada')->time('H:i'),
                TextColumn::make('hora_inicio_almuerzo')->label('Almuerzo')->formatStateUsing(fn ($state, HorarioAsistencia $record): string => $state && $record->hora_fin_almuerzo ? substr($state, 0, 5).' – '.substr($record->hora_fin_almuerzo, 0, 5) : 'No definido'),
                TextColumn::make('hora_salida')->label('Salida')->time('H:i')->placeholder('No definida'),
                IconColumn::make('predeterminado')->label('Predeterminado')->boolean(),
                IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->filters([
                TernaryFilter::make('activo')->label('Activo'),
            ])
            ->recordActions([
                EditAction::make()->label('Editar')->tooltip('Editar turno y sus asignaciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('nombre')
            ->emptyStateHeading('Aún no hay turnos')
            ->emptyStateDescription('Cree un turno y luego asígnelo a los empleados que corresponda.')
            ->emptyStateIcon('heroicon-o-clock');
    }

    public static function getRelations(): array
    {
        return [AsignacionesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHorarioAsistencias::route('/'),
            'create' => CreateHorarioAsistencia::route('/create'),
            'edit' => EditHorarioAsistencia::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return ['view_any', 'view', 'create', 'update', 'delete'];
    }
}
