# SIA — Documentación técnica

> Sistema Integral de Administración (SIA). Esta guía describe el estado actual del código fuente, su arquitectura, módulos, datos, seguridad y operación.

## 1. Propósito y alcance

SIA es una aplicación administrativa construida con Laravel y Filament. Centraliza la configuración organizacional, recursos humanos, control de asistencia, inventario/almacenes, compras, ventas y contabilidad.

El acceso operativo ocurre principalmente en el panel Filament **dashboard**, disponible en `/dashboard`. La raíz (`/`) redirige al panel.

## 2. Tecnología

| Capa | Tecnología |
| --- | --- |
| Backend | PHP 8.2+, Laravel 12 |
| Panel administrativo | Filament 3.3 |
| Autorización | Spatie Laravel Permission + Filament Shield |
| Base de datos | MySQL/MariaDB mediante `pdo_mysql` |
| Plantillas | Blade + componentes Livewire/Filament |
| Mapas | Leaflet (dependencia npm) |
| PDF | `barryvdh/laravel-dompdf` |
| Exportaciones | `pxlrbt/filament-excel` |
| Autenticación externa | Laravel Socialite / Google |
| Estilos del panel | DashStack Theme |

Dependencias y versiones declaradas: `composer.json`, `package.json`.

## 3. Arquitectura

```text
Navegador
  │
  ├─ /dashboard ── Filament DashboardPanelProvider
  │                  ├─ Resources y Clusters descubiertos automáticamente
  │                  ├─ Roles/permisos (Shield)
  │                  └─ Widgets y componentes Blade
  │
  └─ /auth/google/* ─ GoogleAuthController ─ GoogleAuthService

Resources/Pages ── Models Eloquent ── MySQL
                         │
                         ├─ Services/Inventario/TrazabilidadInventarioService
                         └─ Services/RRHH/AsistenciaHorarioService
```

### Directorios principales

| Ubicación | Responsabilidad |
| --- | --- |
| `app/Filament` | Panel, clusters, resources, páginas, relation managers, widgets y exportadores. |
| `app/Models` | Modelos Eloquent y relaciones del dominio. |
| `app/Services` | Reglas de negocio reutilizables: trazabilidad de inventario, asistencia y autenticación Google. |
| `app/Policies` | Políticas generadas/usadas por Shield para entidades del panel. |
| `app/Http` | Controlador de autenticación Google. |
| `database/migrations` | Esquema de base de datos, ordenado cronológicamente. |
| `database/seeders` | Semillas de parámetros, administrador y permisos/roles. |
| `resources/views` | Componentes Filament, mapas, widgets y plantillas PDF. |
| `config` | Configuración Laravel, Shield, permisos, servicios y colas. |
| `tests/Feature` | Pruebas de comportamiento para facturas de contado y valores por defecto del kardex. |

## 4. Panel Filament

El proveedor `app/Providers/Filament/DashboardPanelProvider.php` configura:

- ID de panel: `dashboard`.
- Prefijo de URL: `/dashboard`.
- Inicio de sesión nativo y proveedor de autenticación Google.
- Descubrimiento automático de clusters y resources en `app/Filament`.
- Grupos de navegación: Recursos Humanos, Contabilidad, Compras, Inventario, Ventas, Comercial, Almacenes y Configuración.
- Logo y favicon desde `public/images`.
- Notificaciones de base de datos con sondeo cada 30 segundos.
- Widget de Nextcloud y menú de acceso a “Mi Perfil”.

### Clusters

| Cluster | Ruta base | Contenido |
| --- | --- | --- |
| `Sistema` | `/dashboard/configuracion` | Parámetros generales, empresas, sucursales, áreas, cargos y horarios de asistencia. |
| `ParametrosInventario` | Bajo la navegación de Inventario | Almacenes, artículos maestros, atributos, fabricantes, grupos, listas de precios, ubicaciones y unidades de medida. |

## 5. Seguridad, usuarios y permisos

### Autenticación

