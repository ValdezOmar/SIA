<?php

namespace App\Filament\Widgets\Concerns;

use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

trait HasWidgetPermission
{
    use HasWidgetShield {
        canView as protected canViewWithShieldPermission;
    }
}
