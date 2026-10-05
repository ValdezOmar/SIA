# Mapa y memoria del proyecto

Revisión: 5 de octubre de 2026. Base: código, dependencias bloqueadas, rutas, proveedores, pruebas y documentación del repositorio. Es una revisión de arquitectura y continuidad, no una auditoría exhaustiva de cada línea ni de datos productivos.

## Plataforma real

SIA es un monolito Laravel para configuración organizacional, RRHH, compras, ventas, inventario y contabilidad. El panel Filament tiene ID `dashboard` y URL `/dashboard`; `/` redirige al panel. Los Resources y Clusters se descubren desde `app/Filament`.

| Componente | Referencia verificada |
| --- | --- |
| Laravel | `composer.lock`: 13.31.0; `composer.json`: ^13.0 |
| Filament | 5.8.1; restricción ~5.0 |
| Livewire | 4.4.4 |
| PHP local | CLI Laragon 8.3.20; revisar también requisitos transitivos del lock al desplegar |
| Frontend | Blade, Livewire, Alpine, Tailwind 4, Vite 6; `vite.config.js` define CSS, app.js y lector |
| Autorización | Spatie Permission y Filament Shield; `LegacyShieldPermissions` conserva claves existentes |
| Datos | Configuración MySQL/MariaDB; pruebas PHP con SQLite en memoria |
| Exportaciones | Dompdf, Filament Excel; contactos VCF y conteos CSV |
| Paquete propio | `packages/barcode-field`, repositorio Composer path con copia, no symlink |

El README raíz describe el stack vigente; las notas originales están archivadas en `docs/historico/README-original.md`. Las notas de septiembre sobre Laravel 12 y migraciones reflejan otro momento. El tema es propio de SIA: CSS activo en `public/css/sia-filament-theme.css`, cargado por `sia-panel-theme.blade.php` mediante render hook. El proveedor no usa `viteTheme()`. No recuperar DashStack modificando vendor.

## Dónde trabajar

| Necesidad | Archivos y guía inicial |
| --- | --- |
| Panel, navegación, login y widgets | `app/Providers/Filament/DashboardPanelProvider.php`, `app/Filament/Widgets`, `resources/views/filament` |
| Parámetros y apariencia | `app/Filament/Clusters/Sistema/Resources/ParametroResource.php`, sus Pages, `app/Models/Sistema/Parametro.php`, `app/Support/PanelAppearance.php`, `app/Providers/AppServiceProvider.php` |
| Empresas, sucursales, áreas y cargos | `app/Filament/Clusters/Sistema/Resources`, `app/Models/Sistema` |
| Usuarios y roles | `app/Filament/Resources/Configuracion`, `app/Models/User.php`, `app/Policies`, `config/filament-shield.php`, `app/Support/LegacyShieldPermissions.php` |
| RRHH y asistencia | `docs/rrhh.md`, `app/Models/RRHH`, `app/Filament/Resources/RRHH`, `app/Services/RRHH/AsistenciaHorarioService.php`; horarios en cluster Sistema |
| Ventas | `docs/ventas.md`, `app/Models/Ventas`, `app/Filament/Resources/Ventas`, `app/Services/Ventas` |
| Proforma PDF de cotizaciones | `app/Services/Ventas/CotizacionPdfService.php`, `resources/views/exports/cotizacion-pdf.blade.php`; acción `imprimir_pdf`, logo desde `Parametro::logo_path` |
| Formularios e importes | `app/Forms/Components/CalculoRepeater.php`, `ImporteVenta.php`, `app/Support/CalculoDetalle.php`, `resources/js`, `tests/js/ventas-importes.test.cjs` |
| Compras y moneda | `docs/compras-multimoneda-y-costos.md`, `app/Models/Compras`, `app/Filament/Resources/Compras` |
| Catálogo, stock y Kardex | `docs/inventario.md`, `app/Models/Inventario`, `app/Filament/Resources/Inventario`, cluster `ParametrosInventario`, `app/Services/Inventario/TrazabilidadInventarioService.php` |
| Conteos físicos y PDF | `docs/inventarios-fisicos.md`, `app/Filament/Resources/Almacen`, `app/Services/Inventario/InventarioFisicoService.php`, `InventarioPdfService.php` |
| Contabilidad | `docs/contabilidad.md`, `app/Models/Contabilidad/AsientoContable.php`, Resources de Contabilidad, `app/Services/Contabilidad/RegularizacionKardexService.php` |
| OAuth y fotos | `routes/web.php`, `app/Http/Controllers/Auth/GoogleAuthController.php`, `app/Services/GoogleAuthService.php`, `EmpleadoFotoController.php` |
| Errores operativos | `app/Services/Sistema/NotificacionExcepcionOperativaService.php`, registro Livewire en `AppServiceProvider.php` |
| Esquema y semillas | `database/migrations`, `database/seeders`; prefijos `conf_`, `rh_`, `alm_`, `cmp_`, `ven_`, `con_` |

