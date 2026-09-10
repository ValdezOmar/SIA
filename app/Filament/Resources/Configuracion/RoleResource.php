<?php

namespace App\Filament\Resources\Configuracion;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Configuracion\RoleResource\Pages\ListRoles;
use App\Filament\Resources\Configuracion\RoleResource\Pages\CreateRole;
use App\Filament\Resources\Configuracion\RoleResource\Pages\ViewRole;
use App\Filament\Resources\Configuracion\RoleResource\Pages\EditRole;
use Filament\Panel;
use App\Filament\Resources\Configuracion\RoleResource\Pages;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use BezhanSalleh\FilamentShield\Support\Utils;
use BezhanSalleh\FilamentShield\Traits\HasShieldFormComponents;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RoleResource extends Resource
{
    use HasShieldFormComponents;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    /**
     * Organiza los permisos con la misma jerarquía que ve el usuario en el menú.
     * La fuente sigue siendo Filament Shield, por lo que los recursos descubiertos
     * automáticamente se incluyen sin mantener una lista manual.
     */
    public static function getResourceEntitiesSchema(): ?array
    {
        $navigationOrder = collect(Filament::getCurrentOrDefaultPanel()->getNavigationGroups())
            ->map(fn ($group): string => is_string($group) ? $group : $group->getLabel())
            ->flip();

        return collect(FilamentShield::getResources() ?? [])
            ->groupBy(fn (array $entity): string => static::getResourceNavigationGroup($entity['resourceFqcn']))
            ->sortBy(fn ($resources, string $group): string => sprintf(
                '%010d-%s',
                $navigationOrder->get($group, PHP_INT_MAX),
                str($group)->lower(),
            ))
            ->map(function ($resources, string $group) {
                return Section::make($group)
                    ->description('Permisos de los recursos disponibles en este grupo del menú.')
                    ->schema(
                        $resources
                            ->sortBy(fn (array $entity): string => static::getResourcePermissionSectionLabel($entity))
                            ->map(fn (array $entity) => static::makeResourcePermissionSection($entity))
                            ->values()
                            ->all(),
                    )
                    ->columns(static::shield()->getGridColumns())
                    ->columnSpanFull()
                    ->collapsible();
            })
            ->values()
            ->all();
    }

    protected static function getResourceNavigationGroup(string $resource): string
    {
        $group = $resource::getNavigationGroup();

        if (blank($group) && ($cluster = $resource::getCluster())) {
            $group = $cluster::getNavigationGroup();
        }

        return $group ?: 'Sin grupo de navegación';
    }

    protected static function getResourcePermissionSectionLabel(array $entity): string
    {
        $resource = $entity['resourceFqcn'];
        $navigationLabel = $resource::getNavigationLabel();

        return filled($navigationLabel)
            ? $navigationLabel
            : $resource::getPluralModelLabel();
    }

    protected static function makeResourcePermissionSection(array $entity): Section
    {
        $resource = $entity['resourceFqcn'];
        $label = static::getResourcePermissionSectionLabel($entity);

        return Section::make($label)
            ->icon($resource::getNavigationIcon())
            ->description("Permisos correspondientes a la opción de menú «{$label}».")
            ->compact()
            ->schema([
                static::getCheckBoxListComponentForResource($entity),
            ])
            ->columnSpan(static::shield()->getSectionColumnSpan())
            ->collapsible();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('filament-shield::filament-shield.field.name'))
                                    ->unique(ignoreRecord: true)
                                    ->required()
                                    ->maxLength(255),

                                TextInput::make('guard_name')
                                    ->label(__('filament-shield::filament-shield.field.guard_name'))
                                    ->default(Utils::getFilamentAuthGuard())
                                    ->nullable()
                                    ->maxLength(255),

                                Select::make(config('permission.column_names.team_foreign_key'))
                                    ->label(__('filament-shield::filament-shield.field.team'))
                                    ->placeholder(__('filament-shield::filament-shield.field.team.placeholder'))
                                    /** @phpstan-ignore-next-line */
                                    ->default([Filament::getTenant()?->id])
                                    ->options(fn (): Arrayable => Utils::getTenantModel() ? Utils::getTenantModel()::pluck('name', 'id') : collect())
                                    ->hidden(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled()))
                                    ->dehydrated(fn (): bool => ! (static::shield()->isCentralApp() && Utils::isTenancyEnabled())),
                                static::getSelectAllFormComponent(),

                            ])
                            ->columns([
                                'sm' => 2,
                                'lg' => 3,
                            ]),
                    ]),
                static::getShieldFormComponents(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->weight('font-medium')
                    ->label(__('filament-shield::filament-shield.column.name'))
                    ->formatStateUsing(fn ($state): string => Str::headline($state))
                    ->searchable(),
                TextColumn::make('guard_name')
                    ->badge()
                    ->color('warning')
                    ->label(__('filament-shield::filament-shield.column.guard_name')),
                TextColumn::make('team.name')
                    ->default('Global')
                    ->badge()
                    ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                    ->label(__('filament-shield::filament-shield.column.team'))
                    ->searchable()
                    ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
                TextColumn::make('permissions_count')
                    ->badge()
                    ->label(__('filament-shield::filament-shield.column.permissions'))
                    ->counts('permissions')
                    ->colors(['success']),
                TextColumn::make('updated_at')
                    ->label(__('filament-shield::filament-shield.column.updated_at'))
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
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
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    public static function getCluster(): ?string
    {
        return Utils::getResourceCluster() ?? static::$cluster;
    }

    public static function getModel(): string
    {
        return Utils::getRoleModel();
    }

    public static function getModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament-shield::filament-shield.resource.label.roles');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-shield::filament-shield.nav.role.label');
    }

    public static function getNavigationIcon(): string
    {
        return __('filament-shield::filament-shield.nav.role.icon');
    }

    public static function getNavigationSort(): ?int
    {
        return -1;
    }

    public static function getSlug(?Panel $panel = null): string
    {
        return Utils::getResourceSlug();
    }

    public static function getNavigationBadge(): ?string
    {
        return true
            ? strval(static::getEloquentQuery()->count())
            : null;
    }

    public static function isScopedToTenant(): bool
    {
        return true;
    }

    public static function canGloballySearch(): bool
    {
        return false && count(static::getGloballySearchableAttributes()) && static::canViewAny();
    }
}
