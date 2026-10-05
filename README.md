# SIA — Sistema Integral de Administración

Aplicación para configuración organizacional, RRHH, asistencia, compras, ventas, inventario y contabilidad. El panel administrativo está en `/dashboard`.

## Documentación y memoria

- [Guías de módulos](docs/README.md).
- [Documentación técnica](DOCUMENTACION_TECNICA.md).
- [Instrucciones para agentes](AGENTS.md), [mapa del proyecto](docs/memoria/PROYECTO.md) y [continuidad](docs/memoria/CONTINUIDAD.md).
- [Claude Mem: instalación y pendientes](docs/memoria/CLAUDE_MEM.md).
- [Notas históricas archivadas](docs/historico/README-original.md).

## Stack vigente

Revisado el 5 de octubre de 2026 contra `composer.lock` y las dependencias instaladas:

| Componente | Versión |
| --- | --- |
| PHP | 8.3 o superior, requerido por Laravel 13; CLI local 8.3.20 |
| Laravel | 13.31.0 |
| Filament / Livewire | 5.8.1 / 4.4.4 |
| Frontend | Blade, Alpine, Tailwind 4 y Vite 6 |
| Base de datos | MySQL/MariaDB; SQLite en memoria para pruebas |
| Permisos | Filament Shield y Spatie Permission |

El lector de códigos se carga con Vite. El tema del panel se sirve desde `public/css/sia-filament-theme.css` mediante `resources/views/filament/components/sia-panel-theme.blade.php`; revisar también las fuentes en `resources/css/filament/dashboard`. DashStack fue retirado y no debe editarse en vendor.

## Instalación local nueva

Instalar dependencias desde los archivos lock y configurar una base local antes de migrar. Estos pasos son para una instalación nueva; conservar la configuración y los datos en una instalación existente.

```powershell
composer install
npm.cmd ci
Copy-Item .env.example .env
php artisan key:generate
# Configurar APP_URL, DB_* y correo en .env antes del siguiente paso.
php artisan migrate --seed
php artisan storage:link
npm.cmd run build
php artisan serve
```

Revisar los seeders antes de ejecutarlos. La configuración de Google y la zona horaria también pueden provenir de Parámetros Generales. No registrar credenciales en documentación ni memoria.

## Desarrollo y comprobación

```powershell
npm.cmd run dev
npm.cmd test
php -d extension=pdo_sqlite -d extension=sqlite3 artisan test
```

`phpunit.xml` fuerza SQLite en memoria para pruebas PHP. Cámara, GPS, OAuth y apariencia requieren comprobación en navegador. La captura biométrica ZKTeco está documentada como una integración Python externa.

Consultar la documentación técnica para colas, cachés y despliegue, y las guías de módulos para reglas de stock, pagos, fechas y contabilidad.
