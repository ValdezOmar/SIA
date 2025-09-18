<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$primary = '#009BA4';   // valores por defecto
$secondary = '#3066BE';

if (Schema::hasTable('conf_parametros')) {
    $parametros = DB::table('conf_parametros')->first();

    if ($parametros) {
        $primary   = $parametros->color_principal ?? $primary;
        $secondary = $parametros->color_secundario ?? $secondary;
    }
}

return [
    'default-colors' => [
        'primary'   => $primary,
        'secondary' => $secondary,
    ],

    'side-bar-collapsable-on-desktop' => true,
    'collapsible-navigation-groups'   => true,
    'breadcrumbs'                     => false,

    /**
     * Nunito Sans is the default font for the theme.
     */
    'use-default-font' => true,
];