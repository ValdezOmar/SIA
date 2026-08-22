<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\Pages;
use App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\RelationManagers\AsignacionesRelationManager;
use App\Models\RRHH\HorarioAsistencia;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HorarioAsistenciaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = HorarioAsistencia::class;

    protected static ?string $cluster = Sistema::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Horarios de asistencia';

    protected static ?string $modelLabel = 'Horario de asistencia';

    protected static ?string $pluralModelLabel = 'Horarios de asistencia';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identificación del turno')
                ->description('Defina un turno reutilizable y asígnelo a los empleados desde la pestaña Asignaciones.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del turno')
                        ->placeholder('Ej. Administrativo')
                        ->required()
                        ->maxLength(100),
                    Forms\Components\TextInput::make('codigo')
                        ->label('Código')
                        ->placeholder('Ej. ADMINISTRATIVO')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(50)
                        ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                        ->extraInputAttributes(['style' => 'text-transform: uppercase;']),
                    Forms\Components\CheckboxList::make('dias_laborales')
                        ->label('Días laborables')
                        ->options([
                            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
                            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
                        ])
                        ->columns(4)
                        ->required()
                        ->minItems(1)
                        ->default([1, 2, 3, 4, 5])
                        ->columnSpanFull(),
                ])->columns(2),
            Forms\Components\Section::make('Marcaciones esperadas')
                ->description('Las horas se usan para calcular puntualidad. La primera marcación del día se contrasta con la entrada y su tolerancia.')
                ->schema([
                    Forms\Components\TimePicker::make('hora_entrada')->label('Entrada')->seconds(false)->required(),
                    Forms\Components\TextInput::make('tolerancia_minutos')->label('Tolerancia (minutos)')->numeric()->minValue(0)->maxValue(240)->default(0)->required(),
                    Forms\Components\TimePicker::make('hora_omision')->label('Desde qué hora es omisión')->seconds(false)->helperText('Opcional. Después de esta hora la primera marcación se clasifica como omisión.'),
                    Forms\Components\TimePicker::make('hora_inicio_almuerzo')->label('Inicio de almuerzo')->seconds(false),
                    Forms\Components\TimePicker::make('hora_fin_almuerzo')->label('Fin de almuerzo')->seconds(false),
                    Forms\Components\TimePicker::make('hora_salida')->label('Salida')->seconds(false),
                    Forms\Components\Toggle::make('requiere_marcacion_almuerzo')->label('Exigir marcación de almuerzo'),
                ])->columns(3),
            Forms\Components\Section::make('Estado')
                ->schema([
                    Forms\Components\Toggle::make('activo')->label('Turno activo')->default(true),
                    Forms\Components\Toggle::make('predeterminado')->label('Usar cuando un empleado no tiene un turno asignado')->helperText('Solo puede existir un turno predeterminado.')->default(false),
                    Forms\Components\Textarea::make('observaciones')->label('Observaciones')->maxLength(1000)->columnSpanFull(),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->label('Turno')->searchable()->sortable()->description(fn (HorarioAsistencia $record): string => $record->codigo),
                Tables\Columns\TextColumn::make('hora_entrada')->label('Entrada')->time('H:i'),
                Tables\Columns\TextColumn::make('hora_inicio_almuerzo')->label('Almuerzo')->formatStateUsing(fn ($state, HorarioAsistencia $record): string => $state && $record->hora_fin_almuerzo ? substr($state, 0, 5).' – '.substr($record->hora_fin_almuerzo, 0, 5) : 'No definido'),
                Tables\Columns\TextColumn::make('hora_salida')->label('Salida')->time('H:i')->placeholder('No definida'),
                Tables\Columns\IconColumn::make('predeterminado')->label('Predeterminado')->boolean(),
                Tables\Columns\IconColumn::make('activo')->label('Activo')->boolean(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('activo')->label('Activo'),
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
        return [AsignacionesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHorarioAsistencias::route('/'),
            'create' => Pages\CreateHorarioAsistencia::route('/create'),
            'edit' => Pages\EditHorarioAsistencia::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return ['view_any', 'view', 'create', 'update', 'delete'];
    }
}