## Reglas que deben conservarse

- Los modelos contienen operaciones de negocio; no son simples contenedores. `Factura::registrarPago`, `procesarVentaAutomatica` y `anular`, `Kardex::registrarMovimiento`, `Recepcion::procesarEntradaInventario` y `AsientoContable::confirmar/anular` son puntos iniciales para investigar efectos y transacciones.
- Usuario y contexto empresarial se relacionan con empleado e historial laboral activo mediante correo corporativo. Revisar `User`, `HistorialLaboral` y `ScopesEmpresa`; los alcances no son idénticos en todos los módulos.
- Reserva compromete stock, entrega lo descuenta. El pago completo procesa entrega en el flujo actual. Servicios no inventariables no deben exigir almacén ni generar salidas físicas.
- `Pedido::cancelar()` anula todas sus facturas activas mediante `Factura::anular()`, revierte pagos/contabilidad e inventario correspondiente y libera reservas en una sola transacción. El modal exige aceptar consecuencias irreversibles desde la interfaz. Anular un pago no ejecuta una devolución bancaria ni de efectivo.
- Stock negativo es opcional por almacén, exige valoración y no elimina controles de series/lotes.
- Compras conservan moneda negociada; inventario y asientos se valorizan en BOB. Gastos capitalizables se prorratean; los no capitalizables no tienen aún toda la imputación contable documentada.
- Totales se recalculan al guardar. Descuento se resta una vez; impuesto se calcula sobre neto. Revisar coherencia de PHP y JavaScript.
- `fecha_emision` es venta; `fecha_vencimiento` representa entrega en el esquema actual. Fecha de pago, fecha contable y auditoría son independientes. Períodos cerrados deben seguir bloqueando operaciones.
- Asientos confirmados se reutilizan para evitar duplicación; regularizar Kardex no implica reescribir fechas históricas.
- Cerrar inventario físico no ajusta stock automáticamente. Ajustes se registran por Kardex. Bitácora de conteos conserva auditoría.
- RRHH evalúa primera marcación y horario efectivo; no atribuirle nómina u horas extra no implementadas. Integración biométrica se describe como herramienta Python externa.
- Parámetros se consumen en arranque para timezone y Google; apariencia tiene valores de respaldo. Nunca copiar secretos de configuración a memoria.

## Verificación dirigida

En PowerShell usar `npm.cmd` y `npx.cmd` si los wrappers .ps1 causan problemas.

```powershell
git status --short
php artisan test --testsuite=Unit
php artisan test --filter=FacturaContadoTest
npm.cmd test
npm.cmd run build
php vendor/laravel/pint/builds/pint --test
```

`phpunit.xml` fuerza `APP_ENV=testing`, SQLite `:memory:` y `DB_URL` vacío. Comprobar extensiones con `php -m`; si faltan SQLite, habilitarlas solo para el proceso con `php -d extension=pdo_sqlite -d extension=sqlite3 artisan test`. No cambiar a la base real para ejecutar Feature. Revisar caché de configuración antes de pruebas.

Elegir Feature por dominio: `VentaFechasTest`, `FacturaContadoTest`, `PagoMixtoTest`, `VentaServiciosTest`, `InventarioNegativoTest`, `InventarioFisicoTest`, `KardexAccountingIntegrationTest`, `FacturaCompraFlujoTest`, `TotalesDocumentosTest`, `WidgetsPermissionTest`. Las pruebas JS cubren cálculo y escáner. Cámara, GPS, OAuth y apariencia requieren comprobación de navegador/dispositivo.

## Cómo mantener la memoria

Guardar aquí arquitectura y decisiones vigentes, en la guía del módulo sus flujos y en `CONTINUIDAD.md` el estado de trabajo. Indicar evidencia y fecha; distinguir hecho verificado, decisión y pendiente. No guardar conversaciones enteras, secretos ni listas de archivos generados. Si el código cambia, corregir la memoria en el mismo trabajo.

Compartir proformas: acción compartir_whatsapp en CotizacionResource, modal resources/views/filament/ventas/compartir-cotizacion.blade.php; ruta autenticada cotizaciones.pdf en routes/web.php y CotizacionPdfController. Web Share comparte el archivo desde el dispositivo; alternativa descarga y chat. Sin publicación anónima ni cambio de estado.
