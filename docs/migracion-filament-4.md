> Documento histórico de la migración de septiembre. El estado vigente es Laravel 13, Filament 5 y Livewire 4; consultar [memoria](memoria/PROYECTO.md). Las referencias al paquete DashStack describen fases anteriores; el CSS activo es `public/css/sia-filament-theme.css` mediante render hook.

# Migración de SIA a Filament 4

## Base y alcance

Base de reversión del código: `b70fca6fdaeaeb70507448e5d4671a4c44708af0`.
El árbol de trabajo estaba limpio al iniciar la migración. La página de análisis
comercial y la eliminación de su widget forman parte de esa base.

Antes de convertir el código: **63 pruebas PHP, 404 aserciones y 4 pruebas JavaScript correctas**.
PHP local: 8.3.20, con `pdo_sqlite` y `sqlite3` habilitados únicamente para ejecutar pruebas.
No se ejecutan migraciones, regeneración de permisos ni cambios sobre la base operativa.

## Personalizaciones que deben conservarse

| Personalización | Ubicación / estrategia |
| --- | --- |
| Totales, descuentos, IVA, listas de precios y escritura sin espera | `app/Forms/Components/CalculoRepeater.php`, `ImporteVenta.php`, `app/Support/CalculoDetalle.php` y pruebas PHP/JS. Conservar validación y recálculo al guardar. |
| Apariencia del panel | DashStack original portado a Filament 4 desde `packages/dash-stack-theme`; conserva la configuracion visual de Sistema. |
| Nombres de permisos existentes y asignaciones | Mantener el formato histórico; no regenerar ni renombrar permisos de la base de datos. |
| Roles agrupados por menú y traducciones | `RoleResource` propio y traducciones en `lang/vendor/filament-shield/es`. |
| Login Google y credenciales, perfil de empleado | Conservar rutas y opciones configurables del login. |
| Fechas comerciales, pagos, contabilidad, inventarios y trazabilidad | Conservar reglas descritas en las guías de módulos y verificar regresiones. |
| Análisis comercial | Mantener la página registrada y el widget retirado. |

La versión `1.0.0` de Barcode Field es una adaptación local de SIA instalada desde `packages/*` mediante un repositorio Composer de tipo `path`.

## Requisitos y comprobaciones

- PHP 8.2+, Laravel 11.28+; SIA conserva Laravel 12.
- Filament 4 y Livewire 3 compatibles, resueltos en `composer.lock`.
- Tailwind CSS 4.1+ y compilación Vite desde `package-lock.json`.
- Extensiones PHP requeridas por `composer check-platform-reqs`; SQLite para pruebas.
- Comprobar cambios con la [guía oficial](https://filamentphp.com/docs/4.x/upgrade-guide).

Comandos de regresión (Windows):

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit
npm.cmd test
npm.cmd run build
composer validate --no-check-publish
composer check-platform-reqs
```

## Despliegue y reversión

Validar primero una copia de staging con la misma versión de PHP, base de datos,
discos y colas que producción. Comprobar login real de Google, permisos por rol,
dispositivos de cámara/lector, mapas y PDF. Los servicios externos y los dispositivos
físicos no quedan certificados por las pruebas automatizadas.

Respaldar base de datos y archivos antes del despliegue. Preparar una nueva release
con sus dependencias y assets compilados; conservar la release anterior para
revertir el código, el lock y los assets juntos. No ejecutar `shield:setup`,
`shield:generate --all`, ni regenerar políticas durante el despliegue.

El repositorio ya versiona `vendor` y `node_modules`: la actualización de esas carpetas
produce un diff amplio. Las personalizaciones se mantienen en código propio; no editar
dependencias generadas como mecanismo de mantenimiento.

## Resultado posterior

La validación de esta fase quedó registrada en la sección final del documento.

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

