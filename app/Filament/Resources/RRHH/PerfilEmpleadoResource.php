<?php

namespace App\Filament\Resources\RRHH;

use App\Models\RRHH\Empleado;
use Filament\Resources\Resource;
use App\Filament\Resources\RRHH\PerfilEmpleadoResource\Pages;

class PerfilEmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;
    protected static ?string $modelLabel = 'Perfil Empleado';
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Mi Perfil';
    protected static ?int $navigationSort = -1;

    // Este recurso no necesita listar ni crear registros
    public static function shouldRegisterNavigation(): bool
    {
        return True;
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
        ];
    }
    //Busca parametro de empleado
    public static function getNavigationUrl(): string
    {
        $empleado = auth()->user()->empleado; // Asegúrate de tener esta relación definida
        return static::getUrl('edit', ['record' => $empleado?->getKey()]);
    }
}
