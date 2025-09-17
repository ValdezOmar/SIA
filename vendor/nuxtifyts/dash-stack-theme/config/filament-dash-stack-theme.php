<?php

use Illuminate\Support\Facades\DB;

$parametros = DB::table('conf_parametros')->first();

$primary = $parametros->color_principal ?? '#009BA4';
$secondary = $parametros->color_secundario ?? '#3066BE';

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