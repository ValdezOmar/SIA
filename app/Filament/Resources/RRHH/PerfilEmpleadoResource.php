<?php

namespace App\Filament\Resources\RRHH;

use App\Models\RRHH\PerfilEmpleado;
use Filament\Resources\Resource;
use App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class PerfilEmpleadoResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = PerfilEmpleado::class;
    protected static ?string $modelLabel = 'Perfil del empleado'; //Seccion para configurar el nombre en Filament-Shield
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Mi Perfil';
    protected static ?int $navigationSort = -1;

    // Este recurso no necesita listar ni crear registros
    public static function shouldRegisterNavigation(): bool
    {
        $user = Auth::user();
        return PerfilEmpleado::where('correo_corporativo', $user->email)->exists();
    }

    //Busca parametro de empleado
    public static function getNavigationUrl(): string
    {
        $empleado = Auth::user()->empleado;
        return static::getUrl('edit', ['record' => $empleado?->getKey()]);
    }

    // Prefijo de premisos
    protected static function getPermissionPrefix(): string
    {
        return 'mi_perfil_';
    }
    //Rutas de dominio
    public static function getPages(): array
    {
        return [
            'edit' => Pages\EditPerfilEmpleado::route('/{record}/edit'),
            'index' => Pages\EditPerfilEmpleado::route('/'),
            'view' => Pages\ViewPerfilEmpleado::route('/{record}'),
        ];
    }
    //Permisos personalizados de filament shield
    public static function getPermissionPrefixes(): array
    {
        return [
            'view_any',    // los permisos del Shield usuales                   
            'update',
        ];
    }
}
