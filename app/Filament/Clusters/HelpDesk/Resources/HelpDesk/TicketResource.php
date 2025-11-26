<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource\Pages;
use App\Models\HelpDesk\Ticket;
use App\Models\RRHH\Empleado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Asistencia al Cliente';
    protected static ?string $navigationLabel = 'Tickets de clientes';
    protected static ?string $pluralModelLabel = 'Tickets de clientes';

    protected static ?string $cluster = HelpDesk::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de solicitud')
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Numero de Ticket')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => Ticket::generarCodigo())
                            ->unique(ignoreRecord: true)->columnSpan(2),

                        DateTimePicker::make('fecha_solicitada')
                            ->label('Fecha Solicitada')
                            ->required()
                            ->default(now()),

                        DateTimePicker::make('fecha_programada')
                            ->label('Fecha Programada')
                            ->required()
                            ->default(now()),

                        TextInput::make('cli_solicitante')
                            ->label('Nombre de cliente')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('cli_telefono')
                            ->label('Teléfono de contacto')
                            ->tel()
                            ->maxLength(50),
                    ])
                    ->columns(2),

                Section::make('Información del Ticket')
                    ->schema([
                        Select::make('equipo_id')
                            ->label('Equipo')
                            ->relationship('equipo', 'codigo')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('destinatario_id')
                            ->label('Técnico Asignado')
                            ->options(Empleado::where('activo', true)->get()
                                ->pluck('full_name', 'id'))
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('tipo')
                            ->label('Tipo')
                            ->required()
                            ->options([
                                'preventivo' => 'Preventivo',
                                'correctivo' => 'Correctivo',
                            ]),

                        Select::make('prioridad')
                            ->label('Prioridad')
                            ->required()
                            ->options([
                                'baja' => 'Baja',
                                'media' => 'Media',
                                'alta' => 'Alta',
                                'urgente' => 'Urgente',
                            ])
                            ->default('media'),

                        Textarea::make('diagnostico')
                            ->label('Diagnóstico')
                            ->rows(3)
                            ->columnSpan(3),

                        FileUpload::make('adjunto')
                            ->label('Respaldos')
                            ->directory('respaldo_equipos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(4096),
                    ])
                    ->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('destinatario.full_name')
                    ->label('Técnico Asignado')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('equipo.codigo')
                    ->label('Equipo')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('equipo.cliente.razon_social')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('fecha_solicitada')
                    ->label('Fecha Solicitada')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('fecha_programada')
                    ->label('Fecha Programada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('empleado_creacion')
                    ->label('Creado Por')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),


                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'soporte' => 'info',
                        'incidente' => 'danger',
                        'requerimiento' => 'warning',
                        'mejora' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('prioridad')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'baja' => 'gray',
                        'media' => 'warning',
                        'alta' => 'danger',
                        'critica' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'abierto' => 'success',
                        'en_proceso' => 'warning',
                        'pendiente' => 'info',
                        'resuelto' => 'primary',
                        'cerrado' => 'gray',
                        default => 'gray',
                    }),


            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'soporte' => 'Soporte',
                        'incidente' => 'Incidente',
                        'requerimiento' => 'Requerimiento',
                        'mejora' => 'Mejora',
                    ]),

                SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'critica' => 'Crítica',
                    ]),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_proceso' => 'En Proceso',
                        'pendiente' => 'Pendiente',
                        'resuelto' => 'Resuelto',
                        'cerrado' => 'Cerrado',
                    ]),

                Filter::make('fecha_solicitada')
                    ->label('Fecha Solicitada')
                    ->form([
                        DatePicker::make('desde'),
                        DatePicker::make('hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn(Builder $query, $date): Builder => $query->whereDate('fecha_solicitada', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn(Builder $query, $date): Builder => $query->whereDate('fecha_solicitada', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make(),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTickets::route('/'),
            'create' => Pages\CreateTicket::route('/create'),
            'edit' => Pages\EditTicket::route('/{record}/edit'),
        ];
    }
}