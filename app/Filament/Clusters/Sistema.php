<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class Sistema extends Cluster implements HasShieldPermissions
{
    protected static ?string $slug = 'configuracion';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Sistema';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 2;
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;   
    //Evitar que Shield genere permisos para este cluster
    protected static function getPermissionPrefix(): string
    {
        return ''; // Sin prefijo = no genera permisos
    }

    public static function getPermissionPrefixes(): array
    {
        return []; // Lista vacía = Shield no genera permisos
    }
}