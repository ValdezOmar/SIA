<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Spatie\Permission\Models\Role;

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
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DateTimePicker::make('email_verified_at'),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->hiddenOn('edit'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Roles y Permisos')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Roles asignados')
                            ->options(Role::all()->pluck('name', 'id'))
                            ->getOptionLabelUsing(fn ($value): string => Role::find($value)?->name ?? '')
                            ->searchable()
                            ->multiple()
                            ->preload()
                            ->relationship('roles', 'name')
                            ->hintIcon('heroicon-o-question-mark-circle')
                            ->hintColor('primary')
                            ->hint(function () {
                                return self::getRolesDescriptions();
                            }),
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
                    ->color(fn (string $state): string => match ($state) {
                        'Administrador' => 'danger',
                        'Directiva' => 'warning',
                        'Gerencia' => 'success',
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

    protected static function getRolesDescriptions(): string
    {
        $roles = [
            'Administrador' => 'Acceso completo al sistema. Puede leer, crear, modificar y eliminar cualquier registro.',
            'Directiva' => 'Puede leer, crear y modificar registros. No puede eliminar información crítica.',
            'Gerencia' => 'Puede leer, crear y modificar registros en su área de responsabilidad.',
            'Administracion Regional' => 'Puede leer, crear y modificar registros en su región asignada.',
            'Jefatura' => 'Puede leer, crear y modificar registros en su departamento.',
            'Operativo' => 'Puede leer y crear registros. Capacidad limitada de modificación.',
            'Empleado' => 'Solo acceso de lectura. No puede modificar ni crear registros.',
        ];

        $descriptions = [];
        foreach ($roles as $role => $desc) {
            $descriptions[] = "{$role}: {$desc}";
        }

        return implode('<br>', $descriptions);
    }
}