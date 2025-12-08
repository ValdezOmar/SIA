<?php

namespace App\Filament\Clusters\HelpDesk\Resources\HelpDesk;

use App\Filament\Clusters\HelpDesk;
use App\Filament\Clusters\HelpDesk\Resources\HelpDesk\EventoEntradaResource\Pages;
use App\Models\HelpDesk\Evento;
use App\Models\HelpDesk\Ticket;
use App\Models\RRHH\Empleado;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Support\HtmlString;

class EventoEntradaResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Evento::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    protected static ?string $navigationGroup = 'Help Desk';
    protected static ?string $modelLabel = 'Bandeja de Entrada';
    protected static ?string $pluralModelLabel = 'Bandeja de Entrada';
    protected static ?string $navigationLabel = 'Bandeja de Entrada';
    protected static ?int $navigationSort = 1;
    protected static ?string $cluster = HelpDesk::class;

    public static function getEloquentQuery(): Builder
    {
        $empleadoId = Auth::user()->empleado?->id;

        return parent::getEloquentQuery()
            ->where('estado', 'entrada') // Estado puede ser "entrada" o "salida"
            ->where('destinatario_id', $empleadoId)    // Y destinatario es el empleado
            ->whereNull('encargado_id')                // Y encargado_id es nulo
            ->with(['ticket' => function ($query) {
                $query->with(['equipo' => function ($q) {
                    $q->with('cliente');
                }]);
            }, 'remitente']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Ticket')
                    ->description('Detalles del ticket asignado')
                    ->icon('heroicon-o-ticket')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Tarjeta de información del ticket
                                // Ticket Info optimizado
                                Grid::make(1)
                                    ->schema([
                                        Placeholder::make('ticket_info')
                                            ->label('Información Ticket')
                                            ->content(function ($livewire) {
                                                $ticket = $livewire->record?->ticket;
                                                if (!$ticket) return new HtmlString('
                                                    <div class="p-3 text-center">
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin ticket asignado</p>
                                                    </div>
                                                ');

                                                $html = '<div class="space-y-3">';

                                                // Código y badges
                                                $html .= '<div class="flex items-center justify-between">';
                                                $html .= '<span class="font-semibold text-gray-900 dark:text-gray-100">' . $ticket->codigo . '</span>';
                                                $html .= '<div class="flex gap-1">';
                                                $html .= '<span class="px-2 py-0.5 text-xs rounded-full font-medium ' .
                                                    match ($ticket->prioridad) {
                                                        'urgente' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                        'alta' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300',
                                                        'media' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                                    } . '">' . ucfirst($ticket->prioridad) . '</span>';
                                                $html .= '<span class="px-2 py-0.5 text-xs rounded-full font-medium ' .
                                                    match ($ticket->estado) {
                                                        'cerrado' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                                        'abierto' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                                    } . '">' . ucfirst($ticket->estado) . '</span>';
                                                $html .= '</div>';
                                                $html .= '</div>';

                                                // Cliente
                                                $html .= '<div class="pt-2 border-t border-gray-200 dark:border-gray-700">';
                                                $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Cliente</p>';
                                                $html .= '<p class="text-sm font-medium text-gray-900 dark:text-gray-100">' . ($ticket->cli_solicitante ?? 'No especificado') . '</p>';
                                                if ($ticket->cli_telefono) {
                                                    $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 mt-1">📞 ' . $ticket->cli_telefono . '</p>';
                                                }
                                                $html .= '</div>';

                                                // Fechas
                                                $html .= '<div class="pt-2 border-t border-gray-200 dark:border-gray-700">';
                                                $html .= '<div class="grid grid-cols-2 gap-2 text-xs">';

                                                if ($ticket->fecha_solicitada) {
                                                    $fecha = is_string($ticket->fecha_solicitada) ? $ticket->fecha_solicitada : $ticket->fecha_solicitada->format('d/m/Y H:i');
                                                    $html .= '<div>';
                                                    $html .= '<p class="text-gray-500 dark:text-gray-400">Solicitud</p>';
                                                    $html .= '<p class="font-medium text-gray-700 dark:text-gray-300">' . $fecha . '</p>';
                                                    $html .= '</div>';
                                                }

                                                if ($ticket->fecha_programada) {
                                                    $fecha = is_string($ticket->fecha_programada) ? $ticket->fecha_programada : $ticket->fecha_programada->format('d/m/Y H:i');
                                                    $html .= '<div>';
                                                    $html .= '<p class="text-gray-500 dark:text-gray-400">Programado</p>';
                                                    $html .= '<p class="font-medium text-gray-700 dark:text-gray-300">' . $fecha . '</p>';
                                                    $html .= '</div>';
                                                }

                                                $html .= '</div>';
                                                $html .= '</div>';

                                                $html .= '</div>';
                                                return new HtmlString($html);
                                            })
                                            ->extraAttributes(['class' => 'p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'])
                                            ->columnSpan(1),
                                    ])
                                    ->columnSpan(1),

                                // Remitente con fecha de entrada del evento
                                Grid::make(1)
                                    ->schema([
                                        Placeholder::make('remitente_card')
                                            ->label('Información Remitente')
                                            ->content(function ($livewire) {
                                                $evento = $livewire->record; // Obtener el evento del livewire
                                                $remitente = $evento?->remitente;

                                                if (!$remitente) return new HtmlString('
                                                    <div class="p-3 text-center">
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin remitente asignado</p>
                                                    </div>
                                                ');

                                                $html = '<div class="space-y-3">';

                                                // Nombre
                                                $html .= '<div>';
                                                $html .= '<p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Remitente</p>';
                                                $html .= '<p class="text-sm font-medium text-gray-900 dark:text-gray-100">' . $remitente->full_name . '</p>';
                                                $html .= '</div>';

                                                // Información adicional
                                                $html .= '<div class="pt-2 border-t border-gray-200 dark:border-gray-700">';
                                                $html .= '<div class="space-y-2 text-sm">';

                                                if ($remitente->cargo) {
                                                    $html .= '<div>';
                                                    $html .= '<p class="text-gray-500 dark:text-gray-400">Cargo</p>';
                                                    $html .= '<p class="text-gray-700 dark:text-gray-300">' . $remitente->cargo . '</p>';
                                                    $html .= '</div>';
                                                }

                                                if ($remitente->correo_corporativo) {
                                                    $html .= '<div>';
                                                    $html .= '<p class="text-gray-500 dark:text-gray-400">Email</p>';
                                                    $html .= '<p class="text-blue-600 dark:text-blue-400 font-medium truncate">' . $remitente->correo_corporativo . '</p>';
                                                    $html .= '</div>';
                                                }

                                                // Fecha de entrada del evento
                                                if ($evento && $evento->fecha_entrada) {
                                                    $fechaEntrada = is_string($evento->fecha_entrada) ?
                                                        $evento->fecha_entrada :
                                                        $evento->fecha_entrada->format('d/m/Y H:i');

                                                    $html .= '<div>';
                                                    $html .= '<p class="text-gray-500 dark:text-gray-400">Fecha de Entrada</p>';
                                                    $html .= '<p class="text-gray-700 dark:text-gray-300 font-medium">' . $fechaEntrada . '</p>';
                                                    $html .= '</div>';
                                                }

                                                $html .= '</div>';
                                                $html .= '</div>';

                                                $html .= '</div>';
                                                return new HtmlString($html);
                                            })
                                            ->extraAttributes(['class' => 'p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'])
                                            ->columnSpan(1),
                                    ])
                                    ->columnSpan(1),

                                // Diagnóstico optimizado
                                Grid::make(1)
                                    ->schema([
                                        Placeholder::make('diagnostico_card')
                                            ->label('Diagnóstico')
                                            ->content(function ($livewire) {
                                                $diagnostico = $livewire->record?->ticket?->diagnostico;
                                                if (!$diagnostico) return new HtmlString('
                                                    <div class="p-3 text-center">
                                                        <p class="text-sm text-gray-500 dark:text-gray-400">Sin diagnóstico disponible</p>
                                                    </div>
                                                ');

                                                return new HtmlString('
                                                    <div class="space-y-2">
                                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Descripción del problema</p>
                                                        <div class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed whitespace-pre-wrap max-h-40 overflow-y-auto p-2 bg-gray-50/50 dark:bg-gray-900/20 rounded">
                                                            ' . nl2br(e($diagnostico)) . '
                                                        </div>
                                                    </div>
                                                ');
                                            })
                                            ->extraAttributes(['class' => 'p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800'])
                                            ->columnSpan(2),
                                    ])
                                    ->columnSpan(2),
                            ])
                            ->columnSpanFull(),

                        Hidden::make('remitente_id')
                            ->default(fn() => Auth::user()->empleado?->id),

                        Hidden::make('destinatario_id')
                            ->default(fn() => Auth::user()->empleado?->id),
                    ])
                    ->collapsible(false),

                // Acciones del ticket
                Section::make('Acciones del Remitente')
                    ->description('Complete la información requerida')
                    ->icon('heroicon-o-cog')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Select::make('prioridad')
                                    ->label('Prioridad')
                                    ->required()
                                    ->options([
                                        'baja' => '🔵 Baja',
                                        'media' => '🟡 Media',
                                        'alta' => '🟠 Alta',
                                        'urgente' => '🔴 Urgente',
                                    ])
                                    ->native(false)
                                    ->columnSpan(1),

                                FileUpload::make('adjunto')
                                    ->label('Archivos Adjuntos')
                                    ->directory('eventos/entradas/' . date('Y/m'))
                                    ->multiple()
                                    ->maxFiles(5)
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/*',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                                    ])
                                    ->maxSize(10240)
                                    ->hint('Máx. 5 archivos, 10MB c/u')
                                    ->columnSpan(2),
                            ]),

                        Textarea::make('observaciones')
                            ->label('Observaciones del Remitente')
                            ->disabled()
                            ->rows(2)
                            ->placeholder('Sin observaciones')
                            ->columnSpanFull()
                            ->hidden(fn($get) => !filled($get('observaciones'))), // Ocultar si está vacío
                    ])
                    ->collapsible(false)
                    ->hidden(fn($get) => empty($get('observaciones'))), // ← OCULTAR TODA LA SECCIÓN
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(20)
            ->defaultSort('fecha_entrada', 'desc')
            ->columns([
                TextColumn::make('ticket.codigo')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary')
                    ->description(fn(Evento $record): string =>
                    $record->ticket?->equipo?->codigo ?? 'N/A')
                    ->tooltip('Ver detalles del ticket'),

                TextColumn::make('ticket.cli_solicitante')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(25)
                    ->description(fn(Evento $record): string =>
                    $record->ticket?->cli_telefono ?? '')
                    ->tooltip(fn(Evento $record): string =>
                    $record->ticket?->cli_solicitante ?? ''),

                TextColumn::make('remitente.full_name')
                    ->label('Remitente')
                    ->searchable()
                    ->limit(20)
                    ->description(fn(Evento $record): string =>
                    $record->remitente?->cargo ?? '')
                    ->tooltip('Técnico remitente'),

                TextColumn::make('ticket.prioridad')
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

                TextColumn::make('fecha_entrada')
                    ->label('Recibido')
                    ->dateTime('d/m H:i')
                    ->sortable()
                    ->description(fn(Evento $record): string =>
                    $record->ticket?->fecha_solicitada ?? '')
                    ->tooltip('Fecha de recepción'),

                TextColumn::make('ticket.diagnostico')
                    ->label('Diagnóstico')
                    ->limit(30)
                    ->searchable()
                    ->tooltip(fn(Evento $record): string =>
                    $record->ticket?->diagnostico ?? ''),
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
                Action::make('ver')
                    ->label('Ver')
                    ->color('primary')
                    ->icon('heroicon-o-eye')
                    ->tooltip('Ver detalles')
                    ->url(fn(Evento $record): string =>
                    EventoEntradaResource::getUrl('edit', ['record' => $record])),

                //Boton aceptar ticket
                Action::make('aceptar')
                    ->label('Aceptar')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->tooltip('Aceptar ticket')
                    ->action(function (Evento $record) {
                        // Obtener el ID del empleado actual
                        $empleadoId = Auth::user()->empleado?->id;

                        // 1. Buscar y cerrar el registro anterior (si existe)
                        // Buscamos el registro donde este empleado era el DESTINATARIO
                        // y el estado era 'entrada' o 'salida'
                        if ($record->ticket) {
                            Evento::where('hd_ticket_id', $record->hd_ticket_id)
                                ->where('destinatario_id', $empleadoId) // Este empleado era el destinatario
                                ->whereIn('estado', ['entrada', 'salida']) // En estado pendiente de aceptación
                                ->where('id', '!=', $record->id) // Excluir el registro actual
                                ->update([
                                    'estado' => 'cerrado',
                                    'fecha_salida' => now(),
                                ]);
                        }

                        // 2. Actualizar el registro actual (aceptar en la bandeja)
                        $record->update([
                            'encargado_id' => $empleadoId,
                            'destinatario_id' => null, // Ya no tiene destinatario, lo tiene el encargado
                            'estado' => 'pendiente',
                            'fecha_recepcion' => now(), // Fecha en que se aceptó
                        ]);

                        // 3. Actualizar el ticket principal
                        if ($record->ticket) {
                            $record->ticket->update([
                                'estado' => 'en_proceso',
                                'encargado_id' => $empleadoId,
                            ]);
                        }

                        Notification::make()
                            ->title('Ticket Aceptado')
                            ->body('El ticket ha sido aceptado y está ahora en proceso.')
                            ->success()
                            ->send();
                    })
                    ->hidden(fn(Evento $record): bool => !is_null($record->encargado_id))
                    ->requiresConfirmation()
                    ->modalHeading('Aceptar Ticket')
                    ->modalDescription('¿Aceptar este ticket para comenzar a trabajar en él?'),

                //Boton derivar ticket con funcionalidades exclusivas para bandeja de entrada
                Action::make('derivar')
                    ->label('Derivar')
                    ->color('warning')
                    ->icon('heroicon-o-arrow-up-circle')
                    ->tooltip('Derivar a otro técnico')
                    ->form([
                        Select::make('destinatario_id')
                            ->label('Derivar a:')
                            ->options(
                                Empleado::where('activo', true)
                                    ->get()
                                    ->mapWithKeys(fn($emp) => [
                                        $emp->id => "{$emp->full_name}" .
                                            ($emp->cargo ? " - {$emp->cargo}" : "")
                                    ])
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        Textarea::make('descripcion')
                            ->label('Observaciones')
                            ->placeholder('Describe por qué se deriva este ticket...')
                            ->required(),
                    ])
                    ->action(function (array $data, Evento $record) {
                        $destinatarioActual = $record->destinatario_id;
                        $record->update([
                            'remitente_id' => $destinatarioActual,
                            'encargado_id' => $destinatarioActual,
                            'destinatario_id' => $data['destinatario_id'],
                            'estado' => 'salida',
                            'fecha_salida' => now(),
                        ]);

                        Evento::create([
                            'hd_ticket_id' => $record->hd_ticket_id,
                            'remitente_id' => $record->encargado_id,
                            'destinatario_id' => $data['destinatario_id'],
                            'area_origen_id' => $record->area_destino_id,
                            'area_destino_id' => Empleado::find($data['destinatario_id'])?->area_id,
                            'estado' => 'entrada',
                            'fecha_entrada' => now(),
                            'observaciones' => $data['descripcion'],
                            'descripcion' => $record->descripcion,
                            'prioridad' => $record->prioridad,
                        ]);

                        if ($record->ticket) {
                            $record->ticket->update([
                                'destinatario_id' => $data['destinatario_id'],
                                'estado' => 'en_proceso',
                            ]);
                        }

                        Notification::make()
                            ->title('Ticket Derivado')
                            ->body('El ticket ha sido derivado a otro técnico.')
                            ->success()
                            ->send();

                        return redirect(EventoEntradaResource::getUrl('index'));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Derivar Ticket')
                    ->modalDescription('¿Derivar este ticket a otro técnico?')
                    ->hidden(fn(Evento $record): bool => !is_null($record->observaciones)), // SOLO SE MUESTRA SI OBSERVACIONES ES NULL
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar Seleccionados'),
                ]),
            ])
            ->emptyStateHeading('🎉 ¡Bandeja vacía!')
            ->emptyStateDescription('No tienes tickets pendientes en tu bandeja de entrada.')
            ->emptyStateIcon('heroicon-o-inbox')
            ->emptyStateActions([
                Action::make('refresh')
                    ->label('Actualizar')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn() => null),
            ])
            ->striped()
            ->deferLoading();
    }

    //Badge contador de bandeja
    public static function getNavigationBadge(): ?string
    {
        $count = Auth::user()->empleado?->id
            ? static::getModel()::where('estado', 'entrada')
            ->where('destinatario_id', Auth::user()->empleado->id)
            ->whereNull('encargado_id')
            ->count()
            : 0;

        return $count > 0 ? (string) $count : null;
    }

    //Color de estado de Badge
    public static function getNavigationBadgeColor(): string|array|null
    {
        $count = Auth::user()->empleado?->id
            ? static::getModel()::where('estado', 'entrada')
            ->where('destinatario_id', Auth::user()->empleado->id)
            ->whereNull('encargado_id')
            ->count()
            : 0;

        return match (true) {
            $count == 0 => 'success',
            $count <= 3 => 'warning',
            default => 'danger',
        };
    }

    protected static function handleRecordCreation(array $data): Model
    {
        $data['estado'] = 'entrada';
        $data['fecha_entrada'] = $data['fecha_entrada'] ?? now();

        if (!isset($data['destinatario_id'])) {
            $data['destinatario_id'] = Auth::user()->empleado?->id;
        }

        return static::getModel()::create($data);
    }

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
        return [];
    }
}