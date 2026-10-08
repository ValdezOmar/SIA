<?php

return [
    'enabled' => env('AUDITORIA_ENABLED', true),
    // Infraestructura volátil, no movimientos comerciales.
    'tablas_excluidas' => ['sis_auditorias', 'migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches'],
    'niveles' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
];
