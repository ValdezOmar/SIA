<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\TicketResource\Pages;
use App\Models\HelpDesk\Equipo;
use App\Models\HelpDesk\Ticket;
use App\Models\RRHH\Empleado;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Form;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TicketResource extends Resource
{
    protected static ?string $model = Ticket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Asistencia al Cliente';
    protected static ?string $navigationLabel = 'Tickets';
    protected static ?string $pluralModelLabel = 'Tickets';

    protected static ?string $cluster = HelpDesk::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Cliente')
                    ->schema([
                        TextInput::make('codigo')
                            ->label('N° Ticket')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn() => Ticket::generarCodigo())
                            ->unique(ignoreRecord: true),

                        Select::make('equipo_id')
                            ->label('Equipo')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hint('Seleccione el equipo del cliente')
                            ->relationship(
                                name: 'equipo',
                                titleAttribute: 'codigo',
                                modifyQueryUsing: fn($query) => $query->with('cliente')
                            )
                            ->getOptionLabelFromRecordUsing(function (Equipo $equipo) {
                                $info = $equipo->codigo;

                                // Agregar cliente
                                $cliente = $equipo->cliente?->razon_social ?? 'Sin cliente';
                                $info .= ' / ' . Str::limit($cliente, 20);

                                //Agregar descripción si existe
                                if ($equipo->cliente?->ciudad) {
                                    $info .= ' / ' . Str::limit($equipo->cliente->ciudad, 20);
                                }

                                return $info;
                            })
                            ->searchDebounce(500) // Evitar demasiadas consultas
                            ->columnSpan(2)
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    // Cargar información adicional cuando se selecciona un equipo
                                    $equipo = Equipo::with('cliente')->find($state);
                                    if ($equipo && $equipo->cliente) {
                                        // Auto-completar información del cliente
                                        $set('cli_solicitante', $equipo->cliente->razon_social);
                                        // Puedes agregar más campos si necesitas
                                    }
                                }
                            }),

                        TextInput::make('cli_solicitante')
                            ->label('Nombre del Cliente')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        TextInput::make('cli_telefono')
                            ->label('Teléfono de Contacto')
                            ->tel()
                            ->required()
                            ->maxLength(50),


                    ])
                    ->columns(3),

                Section::make('Detalles del Ticket')
                    ->schema([
                        Select::make('tipo')
                            ->label('Tipo de Ticket')
                            ->required()
                            ->options([
                                'preventivo' => '🛡️ Preventivo',
                                'correctivo' => '🔧 Correctivo',
                            ])
                            ->native(false),

                        Select::make('prioridad')
                            ->label('Prioridad')
                            ->required()
                            ->options([
                                'baja' => '🔵 Baja',
                                'media' => '🟡 Media',
                                'alta' => '🟠 Alta',
                                'urgente' => '🔴 Urgente',
                            ])
                            ->default('media')
                            ->native(false),

                        Select::make('destinatario_id')
                            ->label('Técnico Asignado')
                            ->options(function () {
                                return Empleado::where('activo', true)
                                    ->get()
                                    ->mapWithKeys(fn($empleado) => [
                                        $empleado->id => "{$empleado->full_name}" .
                                            ($empleado->cargo ? " - {$empleado->cargo}" : "")
                                    ]);
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            //->hint('Técnico responsable del ticket')
                            ->default(function ($livewire, $get) {
                                // Si estamos editando, mantener el valor actual
                                if ($livewire instanceof EditRecord) {
                                    return $livewire->record->destinatario_id;
                                }

                                // Si estamos creando, obtener del equipo
                                $equipoId = $get('equipo_id');
                                if ($equipoId) {
                                    $equipo = Equipo::with('tecnico')->find($equipoId);
                                    if ($equipo?->tecnico_asignado && $equipo->tecnico?->activo) {
                                        return $equipo->tecnico_asignado;
                                    }
                                }

                                // Si no hay técnico en el equipo, usar el usuario actual si es técnico
                                $currentUser = Auth::user();
                                if ($currentUser->empleado && $currentUser->empleado->activo) {
                                    return $currentUser->empleado->id;
                                }

                                return null;
                            })
                            ->live()
                            ->helperText(function ($get, $livewire) {
                                $equipoId = $get('equipo_id');

                                // Si estamos editando, mostrar información actual
                                if ($livewire instanceof EditRecord) {
                                    $ticket = $livewire->record;
                                    if ($ticket->destinatario) {
                                        return "Actualmente asignado: {$ticket->destinatario->full_name}";
                                    }
                                }

                                // Si estamos creando y hay equipo seleccionado
                                if ($equipoId && !($livewire instanceof EditRecord)) {
                                    $equipo = Equipo::with('tecnico')->find($equipoId);
                                    if ($equipo?->tecnico) {
                                        return "Técnico del equipo: {$equipo->tecnico->full_name}";
                                    }
                                }

                                return "Seleccione el técnico responsable";
                            })
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Section::make('Fechas y Programación')
                    ->schema([
                        DateTimePicker::make('fecha_solicitada')
                            ->label('Fecha de Solicitud')
                            ->required()
                            ->default(now())
                            ->displayFormat('d/m/Y H:i'),

                        DateTimePicker::make('fecha_programada')
                            ->label('Fecha Programada')
                            ->required()
                            ->default(now()->addDay())
                            ->displayFormat('d/m/Y H:i')
                            ->hint('Fecha estimada para atención'),

                        Textarea::make('diagnostico')
                            ->label('Diagnóstico / Descripción')
                            ->rows(4)
                            ->required()
                            ->placeholder('Describa el problema o solicitud...')
                            ->columnSpan(2),
                    ])
                    ->columns(2),

                Section::make('Documentos Adjuntos')
                    ->schema([
                        FileUpload::make('adjunto')
                            ->label('Archivos Adjuntos')
                            ->directory('tickets_adjuntos')
                            ->multiple()
                            ->openable()
                            ->downloadable()
                            ->maxFiles(5)
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                            ])
                            ->maxSize(5120) // 5MB en kilobytes
                            ->hint('Máx. 5 archivos, 5MB cada uno')
                            ->preserveFilenames()
                            ->getUploadedFileNameForStorageUsing(
                                fn($file): string => (string) str()->uuid() . '.' . $file->getClientOriginalExtension()
                            )
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                Section::make('Historial de Eventos')
                    ->description('Registro cronológico de acciones realizadas')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        View::make('filament.forms.components.historial-eventos')
                            ->hiddenLabel()
                            ->viewData([
                                'ticket' => fn($livewire) => $livewire->record?->load('eventosOrdenados'),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(false),

            ]);
    }

    public static function table(Table $table): Table
    {
        $currentUserId = Auth::user()->empleado?->id ?? null;

        return $table
            ->defaultPaginationPageOption(25)
            ->defaultSort('fecha_solicitada', 'desc')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->tooltip('Ver detalles del ticket'),

                TextColumn::make('cli_solicitante')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(20)
                    ->tooltip(fn(Ticket $record): string => $record->cli_solicitante ?? '')
                    ->description(fn(Ticket $record): string => $record->cli_telefono ?? ''),

                TextColumn::make('equipo.codigo')
                    ->label('Equipo')
                    ->sortable()
                    ->searchable()
                    ->description(fn(Ticket $record): string => $record->equipo?->cliente?->razon_social ?? 'N/A')
                    ->tooltip(fn(Ticket $record): string => $record->equipo?->cliente?->razon_social ?? ''),

                ViewColumn::make('info')
                    ->label('Información')
                    ->view('filament.forms.components.ticket-info')
                    ->viewData(['model' => Ticket::class])
                    ->sortable(false),

                TextColumn::make('prioridad')
                    ->label('Prioridad')
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'urgente' => 'danger',
                        'alta' => 'warning',
                        'media' => 'info',
                        'baja' => 'gray',
                        default => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'urgente' => 'heroicon-o-exclamation-triangle',
                        'alta' => 'heroicon-o-exclamation-circle',
                        'media' => 'heroicon-o-clock',
                        'baja' => 'heroicon-o-arrow-down',
                        default => 'heroicon-o-minus',
                    })
                    ->sortable()
                    ->searchable(),

                TextColumn::make('destinatario.full_name')
                    ->label('Asignado a:')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn(Ticket $record) => $record->destinatario_id == $currentUserId ? 'success' : 'gray')
                    ->tooltip('Técnico responsable'),

                TextColumn::make('fecha_programada')
                    ->label('Programado')
                    ->dateTime('d/m H:i')
                    ->sortable()
                    ->color(
                        fn(Ticket $record): string =>
                        $record->fecha_programada  && $record->estado != 'cerrado'
                            ? 'danger'
                            : 'gray'
                    )
                    ->tooltip('Fecha programada para atención'),

                IconColumn::make('adjunto')
                    ->label('Adjunto📎')
                    ->icon('heroicon-o-paper-clip')
                    ->color('info')
                    //->visible(fn(Ticket $record): bool => !empty($record->adjunto))
                    ->tooltip('Tiene archivos adjuntos'),
            ])
            ->filters([
                Filter::make('vencidos')
                    ->label('⚠️Tickets Vencidos')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('fecha_programada', '<', now())
                            ->whereNotIn('estado', ['cerrado'])
                    ),

                Filter::make('asignados_a_mi')
                    ->label('📌Asignados a mí')
                    ->query(
                        fn(Builder $query): Builder =>
                        $currentUserId ? $query->where('destinatario_id', $currentUserId) : $query
                    ),

                SelectFilter::make('estado')
                    ->label('Estado')
                    ->multiple()
                    ->options([
                        'abierto' => '🟢 Abierto',
                        'en_proceso' => '🟡 En Proceso',
                        'cerrado' => '⚫ Cerrado',
                    ])
                    ->default(['abierto', 'en_proceso']),

                SelectFilter::make('prioridad')
                    ->label('Prioridad')
                    ->multiple()
                    ->options([
                        'urgente' => '🔴 Urgente',
                        'alta' => '🟠 Alta',
                        'media' => '🟡 Media',
                        'baja' => '🔵 Baja',
                    ]),

                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'preventivo' => '🛡️Preventivo',
                        'correctivo' => '🔧Correctivo',
                    ]),

                Filter::make('fecha_solicitada')
                    ->label('Fecha de Solicitud')
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

            ])   //,layout: FiltersLayout::AboveContentCollapsible //para cambiar el layout por encima
            ->filtersFormColumns(2)

            ->actions([
                Action::make('cambiar_estado')
                    ->label('Cambiar Estado')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->tooltip('Cambiar estado')
                    ->iconButton()
                    ->form([
                        Select::make('estado')
                            ->label('Nuevo Estado')
                            ->options([
                                'abierto' => '🟢 Abierto',
                                'en_proceso' => '🟡 En Proceso',
                                'cerrado' => '⚫ Cerrado',
                            ])
                            ->required(),
                    ])
                    ->action(function (Ticket $record, array $data): void {
                        $record->update(['estado' => $data['estado']]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('cambiar_estado_masivo')
                        ->label('Cambiar Estado')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Select::make('estado')
                                ->label('Nuevo Estado')
                                ->options([
                                    'abierto' => 'Abierto',
                                    'en_proceso' => 'En Proceso',
                                    'cerrado' => 'Cerrado',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records): void {
                            $records->each->update(['estado' => $data['estado']]);
                        }),

                    Tables\Actions\BulkAction::make('reasignar_masivo')
                        ->label('Reasignar Técnicos')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Select::make('destinatario_id')
                                ->label('Asignar a Técnico')
                                ->options(Empleado::where('activo', true)->get()
                                    ->pluck('full_name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (array $data, $records): void {
                            $records->each->update(['destinatario_id' => $data['destinatario_id']]);
                        }),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Nuevo Ticket')
                    ->icon('heroicon-o-plus-circle'),
            ])
            ->striped()
            ->deferLoading()
            ->persistFiltersInSession()
            ->persistSearchInSession();
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
            //'view' => Pages\ViewTicket::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereIn('estado', ['abierto', 'en_proceso', 'pendiente'])->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = static::getModel()::whereIn('estado', ['abierto', 'en_proceso', 'pendiente'])->count();

        if ($count == 0) return 'success';
        if ($count <= 5) return 'warning';
        return 'danger';
    }
}