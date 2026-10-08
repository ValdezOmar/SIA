<?php

namespace App\Filament\Clusters\Sistema\Resources;

use App\Filament\Clusters\Sistema;
use App\Filament\Clusters\Sistema\Resources\AuditoriaResource\Pages\ListAuditorias;
use App\Models\Sistema\Auditoria;
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

class AuditoriaResource extends Resource
{
    protected static ?string $model = Auditoria::class;

    protected static ?string $cluster = Sistema::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $modelLabel = 'Registro de auditoría';

    protected static ?string $pluralModelLabel = 'Auditoría';

    protected static string|\UnitEnum|null $navigationGroup = 'Control y seguridad';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['usuario', 'empresa', 'sucursal']);

        return auth()->user() ? $query->visiblePara(auth()->user()) : $query->whereRaw('1 = 0');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Evento')->columnSpanFull()->columns(['default' => 1, 'md' => 2])->schema([
                TextEntry::make('registrado_at')->label('Fecha de registro')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('nivel')->label('Severidad')->badge(),
                TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
                TextEntry::make('evento')->label('Evento'),
                TextEntry::make('categoria')->label('Módulo / categoría'),
                TextEntry::make('usuario.name')->label('Usuario')->placeholder('Sistema / sin usuario')->helperText(fn (Auditoria $record) => 'ID: '.($record->usuario_id ?? 'sin identificar')),
                TextEntry::make('origen')->label('Origen'),
                TextEntry::make('empresa.razon_social')->label('Empresa')->placeholder('Sin contexto'),
                TextEntry::make('sucursal.nombre')->label('Sucursal')->placeholder('Sin contexto'),
                TextEntry::make('entidad')->label('Modelo')->placeholder('Operación sin evento de modelo'),
                TextEntry::make('entidad_id')->label('ID del registro')->placeholder('No disponible'),
                TextEntry::make('tabla')->label('Tabla')->placeholder('No aplica'),
                TextEntry::make('correlacion_id')->label('ID de petición')->copyable()->placeholder('Proceso sin petición HTTP'),
            ]),
            Section::make('Cambios y contexto')->columnSpanFull()->schema(array_map(
                fn (string $campo) => TextEntry::make($campo)->label(match ($campo) {
                    'antes' => 'Antes', 'despues' => 'Después', default => 'Contexto'
                })
                    ->getStateUsing(fn (Auditoria $record): string => $record->$campo === null ? 'No disponible' : json_encode($record->$campo, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE))
                    ->fontFamily('mono')->columnSpanFull(),
                ['antes', 'despues', 'contexto'],
            )),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('registrado_at')->label('Fecha')->dateTime('d/m/Y H:i:s')->sortable(),
            TextColumn::make('nivel')->label('Severidad')->badge()->color(fn (string $state) => match ($state) {
                'emergency', 'alert', 'critical', 'error' => 'danger', 'warning', 'notice' => 'warning', default => 'info',
            }),
            TextColumn::make('categoria')->label('Módulo')->searchable(),
            TextColumn::make('evento')->label('Evento')->searchable(),
            TextColumn::make('descripcion')->label('Descripción')->wrap()->searchable(),
            TextColumn::make('usuario.name')->label('Usuario')->placeholder('Sistema')->searchable(),
            TextColumn::make('entidad_id')->label('ID del registro')->searchable()->toggleable(),
            TextColumn::make('tabla')->label('Tabla')->searchable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('correlacion_id')->label('ID de petición')->searchable()->copyable()->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('empresa.razon_social')->label('Empresa')->toggleable(isToggledHiddenByDefault: true),
            TextColumn::make('sucursal.nombre')->label('Sucursal')->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            SelectFilter::make('nivel')->label('Severidad')->options(array_combine(config('auditoria.niveles'), ['Depuración', 'Información', 'Aviso', 'Advertencia', 'Error', 'Crítico', 'Alerta', 'Emergencia'])),
            SelectFilter::make('categoria')->label('Módulo')->options(fn () => self::getEloquentQuery()->distinct()->pluck('categoria', 'categoria')->all()),
            SelectFilter::make('evento')->label('Evento')->options(fn () => self::getEloquentQuery()->distinct()->pluck('evento', 'evento')->all())->searchable(),
            SelectFilter::make('usuario_id')->label('Usuario')->options(fn () => self::getEloquentQuery()->whereNotNull('usuario_id')->get(['usuario_id'])->pluck('usuario.name', 'usuario_id')->all())->searchable(),
            Filter::make('fechas')->schema([DatePicker::make('desde')->label('Desde'), DatePicker::make('hasta')->label('Hasta')->afterOrEqual('desde')])
                ->query(fn (Builder $query, array $data) => $query->when($data['desde'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('registrado_at', '>=', $fecha))->when($data['hasta'] ?? null, fn (Builder $q, $fecha) => $q->whereDate('registrado_at', '<=', $fecha))),
        ])->recordActions([ViewAction::make()->modalWidth('5xl')])
            ->emptyStateHeading('No hay eventos de auditoría para estos filtros');
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditorias::route('/')];
    }
}
