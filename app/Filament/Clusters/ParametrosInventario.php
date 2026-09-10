<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;

class ParametrosInventario extends Cluster
{
    protected static ?string $slug = 'parametros inventario';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Parámetros de Inventario';
    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';
    protected static ?int $navigationSort = 5;
    
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