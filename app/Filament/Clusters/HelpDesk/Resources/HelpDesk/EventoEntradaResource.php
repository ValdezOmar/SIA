<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource\Pages;
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
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class EventoEntradaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Evento::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationGroup = 'Help Desk';
    protected static ?string $modelLabel = 'Evento Entrada';
    protected static ?string $pluralModelLabel = 'Bandeja de Entrada';
    protected static ?string $navigationLabel = 'Bandeja de Entrada';
    protected static ?int $navigationSort = 1;
    protected static ?string $cluster = HelpDesk::class;

    public static function getEloquentQuery(): Builder
    {
        $empleadoId = Auth::user()->empleado?->id;

        return parent::getEloquentQuery()
            ->where('estado', 'entrada')
            ->where('destinatario_id', $empleadoId) // Solo eventos donde el destinatario es el empleado logueado
            ->whereNull('encargado_id'); // Y donde encargado_id es null
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
                            ->disabled()
                            ->searchable()
                            ->preload(),

                        Select::make('remitente_id')
                            ->label('Remitente')
                            ->relationship('encargado', 'nombres')
                            ->required()
                            ->disabled()
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

                        FileUpload::make('adjunto')
                            ->label('Archivo Adjunto')
                            ->directory('eventos/entradas')
                            ->preserveFilenames(),

                        Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->disabled()
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('descripcion')
                            ->label('Descripción del Problema')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),

                    ])->columns(3),
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
                    ->icon('heroicon-o-check')
                    ->color('primary')
                    ->action(function (Evento $record) {
                        $record->update([
                            'encargado_id' => Auth::user()->empleado?->id,
                            'estado' => 'pendiente',
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

                ViewAction::make(),
                //EditAction::make(),
            ]);
    }

    protected static function handleRecordCreation(array $data): Model
    {
        $data['estado'] = 'entrada';
        $data['fecha_entrada'] = $data['fecha_entrada'] ?? now();

        // Auto-asignar destinatario si no viene en los datos
        if (!isset($data['destinatario_id'])) {
            $data['destinatario_id'] = Auth::user()->empleado?->id;
        }

        return static::getModel()::create($data);
    }   

    // Si aún quieres prevenir generación de permisos
    public static function getPermissionPrefixes(): array
    {
        return [];
    }
    

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEventoEntradas::route('/'),
            'create' => Pages\CreateEventoEntrada::route('/create'),
            'edit' => Pages\EditEventoEntrada::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            // Relaciones si son necesarias
        ];
    }
}