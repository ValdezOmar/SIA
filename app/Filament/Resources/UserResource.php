<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;
use Illuminate\Support\HtmlString;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $modelLabel = 'Usuarios';
    protected static ?string $pluralModelLabel = 'Listado de Usuarios';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->hint("El email es muy importante ya que se enlazara al Empleado")
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn(string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn(?string $state): bool => filled($state))
                            ->hiddenOn('edit'),
                    ])->columns(1),

                Forms\Components\Section::make('Roles y Permisos')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles asignados')
                            ->options(Role::all()->pluck('name', 'id'))
                            ->getOptionLabelUsing(fn($value): string => Role::find($value)?->name ?? '')
                            ->searchable()
                            ->multiple()
                            ->preload()
                            ->relationship('roles', 'name')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Placeholder::make('roles_description')
                                    ->label("Descripción de roles")
                                    ->content(fn() => self::getRolesDescriptions())
                                    ->columnSpanFull()
                            ])
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Administrador' => 'danger',
                        'super_admin' => 'danger',
                        'Directiva' => 'warning',
                        'Empleado' => 'success',
                        'Administracion Regional' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('roles')
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    protected static function getRolesDescriptions(): HtmlString
    {
        $roles = [
            '👑 Administrador' => [
                'description' => 'Gestion total • Configuración sistema • Gestión usuarios • Todos los módulos',
                'color' => 'border-indigo-500 dark:border-indigo-400',
                'bg' => 'bg-indigo-50 dark:bg-indigo-900/20'
            ],
            '🏢 Directiva' => [
                'description' => 'Gestion total (sin eliminación crítica) • Reportes ejecutivos • Datos sensibles',
                'color' => 'border-amber-500 dark:border-amber-400',
                'bg' => 'bg-amber-50 dark:bg-amber-900/20'
            ],
            '👔 Gerencia' => [
                'description' => 'Gestión de área • Aprobaciones • Reportes departamentales • Supervisión',
                'color' => 'border-emerald-500 dark:border-emerald-400',
                'bg' => 'bg-emerald-50 dark:bg-emerald-900/20'
            ],
            '🌎 Regional' => [
                'description' => 'Gestión regional • Reportes locales • Coordinación regional • Operaciones',
                'color' => 'border-blue-500 dark:border-blue-400',
                'bg' => 'bg-blue-50 dark:bg-blue-900/20'
            ],
            '📋 Jefatura' => [
                'description' => 'Gestión de equipo • Aprobación docs • Métricas • Operaciones diarias',
                'color' => 'border-purple-500 dark:border-purple-400',
                'bg' => 'bg-purple-50 dark:bg-purple-900/20'
            ],
            '🛠️ Operativo' => [
                'description' => 'Registro actividades • Consulta • Edición limitada • Solicitudes',
                'color' => 'border-cyan-500 dark:border-cyan-400',
                'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'
            ],
            '👤 Empleado' => [
                'description' => 'Solo lectura • Visualización docs • Sin edición • Acceso restringido',
                'color' => 'border-cyan-500 dark:border-cyan-900',
                'bg' => 'bg-cyan-50 dark:bg-cyan-900/20'
            ],
        ];

        $html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
        
        foreach ($roles as $role => $details) {
            $html .= '
            <div class="rounded-lg p-3 '.$details['bg'].' border-l-4 '.$details['color'].' shadow-sm">
                <div class="font-semibold text-gray-900 dark:text-white">'.$role.'</div>
                <div class="text-sm text-gray-600 dark:text-white">'.$details['description'].'</div>
            </div>';
        }
        
        $html .= '</div>';

        return new HtmlString($html);
    }
}