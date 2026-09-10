# MigraciÃ³n de SIA a Filament 4

## Base y alcance

Base de reversiÃ³n del cÃ³digo: `b70fca6fdaeaeb70507448e5d4671a4c44708af0`.
El Ã¡rbol de trabajo estaba limpio al iniciar la migraciÃ³n. La pÃ¡gina de anÃ¡lisis
comercial y la eliminaciÃ³n de su widget forman parte de esa base.

Antes de convertir el cÃ³digo: **63 pruebas PHP, 404 aserciones y 4 pruebas JavaScript correctas**.
PHP local: 8.3.20, con `pdo_sqlite` y `sqlite3` habilitados Ãºnicamente para ejecutar pruebas.
No se ejecutan migraciones, regeneraciÃ³n de permisos ni cambios sobre la base operativa.

## Personalizaciones que deben conservarse

| PersonalizaciÃ³n | UbicaciÃ³n / estrategia |
| --- | --- |
| Totales, descuentos, IVA, listas de precios y escritura sin espera | `app/Forms/Components/CalculoRepeater.php`, `ImporteVenta.php`, `app/Support/CalculoDetalle.php` y pruebas PHP/JS. Conservar validaciÃ³n y recÃ¡lculo al guardar. |
| Apariencia del panel | DashStack original portado a Filament 4 desde `packages/dash-stack-theme`; conserva la configuracion visual de Sistema. |
| Nombres de permisos existentes y asignaciones | Mantener el formato histÃ³rico; no regenerar ni renombrar permisos de la base de datos. |
| Roles agrupados por menÃº y traducciones | `RoleResource` propio y traducciones en `lang/vendor/filament-shield/es`. |
| Login Google y credenciales, perfil de empleado | Conservar rutas y opciones configurables del login. |
| Fechas comerciales, pagos, contabilidad, inventarios y trazabilidad | Conservar reglas descritas en las guÃ­as de mÃ³dulos y verificar regresiones. |
| AnÃ¡lisis comercial | Mantener la pÃ¡gina registrada y el widget retirado. |

La versiÃ³n `1.0.0` de Barcode Field es una adaptaciÃ³n local de SIA instalada desde `packages/*` mediante un repositorio Composer de tipo `path`.

## Requisitos y comprobaciones

- PHP 8.2+, Laravel 11.28+; SIA conserva Laravel 12.
- Filament 4 y Livewire 3 compatibles, resueltos en `composer.lock`.
- Tailwind CSS 4.1+ y compilaciÃ³n Vite desde `package-lock.json`.
- Extensiones PHP requeridas por `composer check-platform-reqs`; SQLite para pruebas.
- Comprobar cambios con la [guÃ­a oficial](https://filamentphp.com/docs/4.x/upgrade-guide).

Comandos de regresiÃ³n (Windows):

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit
npm.cmd test
npm.cmd run build
composer validate --no-check-publish
composer check-platform-reqs
```

## Despliegue y reversiÃ³n

Validar primero una copia de staging con la misma versiÃ³n de PHP, base de datos,
discos y colas que producciÃ³n. Comprobar login real de Google, permisos por rol,
dispositivos de cÃ¡mara/lector, mapas y PDF. Los servicios externos y los dispositivos
fÃ­sicos no quedan certificados por las pruebas automatizadas.

Respaldar base de datos y archivos antes del despliegue. Preparar una nueva release
con sus dependencias y assets compilados; conservar la release anterior para
revertir el cÃ³digo, el lock y los assets juntos. No ejecutar `shield:setup`,
`shield:generate --all`, ni regenerar polÃ­ticas durante el despliegue.

El repositorio ya versiona `vendor` y `node_modules`: la actualizaciÃ³n de esas carpetas
produce un diff amplio. Las personalizaciones se mantienen en cÃ³digo propio; no editar
dependencias generadas como mecanismo de mantenimiento.

## Resultado posterior

Pendiente de completar al finalizar la validaciÃ³n de esta migraciÃ³n.

### DashStack y configuración visual

DashStack parte del codigo original y se adapta a las clases de Filament 4 en
`packages/dash-stack-theme/resources/css/theme.css`. El plugin toma
`color_principal` y `fondo_path` de los parámetros del módulo Sistema. El color se
usa como paleta primaria de Filament y en la navegación; el fondo se usa en el
login con una versión basada en la fecha del archivo para actualizarse después de
cada carga. Los valores por defecto mantienen el panel disponible mientras no
exista una configuración guardada.

### Tema nativo de SIA

El paquete DashStack fue retirado. Su configuración visual de referencia quedó
registrada en `docs/dashstack-660bc7c-referencia.md` y se implementa con el
tema propio `resources/css/filament/dashboard/theme.css`, cargado directamente
por el panel de Filament 4. Sistema > Parámetros Generales controla color
principal, color secundario, escala de interfaz, estilo y fondo de login, logo
y favicon. La migración `2026_09_09_000003_add_panel_appearance_to_conf_parametros.php`
incorpora los nuevos campos sin modificar la configuración existente.
## Validacion completada - 10 de septiembre de 2026

La migracion queda operativa con Filament 4 y el tema nativo de SIA. DashStack ya no es una dependencia del panel.

Se validaron pagos contado y mixto, reservas, entregas, Kardex, reversiones, stock negativo, servicios, fechas y asientos. La migracion `2026_09_07_000002_add_logo_path_to_conf_empresas.php` es idempotente cuando la columna ya existe sin registro en la tabla de migraciones.

Ver [actualizacion de septiembre de 2026](actualizacion-2026-09-10.md).

