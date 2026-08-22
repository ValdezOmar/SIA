<?php

namespace App\Filament\Clusters\Sistema\Resources\HorarioAsistenciaResource\RelationManagers;

use App\Models\RRHH\AsignacionHorarioAsistencia;
use App\Models\RRHH\Empleado;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class AsignacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'asignaciones';

    protected static ?string $title = 'Asignaciones a empleados';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Select::make('empleado_id')
                ->label('Empleado')
                ->options(fn (): array => Empleado::query()
                    ->orderBy('nombres')
                    ->orderBy('apellidos')
                    ->get()
                    ->mapWithKeys(fn (Empleado $empleado): array => [$empleado->id => "{$empleado->nombres} {$empleado->apellidos} ({$empleado->ci})"])
                    ->all())
                ->searchable()
                ->preload()
                ->helperText('Seleccione a quién aplicará este horario.')
                ->required(),
            Forms\Components\DatePicker::make('fecha_inicio')->label('Vigente desde')->default(today())->helperText('Fecha de inicio del turno.')->required(),
            Forms\Components\DatePicker::make('fecha_fin')->label('Vigente hasta')->afterOrEqual('fecha_inicio')->helperText('Déjelo vacío si no tiene fecha de finalización.'),
            Forms\Components\Toggle::make('activo')->label('Activa')->helperText('Solo las asignaciones activas se aplican al control de asistencia.')->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('empleado_id')
            ->columns([
                Tables\Columns\TextColumn::make('empleado.full_name')->label('Empleado')->searchable(['nombres', 'apellidos', 'ci']),
                Tables\Columns\TextColumn::make('fecha_inicio')->label('Desde')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('fecha_fin')->label('Hasta')->date('d/m/Y')->placeholder('Indefinido'),
                Tables\Columns\IconColumn::make('activo')->label('Activa')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Asignar empleado')
                    ->tooltip('Aplicar este horario a un empleado')
                    ->before(function (array $data): void {
                        $this->validarSolapamiento($data);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->tooltip('Modificar la vigencia de esta asignación')
                    ->before(function (array $data, AsignacionHorarioAsistencia $record): void {
                        $this->validarSolapamiento($data, $record);
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('Sin empleados asignados')
            ->emptyStateDescription('Asigne este horario a los empleados que corresponda.');
    }

    private function validarSolapamiento(array $data, $asignacionActual = null): void
    {
        if (! ($data['activo'] ?? true)) {
            return;
        }

        $query = $this->getOwnerRecord()->asignaciones()
            ->getRelated()
            ->newQuery()
            ->where('empleado_id', $data['empleado_id'])
            ->where('activo', true)
            ->whereDate('fecha_inicio', '<=', $data['fecha_fin'] ?? '9999-12-31')
            ->where(function ($query) use ($data): void {
                $query->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', $data['fecha_inicio']);
            });

        if ($asignacionActual) {
            $query->whereKeyNot($asignacionActual->getKey());
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'empleado_id' => 'Este empleado ya tiene un turno activo que se cruza con las fechas indicadas.',
            ]);
        }
    }
}
