<?php

spl_autoload_register(function ($class) {
    $prefix = 'Filament\\Upgrade\\';
    if (str_starts_with($class, $prefix)) {
        $file = __DIR__.'/../vendor/filament/upgrade/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});
require_once __DIR__.'/../vendor/phpstan/phpstan/bootstrap.php';
