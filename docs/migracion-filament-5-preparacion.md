# Migración a Filament 5

Fecha de revisión: 10 de septiembre de 2026.

## Estado de la migración

Actualizada el 10 de septiembre de 2026 en el árbol de trabajo:

| Componente | Antes | Después |
| --- | --- | --- |
| Filament | 4.13.1 | 5.8.1 |
| Livewire | 3.8.8 | 4.4.4 |
| `filament/upgrade` | 4.13.1 | 5.8.1 |
| Laravel | 12.69.2 | 12.69.2 |

La base tecnológica cumple los requisitos de Filament 5. El transformador oficial procesó los 287 archivos del directorio `app` y no encontró transformaciones adicionales necesarias. Laravel 12, PHP 8.3 y Tailwind 4 se mantienen sin cambios.

Filament 5 conserva en gran medida la API de Filament 4. El riesgo principal de este proyecto está en sus integraciones Livewire, sus vistas Blade con Alpine y el tema SIA que utiliza selectores internos de Filament.

## Orden de actualizaciones

1. **Filament 5 y Livewire 4 juntos**, manteniendo Laravel 12. Filament 5 admite Laravel 11.28 o superior, por lo que Laravel 12 es una base compatible.
2. **Laravel 13 después**, cuando el panel, las cargas, GPS, lector y flujos financieros hayan quedado estables sobre Filament 5.

Laravel 13 no desbloquea Filament 5. En cambio, su instalación actual está bloqueada por dependencias que todavía limitan Illuminate a Laravel 12: Dompdf, Socialite, Tinker, Google Calendar, IDE Helper, Pail, Sail y Collision. Esto produciría dos grupos de regresiones independientes y haría difícil identificar el origen de un error.

## Estado actual

| Elemento | Estado actual | Estado para Filament 5 | Evaluación |
| --- | --- | --- | --- |
| PHP | 8.3.20 | 8.2 o superior | Listo |
| Laravel | 12.69.2 | 11.28 o superior | Listo |
| Tailwind CSS | 4.1 | 4 o superior | Listo |
| Filament | 4.13.1 | 5.x | Pendiente |
| Livewire | 3.8.8 | 4.x | Bloqueante |
| Filament Shield | 4.3.1 | Declara compatibilidad con 4 y 5 | Validar roles y permisos |
| Filament Excel | 3.6.1 | Declara compatibilidad con 4 y 5 | Validar exportaciones |
| `designthebox/barcode-field` | 1.0.0 local | Declara solamente Filament 4 | Bloqueante |

## Cambios realizados

### Dependencias

La actualización se resolvió de forma limitada a Filament, Livewire y sus dependencias directas. No se ejecutó una actualización global de Composer. Shield 4.3.1 y Filament Excel 3.6.1 conservan sus versiones porque declaran compatibilidad con Filament 5.

El paquete local `designthebox/barcode-field` ahora admite explícitamente Filament 4 o 5. Su código utiliza `TextInput`, macros de `Field` y componentes Blade que siguen disponibles. Las pruebas JavaScript de escáner verifican lectura única, aislamiento entre campos, liberación de cámara y recuperación ante rechazo de permisos.

### Lector de códigos de barras

Se conservó la validación manual pendiente para el comportamiento físico de cámara y linterna en un navegador móvil, que no puede reproducirse desde la consola.

### Livewire 4 y recursos propios

Filament 5 requiere Livewire 4. Se revisaron los componentes propios que usan enlace directo de estado, eventos o actualización periódica:

- captura de GPS, selector y mapa de asistencia;
- resumen de pagos de facturas;
- widgets de escritorio y Análisis comercial;
- actualización de inventario y sus eventos;
- carga temporal de archivos de empleados y parámetros.

La aplicación no tiene un archivo `config/livewire.php` ni rutas Livewire personalizadas, por lo que no hay configuración publicada que migrar. Aun así, Livewire 4 cambia las URL de scripts, actualizaciones y carga temporal a prefijos que incorporan un hash derivado de `APP_KEY`; cualquier proxy, firewall o CDN que autorice explícitamente `/livewire/` deberá actualizarse.

