<?php

namespace App\Filament\Resources\Almacen;

use App\Filament\Resources\Almacen\InventarioResource\Pages;
use App\Filament\Resources\Almacen\InventarioResource\RelationManagers;
use App\Models\Inventario\Almacen;
use App\Models\Inventario\InventarioFisico;
use App\Models\Sistema\Empresa;
use App\Models\Sistema\Sucursal;
use App\Models\User;
use App\Services\Inventario\InventarioFisicoService as Servicio;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InventarioResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = InventarioFisico::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationGroup = 'Almacenes';

    protected static ?string $navigationLabel = 'Inventarios físicos';

    protected static ?string $modelLabel = 'Inventario físico';

    protected static ?string $pluralModelLabel = 'Inventarios físicos';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(Servicio::VER) ?? false;
    }

    public static function canCreate(): bool
    {
        return static::canViewAny() && auth()->user()->can(Servicio::PROGRAMAR);
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny() && static::getEloquentQuery()->whereKey($record->id)->exists();
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->delUsuario(auth()->user())->conProgreso()->with(['empresa', 'sucursal', 'almacen', 'responsable']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('empresa_id')->label('Empresa')->required()->searchable()->preload()->live()
                ->default(fn () => auth()->user()->empresa_id)
                ->options(fn () => Empresa::query()->when(auth()->user()->empresa_id, fn ($q, $id) => $q->whereKey($id))->pluck('nombre_comercial', 'id'))
                ->afterStateUpdated(function (Set $set) {
                    $set('sucursal_id', null);
                    $set('almacen_id', null);
                    $set('responsable_id', null);
                }),
            Select::make('sucursal_id')->label('Sucursal')->required()->searchable()->preload()->live()
                ->default(fn () => auth()->user()->sucursal_id)
                ->options(fn (Get $get) => Sucursal::where('empresa_id', $get('empresa_id'))->when(auth()->user()->sucursal_id, fn ($q, $id) => $q->whereKey($id))->pluck('nombre', 'id'))
                ->afterStateUpdated(function (Set $set) {
                    $set('almacen_id', null);
                    $set('responsable_id', null);
                }),
            Select::make('almacen_id')->label('Almacén')->required()->searchable()->preload()
                ->options(fn (Get $get) => Almacen::where('empresa_id', $get('empresa_id'))->where('sucursal_id', $get('sucursal_id'))->where('activo', true)->pluck('nombre', 'id')),
            DatePicker::make('fecha_programada')->label('Fecha del conteo')->required()->default(today())->minDate(today()),
            Select::make('responsable_id')->label('Responsable')->required()->searchable()->preload()
                ->options(fn (Get $get) => User::with('empleado.historialActivo')->get()->filter(fn ($user) => $user->can(Servicio::CONTAR) && $user->can(Servicio::VER)
                    && (! $user->empresa_id || (int) $user->empresa_id === (int) $get('empresa_id'))
                    && (! $user->sucursal_id || (int) $user->sucursal_id === (int) $get('sucursal_id')))->pluck('name', 'id')),
            Textarea::make('observaciones')->label('Alcance e instrucciones')->maxLength(4000)->rows(3)->columnSpanFull()
                ->helperText('Al iniciar se incluyen todos los artículos inventariables activos de la empresa y los que tengan existencias en este almacén. Coordine una pausa de movimientos durante el conteo; el sistema no los bloquea.'),
        ])->columns(2);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('codigo')->label('Inventario')->copyable(),
            TextEntry::make('estado')->label('Estado')->badge()->formatStateUsing(fn ($state) => InventarioFisico::ESTADOS[$state]),
            TextEntry::make('empresa.nombre_comercial')->label('Empresa'),
            TextEntry::make('sucursal.nombre')->label('Sucursal'),
            TextEntry::make('almacen.nombre')->label('Almacén'),
            TextEntry::make('responsable.name')->label('Responsable'),
            TextEntry::make('fecha_programada')->label('Fecha programada')->date('d/m/Y'),
            TextEntry::make('progreso')->label('Avance del conteo')->suffix('%')
                ->extraAttributes(['wire:poll.30s' => 'actualizar'])
                ->helperText(fn ($record) => $record->contados_count.' de '.$record->conteos_count.' artículos contados. '.$record->revisados_count.' revisados; '.$record->diferencias_count.' con diferencias.'),
            TextEntry::make('iniciado_at')->label('Stock de referencia tomado el')->dateTime('d/m/Y H:i')->placeholder('Aún no iniciado'),
            TextEntry::make('cerrado_at')->label('Finalizado el')->dateTime('d/m/Y H:i')->placeholder('Abierto'),
            TextEntry::make('observaciones')->label('Instrucciones')->placeholder('Sin observaciones')->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('codigo')->label('Inventario')->searchable()->sortable()->copyable(),
            TextColumn::make('empresa.nombre_comercial')->label('Empresa')->searchable(),
            TextColumn::make('sucursal.nombre')->label('Sucursal')->searchable(),
            TextColumn::make('almacen.nombre')->label('Almacén')->searchable(),
            TextColumn::make('fecha_programada')->label('Programado para')->date('d/m/Y')->sortable(),
            TextColumn::make('estado')->label('Estado')->badge()->formatStateUsing(fn ($state) => InventarioFisico::ESTADOS[$state])
                ->color(fn ($state) => match ($state) {
                    'cerrado' => 'success', 'cancelado' => 'danger', 'en_revision' => 'warning', default => 'info'
                }),
            TextColumn::make('progreso')->label('Conteo')->suffix('%')->description(fn ($record) => $record->contados_count.' / '.$record->conteos_count.' artículos'),
            TextColumn::make('revisados_count')->label('Revisados'),
            TextColumn::make('diferencias_count')->label('Diferencias')->color('warning'),
            TextColumn::make('responsable.name')->label('Responsable')->searchable()->toggleable(isToggledHiddenByDefault: true),
        ])->filters([
            Tables\Filters\SelectFilter::make('empresa_id')->label('Empresa')->searchable()
                ->options(fn () => Empresa::query()->when(auth()->user()->empresa_id, fn ($q, $id) => $q->whereKey($id))->pluck('nombre_comercial', 'id')),
            Tables\Filters\SelectFilter::make('sucursal_id')->label('Sucursal')->searchable()
                ->options(fn () => Sucursal::query()->when(auth()->user()->empresa_id, fn ($q, $id) => $q->where('empresa_id', $id))
                    ->when(auth()->user()->sucursal_id, fn ($q, $id) => $q->whereKey($id))->pluck('nombre', 'id')),
            Tables\Filters\SelectFilter::make('estado')->label('Estado')->multiple()->options(InventarioFisico::ESTADOS),
            Tables\Filters\Filter::make('abiertos')->label('Solo abiertos')->query(fn (Builder $query) => $query->whereIn('estado', ['programado', 'en_conteo', 'en_revision'])),
            Tables\Filters\Filter::make('fechas')->form([
                DatePicker::make('desde')->label('Desde'), DatePicker::make('hasta')->label('Hasta'),
            ])->query(fn (Builder $query, array $data) => $query->when($data['desde'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_programada', '>=', $fecha))
                ->when($data['hasta'] ?? null, fn ($q, $fecha) => $q->whereDate('fecha_programada', '<=', $fecha))),
        ])->actions([Tables\Actions\ViewAction::make()->label('Abrir')])->bulkActions([])
            ->defaultSort('fecha_programada', 'desc')->poll('30s')
            ->emptyStateHeading('No hay inventarios programados')->emptyStateDescription('Programe un inventario por empresa, sucursal y almacén.');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ConteosRelationManager::class, RelationManagers\EventosRelationManager::class];
    }

    public static function getPermissionPrefixes(): array
    {
        return ['view_any', 'update', 'programar_inventario'];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListInventarios::route('/'), 'create' => Pages\CreateInventario::route('/create'), 'view' => Pages\ViewInventario::route('/{record}')];
    }
}
