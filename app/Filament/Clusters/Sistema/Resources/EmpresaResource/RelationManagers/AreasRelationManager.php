<?php

namespace App\Filament\Clusters\Sistema\Resources\EmpresaResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AreasRelationManager extends RelationManager
{
    protected static string $relationship = 'areas';

    protected static ?string $title = 'Áreas';

    protected static ?string $modelLabel = 'área';

    protected static ?string $pluralModelLabel = 'áreas';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del área')
                ->description('El área puede compartirse entre empresas y contiene sus cargos organizacionales.')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre del área')
                        ->placeholder('Ej. Recursos Humanos, Ventas, Tecnología')
                        ->helperText('Use un nombre corto y fácil de identificar.')
                        ->required()
                        ->maxLength(150),
                ]),

            Forms\Components\Section::make('Cargos del área')
                ->description('Agregue los puestos de trabajo que pertenecen a esta área.')
                ->schema([
                    Forms\Components\Repeater::make('cargos')
                        ->relationship('cargos')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('nombre')
                                ->label('Nombre del cargo')
                                ->placeholder('Ej. Gerente, Analista, Asistente')
                                ->helperText('Use el nombre del puesto, no el nombre de una persona.')
                                ->required()
                                ->maxLength(150),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['nombre'] ?? 'Nuevo cargo')
                        ->addActionLabel('Agregar cargo')
                        ->defaultItems(0)
                        ->collapsible()
                        ->cloneable()
                        ->reorderable(false)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Área')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('cargos_count')
                    ->counts('cargos')
                    ->label('Cargos')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('cargos.nombre')
                    ->label('Detalle de cargos')
                    ->badge()
                    ->separator(',')
                    ->placeholder('Sin cargos')
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Asociar área existente')
                    ->icon('heroicon-o-link')
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['nombre'])
                    ->recordSelect(fn ($select) => $select
                        ->label('Área')
                        ->placeholder('Busque un área por nombre')
                        ->helperText('Seleccione un área ya registrada en el sistema.')),

                Tables\Actions\CreateAction::make()
                    ->label('Crear y asociar área')
                    ->icon('heroicon-o-plus'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Administrar cargos')
                    ->icon('heroicon-o-briefcase')
                    ->modalHeading('Administrar área y cargos')
                    ->modalSubmitActionLabel('Guardar cambios')
                    ->modalWidth('4xl'),

                Tables\Actions\DetachAction::make()
                    ->label('Desvincular'),
            ])
            ->emptyStateHeading('Esta empresa aún no tiene áreas')
            ->emptyStateDescription('Asocie un área existente o cree una nueva para organizar sus cargos.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