- El modelo autenticable es `App\Models\User`.
- La autenticación se realiza con el guard `web`.
- Google OAuth expone:
  - `GET /auth/google/redirect` (`google.redirect`)
  - `GET /auth/google/callback` (`google.callback`)
- La implementación está en `GoogleAuthController` y `GoogleAuthService`.

### Shield y Spatie Permission

La configuración está en `config/filament-shield.php`:

- Recursos, widgets y permisos personalizados están habilitados.
- Los recursos de todos los paneles se descubren automáticamente (`discover_all_resources = true`).
- El superadministrador se denomina `super_admin`.
- Los permisos estándar de resources son: `view_any`, `view`, `create`, `update` y `delete`.
- `RoleResource` agrupa los permisos según el grupo de navegación real de cada resource y conserva su orden en el menú.

Los resources de Compras, Ventas, Contabilidad y Almacenes quedan disponibles en el formulario de roles por su grupo de navegación.

### Recursos de Configuración

| Resource | Función |
| --- | --- |
| `Configuracion/UserResource` | Crea y administra usuarios, roles y vínculo con empleados. |
| `Configuracion/RoleResource` | Administra roles y permisos Shield. |
| `Sistema/ParametroResource` | Configura identidad visual, zona horaria y métodos de acceso. |
| `Sistema/EmpresaResource` | Empresas legales y datos de contacto. |
| `Sistema/SucursalResource` | Sucursales asociadas a una empresa. |
| `Sistema/AreaResource` | Áreas organizacionales y sus relaciones. |
| `Sistema/CargoResource` | Cargos asociados a áreas. |

## 6. Módulos funcionales

### 6.1 Recursos Humanos

#### Empleados y vínculo laboral

`rh_empleados` contiene únicamente la identidad y datos personales: nombres, apellidos, CI, foto, fecha de nacimiento, domicilio/GPS, género, nacionalidad, contactos personales/emergencia, NUA/CUA, AFP y estado.

La información laboral no se duplica en el empleado. Se registra en `rh_historial_laboral`:

- Empresa, sucursal y cargo.
- Tipo y vigencia de contrato.
- Salario y seguro médico.
- Correo y teléfono corporativos.
- Documento de contrato, observaciones y estado activo.

La relación `Empleado::historialActivo()` representa el contrato vigente. La tabla de empleados muestra **Sin asignar** cuando un empleado no dispone de contrato activo; por tanto, no confunde esta situación con un contrato indefinido.

Resources:

- `EmpleadoResource`: alta y edición de datos personales, imagen, croquis y relación con historial laboral.
- `DirectorioResource`: consulta de empleados activos, cargo, empresa, sucursal, contacto corporativo y estado de vínculo.
- `PerfilEmpleadoResource`: perfil propio del empleado autenticado.

#### Asistencia

Tablas principales:

- `rh_asistencias`: marcaciones, hora, fecha, localización, equipo y visibilidad.
- `rh_horarios_asistencia`: definición de turnos.
- `rh_asignaciones_horario_asistencia`: asignación temporal de turnos por empleado.

`AsistenciaHorarioService` resuelve el horario efectivo considerando asignaciones, vigencias y turno predeterminado. Evalúa situaciones como puntualidad, retraso, falta, omisión, descanso y ausencia de horario. No depende de una hora fija codificada en la interfaz.

El resource **Horarios de asistencia**, dentro del cluster Sistema, permite configurar entradas, salidas, almuerzo, tolerancias, días laborables y empleados asignados. Las asignaciones activas no pueden solaparse en fechas para el mismo empleado.

Las vistas `gps-location`, `gps-map`, `map-picker` y `asistencia-datafield` apoyan las marcaciones y ubicación geográfica. El reporte de asistencia se genera desde `resources/views/exports/asistencias-pdf.blade.php`.

### 6.2 Inventario y almacenes

Entidades principales:

