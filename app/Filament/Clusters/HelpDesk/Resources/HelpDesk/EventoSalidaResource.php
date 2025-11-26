<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoSalidaResource\Pages;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoSalidaResource\RelationManagers;
use App\Models\HelpDesk\Evento;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EventoSalidaResource extends Resource
{
    protected static ?string $model = Evento::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected static ?string $navigationGroup = 'Help Desk';
    protected static ?string $modelLabel = 'Evento Salida';
    protected static ?string $pluralModelLabel = 'Eventos de Salida';
    protected static ?string $navigationLabel = 'Salidas';
    protected static ?int $navigationSort = 3;
    protected static ?string $cluster = HelpDesk::class;

    public static function getEloquentQuery(): Builder
    {
        $empleadoId = Auth::user()->empleado?->id;

        return parent::getEloquentQuery()
            ->where('estado', 'salida')
            ->where('encargado_id', $empleadoId); // Solo eventos donde el encargado es el empleado logueado
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Ticket')
                    ->schema([
                        Select::make('hd_ticket_id')
                            ->label('Ticket')
                            ->relationship('ticket', 'codigo')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Select::make('encargado_id')
                            ->label('Técnico Encargado')
                            ->relationship('encargado', 'nombres')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Hidden::make('remitente_id')
                            ->default(fn() => Auth::user()->empleado?->id),

                        Hidden::make('destinatario_id')
                            ->default(fn() => Auth::user()->empleado?->id), // Auto-asignar al empleado logueado
                    ])
                    ->columns(2),

                Section::make('Detalles de la Entrada')
                    ->schema([
                        Select::make('prioridad')
                            ->label('Prioridad')
                            ->required()
                            ->options([
                                'baja' => 'Baja',
                                'media' => 'Media',
                                'alta' => 'Alta',
                                'urgente' => 'Urgente',
                            ]),

                        DateTimePicker::make('fecha_entrada')
                            ->label('Fecha de Entrada')
                            ->required()
                            ->default(now()),

                        Textarea::make('descripcion')
                            ->label('Descripción del Problema')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->columnSpanFull(),

                        FileUpload::make('adjunto')
                            ->label('Archivo Adjunto')
                            ->directory('eventos/entradas')
                            ->preserveFilenames(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ticket.codigo')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('ticket.equipo.codigo')
                    ->label('Equipo')
                    ->searchable(),

                TextColumn::make('ticket.equipo.cliente.razon_social')
                    ->label('Cliente')
                    ->searchable(),
                TextColumn::make('remitente.nombres')
                    ->label('Remitente')
                    ->searchable(),

                // TextColumn::make('destinatario.nombres')
                //     ->label('destinatario')
                //     ->searchable(),

                // TextColumn::make('encargado.nombres')
                //     ->label('Técnico Encargado')
                //     ->searchable()
                //     ->placeholder('Sin asignar'),

                TextColumn::make('prioridad')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'baja' => 'gray',
                        'media' => 'warning',
                        'alta' => 'danger',
                        'urgente' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('fecha_entrada')
                    ->label('Fecha Entrada')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
            ])
            ->actions([
                Action::make('aceptar')
                    ->label('Aceptar')
                    ->icon('heroicon-o-user-plus')
                    ->color('primary')
                    ->action(function (Evento $record) {
                        $record->update([
                            'encargado_id' => Auth::user()->empleado?->id,
                        ]);
                    })
                    ->hidden(fn(Evento $record) => !is_null($record->encargado_id)),

                Action::make('derivar')
                    ->label('Derivar')
                    ->icon('heroicon-o-arrow-right')
                    ->color('warning')
                    ->action(function (Evento $record) {
                        $record->update(['estado' => 'pendiente']);
                    }),

                Action::make('procesar')
                    ->label('Procesar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Evento $record) {
                        $record->update(['estado' => 'salida']);
                    }),

                ViewAction::make(),
                EditAction::make(),
            ]);
        // ->bulkActions([
        //     BulkActionGroup::make([
        //         DeleteBulkAction::make(),
        //     ]),
        // ])
        // ->emptyStateActions([
        //     CreateAction::make(),
        // ]);
    }

    protected static function handleRecordCreation(array $data): Model
    {
        $data['estado'] = 'salida';
        return static::getModel()::create($data);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventoSalidas::route('/'),
            'create' => Pages\CreateEventoSalida::route('/create'),
            'edit' => Pages\EditEventoSalida::route('/{record}/edit'),
        ];
    }
}