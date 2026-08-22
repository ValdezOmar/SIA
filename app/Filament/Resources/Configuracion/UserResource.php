<?php

namespace App\Filament\Resources\Configuracion;

use App\Filament\Resources\Configuracion\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de acceso')
                ->description('Estos datos permiten identificar e iniciar sesión en el sistema.')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre completo')
                        ->placeholder('Ej. María Pérez')
                        ->autofocus()
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('email')
                        ->label('Correo corporativo')
                        ->placeholder('nombre@empresa.com')
                        ->email()
                        ->helperText('Debe coincidir con el correo corporativo del empleado para enlazar su perfil.')
                        ->unique(ignoreRecord: true)
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->revealable()
                        ->helperText(fn (string $operation): string => $operation === 'create'
                            ? 'Mínimo 8 caracteres. El usuario podrá cambiarla después.'
                            : 'Déjela vacía para conservar la contraseña actual.')
                        ->required(fn (string $operation): bool => $operation === 'create')
                        ->minLength(8)
                        ->maxLength(255)
                        ->dehydrated(fn (?string $state): bool => filled($state)),
                ])->columns(2),

            Forms\Components\Section::make('Nivel de acceso')
                ->description('El rol define a qué módulos y acciones puede acceder esta persona.')
                ->schema([
                    Forms\Components\Select::make('roles')
                        ->label('Rol del usuario')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->minItems(1)
                        ->maxItems(1)
                        ->default(fn (): array => self::getDefaultRoleId())
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        ->helperText('Seleccione un único rol. Por defecto se asigna Empleado.')
                        ->columnSpanFull(),
                    Forms\Components\Placeholder::make('role_description')
                        ->label('Acceso seleccionado')
                        ->content(fn (Get $get): string => self::getRoleDescription($get('roles')))
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('empleado.foto_url')
                    ->label('')
                    ->circular()
                    ->getStateUsing(fn (User $record): ?string => $record->empleado?->foto_url)
                    ->defaultImageUrl(asset('images/default-avatar.jpg'))
                    ->width(40)
                    ->height(40)
                    ->tooltip(fn (User $record): string => $record->empleado ? 'Perfil de empleado vinculado' : 'Sin perfil de empleado vinculado'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record): string => $record->email)
                    ->icon('heroicon-o-envelope')
                    ->iconColor('gray'),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Nivel de acceso')
                    ->badge()
                    ->color(fn (?string $state) => match (true) {
                        in_array($state, ['Super Admin', 'Administrador De Sistema', 'super_admin']) => 'danger',
                        in_array($state, ['Gerencia', 'Directiva', 'Administrador', 'Encargado Regional', 'Recursos Humanos']) => 'warning',
                        $state === 'Administracion Regional' => 'info',
                        $state === 'Empleado' => 'success',
                        in_array($state, ['Almacenes', 'Comercial', 'Licitaciones', 'Soporte Técnico', 'Operativo']) => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('empleado.full_name')
                    ->label('Perfil de empleado')
                    ->getStateUsing(fn (User $record): string => $record->empleado?->full_name ?? 'Sin vincular')
                    ->description(fn (User $record): string => $record->empleado?->historialActivo?->cargo?->nombre ?? 'El correo aún no coincide con un empleado')
                    ->icon(fn (User $record): string => $record->empleado ? 'heroicon-o-link' : 'heroicon-o-exclamation-circle')
                    ->iconColor(fn (User $record): string => $record->empleado ? 'success' : 'warning')
                    ->color(fn (User $record): string => $record->empleado ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Último cambio')
                    ->since()
                    ->dateTimeTooltip()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->label('Nivel de acceso')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
                Tables\Filters\TernaryFilter::make('perfil_empleado')
                    ->label('Perfil de empleado')
                    ->placeholder('Todos')
                    ->trueLabel('Vinculado')
                    ->falseLabel('Sin vincular')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('empleado'),
                        false: fn (Builder $query): Builder => $query->whereDoesntHave('empleado'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->tooltip('Editar usuario'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name')
            ->emptyStateHeading('Aún no hay usuarios')
            ->emptyStateDescription('Cree un usuario para darle acceso al sistema.')
            ->emptyStateIcon('heroicon-o-users');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'roles',
            'empleado.historialActivo.cargo',
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    /** @return array<int, string> */
    protected static function getDefaultRoleId(): array
    {
        $roleId = Role::query()->where('name', 'Empleado')->value('id');

        return $roleId ? [(string) $roleId] : [];
    }

    /** @param array<int, string>|string|null $roleIds */
    protected static function getRoleDescription(array|string|null $roleIds): string
    {
        $roleId = is_array($roleIds) ? reset($roleIds) : $roleIds;
        $roleName = $roleId ? Role::find($roleId)?->name : null;

        return match ($roleName) {
            'super_admin' => 'Acceso total al sistema, incluida su configuración.',
            'Administrador' => 'Administra usuarios, configuración y operaciones del sistema.',
            'Directiva' => 'Consulta información ejecutiva y gestiona decisiones de alto nivel.',
            'Gerencia' => 'Supervisa su área, aprobaciones y reportes departamentales.',
            'Administracion Regional' => 'Gestiona la operación y reportes de su región.',
            'Jefatura' => 'Coordina equipos y las operaciones diarias de su área.',
            'Operativo' => 'Registra y consulta la información necesaria para sus tareas.',
            'Empleado' => 'Acceso básico para consultar y realizar las tareas autorizadas.',
            null => 'Seleccione un rol para ver el nivel de acceso que se asignará.',
            default => "Acceso definido por el rol {$roleName}.",
        };
    }
}