| Área | Tablas/modelos |
| --- | --- |
| Maestro de artículos | `alm_articulos`, grupos, fabricantes, unidades, atributos, códigos de barras e imágenes. |
| Estructura física | almacenes, ubicaciones, existencias y existencias por ubicación. |
| Trazabilidad | kardex, movimientos de inventario, lotes, series, capas de costo y movimientos de lotes/series. |
| Precios | listas de precios y precios por artículo. |

Resources:

- `ArticuloResource`: ficha completa del artículo y relation managers para atributos, códigos, existencias, imágenes, kardex, lotes, precios, proveedores, series y unidades.
- `StockAlmacenResource`: consulta de stock por almacén.
- `KardexResource`: historial de entradas y salidas.
- `Almacen/InventarioResource`: toma y ajustes de inventario, widget de estadísticas y exportaciones.
- Cluster `ParametrosInventario`: mantenimiento de la estructura maestra de inventario.

`TrazabilidadInventarioService` es el servicio central que registra y revierte entradas/salidas, manteniendo kardex, existencias, lotes, series y capas de costo. Compras y ventas lo invocan desde sus modelos de negocio.

Los reportes PDF de inventario están en:

- `resources/views/exports/inventario-pdf.blade.php`
- `resources/views/exports/ajustes-inventario-pdf.blade.php`

### 6.3 Compras

Flujo principal:

```text
Solicitud → Cotización de proveedor → Orden de compra → Recepción → Factura de compra → Pago a proveedor
                                                     └─ registra entrada y trazabilidad de inventario
```

Resources del grupo Compras:

- Proveedores.
- Solicitudes de compra y detalles.
- Órdenes de compra y sus recepciones/facturas relacionadas.
- Recepciones y detalles.
- Facturas de compra y detalles.
- Pagos a proveedores.

Tablas del módulo usan el prefijo `cmp_`. Las recepciones generan el movimiento de entrada en inventario mediante el servicio de trazabilidad.

### 6.4 Ventas

Flujo principal:

```text
Cliente → Cotización → Pedido → Factura → Pago
                                 └─ registra salida y trazabilidad de inventario
```

Resources del grupo Ventas:

- Clientes.
- Cotizaciones y detalles.
- Pedidos y detalles.
- Facturas, detalles y pagos.

El modelo de factura gestiona fechas y pagos automáticos para operaciones de contado. Las notas de crédito están modeladas en `ven_notas_credito`.

### 6.5 Contabilidad

Resources del grupo Contabilidad:

- Plan de cuentas.
- Centros de costo.
- Proyectos.
- Asientos contables.

Tablas principales:

- `con_plan_cuentas`
- `con_centros_costos`
- `con_proyectos`
- `con_asientos_contables` y `con_asientos_detalle`
- `con_saldos_cuentas`
- `con_periodos_contables`
- `con_tipos_cambio`

Los resources aplican filtros por empresa y sucursal cuando corresponden al usuario autenticado.

## 7. Modelo de datos y convenciones

### Prefijos de tablas

| Prefijo | Dominio |
| --- | --- |
| `rh_` | Recursos Humanos. |
| `conf_` | Configuración organizacional. |
| `alm_` | Inventario, almacenes y maestros de artículos. |
| `cmp_` | Compras. |
| `ven_` | Ventas. |
| `con_` | Contabilidad. |

### Relaciones clave

```text
Empresa ──< Sucursal
Área ──< Cargo
Empresa >──< Área

Empleado ──< HistorialLaboral >── Empresa/Sucursal/Cargo
Empleado ──< Asistencia
Empleado ──< AsignacionHorarioAsistencia >── HorarioAsistencia

Artículo ──< Existencias/Kardex/Lotes/Series/Precios
Proveedor ──< OrdenCompra ──< Recepción ──< FacturaCompra ──< PagoProveedor
Cliente ──< Cotización ──< Pedido ──< Factura ──< Pago
```

Las restricciones, índices y claves foráneas son la fuente definitiva del esquema y están definidas en `database/migrations`.

## 8. Rutas HTTP

