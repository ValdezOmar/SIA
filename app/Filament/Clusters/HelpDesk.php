<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class HelpDesk extends Cluster implements HasShieldPermissions
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $slug = 'help-desk';    
    protected static ?string $navigationLabel = 'Help Desk';
    protected static ?string $navigationGroup = 'Comercial';
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