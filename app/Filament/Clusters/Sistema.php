<?php

namespace App\Filament\Clusters;

use Filament\Clusters\Cluster;
use Filament\Navigation\NavigationItem;
use Filament\Pages\SubNavigationPosition; 

class Sistema extends Cluster
{
    protected static ?string $slug = 'configuracion';
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Sistema';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 2;
    // protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;   
}