| Ruta | Acción |
| --- | --- |
| `/` | Redirige a `/dashboard`. |
| `/dashboard/*` | Panel Filament autenticado. |
| `/auth/google/redirect` | Inicia OAuth con Google. |
| `/auth/google/callback` | Recibe y procesa el callback OAuth. |
| `/up` | Health check de Laravel. |

Para listar todas las rutas registradas:

```bash
php artisan route:list
```

## 9. Archivos de configuración relevantes

| Archivo | Uso |
| --- | --- |
| `config/filament-shield.php` | Shield, permisos, descubrimiento y superadministrador. |
| `config/permission.php` | Integración de Spatie Permission. |
| `config/services.php` | Credenciales de servicios externos, incluido Google. |
| `config/filesystems.php` | Discos para fotos, contratos y archivos. |
| `config/queue.php` | Conexión y ejecución de colas. |
| `config/database.php` | Conexiones de base de datos. |
| `.env` | Credenciales y secretos locales; no debe versionarse. |

## 10. Instalación y puesta en marcha

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan optimize:clear
php artisan serve
```

Configure en `.env` al menos:

- `APP_URL`, `APP_ENV`, `APP_KEY`.
- Variables `DB_*` de MySQL/MariaDB.
- Variables de correo si se usa notificación por email.
- Credenciales OAuth de Google y URL de callback si se habilita ese acceso.

Para desarrollo de frontend:

```bash
npm run dev
```

Para una compilación de producción:

```bash
npm run build
```

## 11. Operación y mantenimiento

### Cachés

Después de modificar configuración, resources, vistas o permisos:

```bash
php artisan optimize:clear
```

### Permisos Shield

Para generar permisos de resources del panel:

```bash
php artisan shield:generate --panel=dashboard --option=permissions --all --no-interaction
```

Para publicar la configuración/recurso de Shield, la forma correcta usa el ID de panel como argumento:

```bash
php artisan shield:publish dashboard
```

### Migraciones

```bash
php artisan migrate:status
php artisan migrate
```

No edite una migración que ya fue aplicada en producción para cambiar una tabla existente: cree una migración nueva y reversible. Las migraciones originales documentan la estructura base para instalaciones nuevas.

### Colas

El proyecto incluye tablas para jobs, batches, imports, exports y filas fallidas. En producción se recomienda ejecutar un worker persistente:

```bash
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

Puede administrarse mediante Supervisor o el gestor de procesos de la infraestructura.

## 12. Pruebas y calidad

Pruebas existentes:

- `tests/Feature/FacturaContadoTest.php`
- `tests/Feature/KardexDefaultsTest.php`

Ejecución:

```bash
php artisan test
vendor/bin/pint --test
```

Las pruebas configuradas usan SQLite. El entorno de ejecución debe tener habilitado `pdo_sqlite`; el entorno local revisado dispone de `pdo_mysql` pero no de `pdo_sqlite`, por lo que esas pruebas no pueden ejecutarse allí hasta instalar/habilitar la extensión.

## 13. Convenciones de desarrollo

- Mantener los Resources en el namespace/carpeta funcional correspondiente para que Filament los descubra.
- Declarar `navigationGroup`, etiquetas e iconos en cada resource para que el menú y Shield mantengan la misma jerarquía.
- Ubicar datos laborales en `rh_historial_laboral`, no en `rh_empleados`.
- Usar `historialActivo` al consultar la asignación laboral vigente de un empleado.
- Encapsular cambios de stock en `TrazabilidadInventarioService`; no modificar existencias/kardex de manera aislada.
- No eliminar migraciones aplicadas ni tablas con datos sin una migración de transición aprobada.
- Ejecutar `php artisan optimize:clear` y pruebas relevantes después de cambios estructurales.

## 14. Documentos complementarios

- `README.md`: notas históricas y diagramas iniciales del proyecto.
- `Arquitectura.md`: resumen histórico del flujo FIFO/inventario.
- Esta guía (`DOCUMENTACION_TECNICA.md`): referencia técnica actual y centralizada.
