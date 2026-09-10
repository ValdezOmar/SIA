<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class Sistema extends Cluster
{
    protected static ?string $slug = 'configuracion';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Sistema';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 2;
    
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