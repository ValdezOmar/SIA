<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\AuditoriaResource\Pages\ListAuditorias;
use App\Models\Sistema\AuditoriaSistema;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AuditoriaResource extends Resource
{
    protected static ?string $model = AuditoriaSistema::class;

    protected static ?string $cluster = Sistema::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Auditoría';

    protected static ?string $modelLabel = 'Evento de auditoría';

    protected static ?string $pluralModelLabel = 'Auditoría del sistema';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoreo';

    protected static ?int $navigationSort = 90;

    public static function canViewAny(): bool
    {
        return Auth::user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Fecha y hora')->dateTime('d/m/Y H:i:s')->sortable(),
                TextColumn::make('nivel')->label('Nivel')->badge()->color(fn (string $state): string => match ($state) {
                    'critico' => 'danger',
                    'advertencia' => 'warning',
                    default => 'info',
                }),
                TextColumn::make('tipo_evento')->label('Evento')->badge()->formatStateUsing(fn (string $state): string => match ($state) {
                    'creado' => 'Creación',
                    'actualizado' => 'Actualización',
                    'eliminado' => 'Eliminación',
                    'error_http' => 'Error HTTP',
                    'error_sistema' => 'Excepción',
                    default => $state,
                }),
                TextColumn::make('resumen')->label('Descripción')->searchable()->wrap(),
                TextColumn::make('usuario.name')->label('Usuario')->placeholder('Sistema')->description(fn (AuditoriaSistema $record): ?string => $record->usuario_id ? 'ID '.$record->usuario_id : null),
                TextColumn::make('tabla')->label('Tabla')->searchable()->toggleable(),
                TextColumn::make('registro_id')->label('Registro')->searchable()->toggleable(),
                TextColumn::make('codigo_http')->label('HTTP')->badge()->placeholder('—')->color(fn (?int $state): string => $state >= 500 ? 'danger' : 'warning'),
                TextColumn::make('ruta')->label('Ruta')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('metodo_http')->label('Método')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('tipo_evento')->label('Evento')->options([
                    'creado' => 'Creación',
                    'actualizado' => 'Actualización',
                    'eliminado' => 'Eliminación',
                    'error_http' => 'Error HTTP',
                    'error_sistema' => 'Excepción',
                ]),
                SelectFilter::make('nivel')->label('Nivel')->options([
                    'informativo' => 'Informativo',
                    'advertencia' => 'Advertencia',
                    'critico' => 'Crítico',
                ]),
                SelectFilter::make('codigo_http')->label('Código HTTP')->options(fn (): array => AuditoriaSistema::query()
                    ->whereNotNull('codigo_http')->distinct()->orderBy('codigo_http')->pluck('codigo_http', 'codigo_http')->all()),
                Filter::make('created_at')->label('Periodo')->schema([
                    DatePicker::make('desde')->label('Desde'),
                    DatePicker::make('hasta')->label('Hasta'),
                ])->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['desde'] ?? null, fn (Builder $q, string $fecha): Builder => $q->whereDate('created_at', '>=', $fecha))
                    ->when($data['hasta'] ?? null, fn (Builder $q, string $fecha): Builder => $q->whereDate('created_at', '<=', $fecha))),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Ver evento')
                    ->slideOver()
                    ->modalWidth('4xl')
                    ->schema([
                        Section::make('Evento registrado')->schema([
                            TextEntry::make('created_at')->label('Fecha y hora')->dateTime('d/m/Y H:i:s'),
                            TextEntry::make('nivel')->label('Nivel')->badge(),
                            TextEntry::make('tipo_evento')->label('Tipo'),
                            TextEntry::make('resumen')->label('Descripción')->columnSpanFull(),
                            TextEntry::make('usuario.name')->label('Usuario')->placeholder('Sistema o usuario eliminado'),
                            TextEntry::make('usuario_id')->label('ID de usuario')->placeholder('Sin usuario autenticado'),
                            TextEntry::make('empresa_id')->label('ID de empresa')->placeholder('Sin empresa'),
                            TextEntry::make('modelo')->label('Modelo')->placeholder('No aplica'),
                            TextEntry::make('tabla')->label('Tabla')->placeholder('No aplica'),
                            TextEntry::make('registro_id')->label('ID del registro')->placeholder('No aplica'),
                            TextEntry::make('codigo_http')->label('Código HTTP')->placeholder('No aplica'),
                            TextEntry::make('clase_excepcion')->label('Clase de excepción')->placeholder('No aplica'),
                            TextEntry::make('linea_excepcion')->label('Línea')->placeholder('No aplica'),
                            TextEntry::make('metodo_http')->label('Método HTTP')->placeholder('No aplica'),
                            TextEntry::make('ruta')->label('Ruta')->placeholder('No disponible'),
                            TextEntry::make('ip')->label('IP')->placeholder('No disponible'),
                            TextEntry::make('agente_usuario')->label('Agente del navegador')->placeholder('No disponible')->columnSpanFull(),
                            TextEntry::make('datos')->label('Datos del cambio')->formatStateUsing(fn ($state): string => $state
                                ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
                                : 'Sin datos adicionales')->columnSpanFull(),
                        ])->columns(2),
                    ]),
            ])
            ->emptyStateHeading('No hay eventos de auditoría')
            ->emptyStateDescription('Los cambios y errores del sistema aparecerán aquí cuando ocurran.')
            ->emptyStateIcon('heroicon-o-clipboard-document-list');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
        ];
    }
}