Los cuatro usos de `TemporaryUploadedFile` y la configuración temporal de archivos siguen presentes en Livewire 4.4.4. Deben mantenerse en la matriz de pruebas funcionales: carga, guardado, borrado y validación de logos, imágenes y documentos.

Los enlaces estándar detectados (`wire:model.live`, `wire:click`, `wire:poll`, `wire:loading`, `wire:ignore`, `$wire.set` y `$wire.entangle`) no entran en los cambios incompatibles documentados. Sin embargo, requieren pruebas de navegador porque GPS, Leaflet y el lector de código de barras conservan estado JavaScript entre actualizaciones de Livewire. Livewire 4 mejora el polling para que no bloquee las demás solicitudes, lo que puede beneficiar los widgets y el resumen de pagos.

### Tema SIA

El tema personalizado contiene selectores de estructura interna de Filament (`.fi-*`): 91 en la fuente y 120 en el CSS publicado. Filament puede modificar esos nombres o su jerarquía. Deben verificarse visualmente, tanto claro como oscuro, el panel, barra superior, barra lateral expandida y contraída, login, tablas, formularios, modales, slide-overs, widgets y estados de carga.

### Superficie funcional

Hay 146 definiciones de formularios, tablas o infolists en los recursos y ocho widgets propios. Los flujos de mayor prioridad son inicio/cierre de sesión y permisos; ventas, pagos, entregas, anulaciones; inventario y Kardex; asientos contables; asistencia con GPS; archivos, imágenes de artículos y exportaciones.

## Validación ejecutada

Completado:

- `composer validate --strict`: archivo válido; queda solamente la advertencia conocida por la restricción exacta del paquete local.
- `php artisan filament:upgrade`, `filament:clear-cached-components`, `optimize:clear` y `filament:optimize`: correctos.
- Caché de vistas, rutas, configuración, iconos y componentes: correcta.
- Las 105 rutas del panel, incluidas login, roles, ventas, inventario, compras, contabilidad y RRHH, se registran correctamente.
- Pruebas unitarias: 5 correctas, 44 aserciones.
- Pruebas JavaScript: 8 correctas, incluidas las del lector de códigos y cálculo de ventas.

Pendiente de ejecutar en un PHP con SQLite habilitado:

- La suite Feature usa `sqlite` en memoria. El PHP de Laragon tiene disponibles `php_pdo_sqlite.dll` y `php_sqlite3.dll`, pero ambas extensiones están deshabilitadas en su `php.ini`; por ello la ejecución ordinaria de las 58 pruebas Feature termina con `could not find driver`.
- La migración no modifica datos ni la configuración de Laragon. Para ejecutar la suite completa se pueden habilitar temporalmente esos módulos para el proceso:

  ```powershell
  php -d extension="C:\laragon\bin\php\php-8.3.20-nts-Win32-vs16-x64\ext\php_pdo_sqlite.dll" -d extension="C:\laragon\bin\php\php-8.3.20-nts-Win32-vs16-x64\ext\php_sqlite3.dll" artisan test
  ```

La compilación Vite comenzó correctamente, pero quedó detenida durante `transforming` en esta sesión. El último paquete compilado existente se conserva en `public/build`; antes de desplegar, ejecutar `npm.cmd run build` en un proceso local sin bloqueo y revisar que finalice con éxito.

## Criterio de aceptación

La actualización estará lista para despliegue después de completar la suite Feature con SQLite, compilar los recursos y verificar visualmente el tema SIA, el login, barra lateral, formularios, modales, slide-overs y lector de códigos en escritorio y móvil.

## Referencias oficiales

- [Guía de actualización de Filament 5](https://filamentphp.com/docs/5.x/upgrade-guide)
- [Anuncio de Filament 5](https://filamentphp.com/community/filament-v5)
- [Guía de actualización de Livewire 4](https://livewire.laravel.com/docs/4.x/upgrading)
- [Requisitos e instalación de Livewire 4](https://livewire.laravel.com/docs/4.x/installation)
