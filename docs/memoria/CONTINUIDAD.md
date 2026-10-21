# Continuidad entre sesiones y modelos

## 2026-10-05 — Preparación de memoria compartida

- Solicitud: evitar exploraciones repetidas del proyecto y facilitar relevo entre modelos; instalar Claude Mem.
- Árbol de trabajo inicialmente limpio. HEAD observado: `ddf2611c2` (estilos oscuros de login).
- Revisados inventario de archivos propios, dependencias, documentación de módulos, configuración de pruebas, rutas, proveedores, contexto de empresa, parámetros y puntos de operación de modelos financieros.
- Creada memoria compartida: `AGENTS.md`, `CLAUDE.md`, `PROYECTO.md` y este registro.
- Identificadas notas históricas contradictorias sobre stack, Vite y DashStack. El lock actual contiene Laravel 13.31.0, Filament 5.8.1 y Livewire 4.4.4.
- La revisión no constituye auditoría línea por línea ni validación de datos de producción.
- Instalado Claude Mem 13.31.0 para Claude Code y Codex CLI, con proveedor Claude y sincronización cloud desactivada. Worker verificado por `/api/health`: `status=ok`, `initialized=true`, `mcpReady=true`.
- Guardado estado inicial del proyecto en Claude Mem mediante `/api/work-state/entries`, lista `memoria-proyecto`, con referencias a la memoria versionada y stack vigente.
- Actualizados README, índice de guías y stack de documentación técnica. Validación: `git diff --check` correcto y enlaces locales de memoria existentes. No se ejecutaron suites funcionales porque no se modificó código de aplicación.
- Pendiente: abrir sesión nueva, habilitar hooks desde el cliente cuando lo solicite y validar captura/compresión con autenticación Claude funcional. El wrapper Claude de OpenClaw en PATH apunta a un ejecutable inexistente. Detalles en `CLAUDE_MEM.md`.

## 2026-10-05 — Corrección de inconsistencias

- README vigente con notas originales archivadas en `docs/historico/README-original.md`.
- Corregidas versiones, Vite, carga real del CSS por render hook, widgets, conteos físicos y responsabilidades de Kardex/trazabilidad. Notas de migración distinguen estado actual y validaciones históricas; reparada codificación de Filament 4.
- Retirada declaración duplicada de la ruta raíz; se conserva redirección a `/dashboard`.
- Reparado ejecutable Claude mediante `node install.cjs` del paquete de OpenClaw ya instalado. `claude --version`: 2.1.285. `claude auth status`: `loggedIn=false`; pendiente iniciar sesión con `claude auth login` y habilitar hooks en nueva sesión.
- Sintaxis PHP correcta. `artisan route:list` no finalizó y se canceló; no se acredita arranque completo ni suite funcional.
- Verificación aislada con Router Laravel: una sola ruta raíz, RedirectController y redirección 302 a `/dashboard`. `git diff --check` correcto.

## Formato para próximos relevos

## 2026-10-05 — Pago previo a confirmar entrega de pedidos

- `Pedido::confirmarEntrega()` bloquea pedido/factura en transacción, recalcula deuda desde pagos confirmados y exige pago completo cuando existe saldo. Usa el flujo existente `Factura::registrarPago()` para cobro, contabilidad y entrega; un fallo revierte el cobro nuevo.
- Acción `confirmar_entrega` de PedidoResource muestra factura/saldo, opción para registrar un pago nuevo con fecha, medio y referencias, explicación previa y notificación persistente de efectos. Solo confirma pedidos pendientes/reservados con autorización de edición del resource.
- La deuda revisada es la factura del pedido; no es un bloqueo por todas las deudas del cliente. Varias facturas activas requieren revisar asociaciones; no se selecciona silenciosamente la última. Abonos parciales y confirmación de pagos existentes continúan en la factura.
- Actualizados `docs/ventas.md` y descripción de pestaña de pagos. Nuevas pruebas en `tests/Feature/PedidoEntregaTest.php`.
- Validación: PedidoEntregaTest, 6 pruebas/29 aserciones, incluido modal Livewire y notificación; PagoMixtoTest, 4 pruebas/19 aserciones. Sintaxis PHP, Pint de archivos afectados y `git diff --check` correctos. No se ejecutó suite completa ni comprobación manual de navegador.

## 2026-10-05 — Cancelación de pedido con anulación de ventas

- `Pedido::cancelar(motivo)` valida estado y motivo, bloquea pedido/facturas, anula todas las facturas activas con el flujo existente `Factura::anular()` y libera reservas en una sola transacción. Conserva el motivo en observaciones y devuelve documentos, cantidad de pagos e importes confirmados revertidos por moneda.
- Modal de PedidoResource advierte acción peligrosa sin opción de deshacer, presenta documentos afectados, exige aceptar consecuencias y muestra notificación persistente al terminar. Aclara que anular el registro del cobro no devuelve dinero automáticamente al cliente.
- Nuevas pruebas de cancelación en PedidoEntregaTest: pago/asiento anulados, pedido sin factura, bloqueo de reintento, aceptación y notificación del modal, reversión completa cuando falla una segunda factura.
- Validación ejecutada: 9 pruebas/61 aserciones de PedidoEntregaTest, más prueba nueva aislada de rollback (1 prueba/6 aserciones), todas correctas. Sintaxis PHP, Pint de los tres archivos PHP y `git diff --check` correctos. Sin cambios en base operativa ni validación manual de navegador.

Registrar fecha, objetivo, archivos afectados, decisiones con motivo, comandos y resultados reales, limitaciones y siguiente acción concreta. Conservar pendientes hasta resolverlos; no trasladar conclusiones antiguas como pruebas actuales.

## 2026-10-05 — Proforma PDF desde cotizaciones

- Añadida acción `imprimir_pdf` (Imprimir PDF) en CotizacionResource. Servicio CotizacionPdfService genera/descarga con Dompdf y respeta autenticación, autorización de consulta y alcance empresarial del resource.
- Plantilla A4 `exports/cotizacion-pdf` basada en referencia del usuario: logo de parámetros, código, título PROFORMA, datos comerciales, fecha, tabla, total con impuestos y nota de validez. Total desde líneas guardadas; exportación no modifica datos. PNG/JPG/SVG locales admitidos, rutas limitadas a public/images y logo general como respaldo.
- Validación: CotizacionPdfTest, 5 pruebas/13 aserciones correctas (descarga Livewire, logo configurado, acceso, exportación sin cambios, impuestos/descuentos y documento largo). Revisadas visualmente la muestra de 1 página y las 4 páginas de una cotización larga, renderizadas con Windows.Data.Pdf; sin cortes ni superposiciones. PyMuPDF no estaba disponible y su instalación falló por DNS; se usó el renderizador nativo sin depender de él en la aplicación.
- Actualizadas guía de ventas y mapa. No se alteró la base operativa ni se ejecutó la suite completa.

## 2026-10-05 — Desglose y vencimiento de la proforma

- Resumen PDF con subtotal antes de descuentos, impuesto, descuento y total. Se recupera el bruto sumando descuentos al subtotal neto guardado; evita descontar dos veces. Total desde líneas guardadas.
- Vencimiento visible (`fecha_validez`, o emisión + 7 días si falta) y observaciones condicionales con saltos de línea.
- Ajuste posterior: retirado pie de página; añadida sucursal de la cotización en datos de empresa (sin asignación: texto explícito). Servicio carga la relación `sucursal`. CotizacionPdfTest actualizado: 5 pruebas/20 aserciones correctas; muestra actual revisada visualmente y ejemplo PDF reemplazado. Pint y diff sin errores.
- Ajuste posterior: columna Concepto sustituida por Foto de catálogo (`Articulo::foto_catalogo`), incrustada desde disco public con límite de tamaño y validación de ruta/tipo. Sin archivo válido: Sin foto. Miniaturas de hasta 48 px mantienen proporciones. CotizacionPdfTest: 6 pruebas/24 aserciones correctas; prueba de foto repetida después de compactar el diseño (1 prueba/4 aserciones). Muestra con imágenes de prueba renderizada y revisada en una página. Ejemplo actualizado con imágenes ilustrativas; Pint y diff correctos.
- CotizacionPdfTest: 5 pruebas/18 aserciones correctas. Revisión visual del documento de una página y página final del documento largo (4 páginas), sin cortes ni superposición en totales/observaciones. Ejemplo PDF actualizado. No se modificaron datos operativos.

## 2026-10-05 — Compartir proforma por WhatsApp

- Acción `compartir_whatsapp` en Cotizaciones: prepara el PDF al abrir el modal y permite compartir el archivo con Web Share al pulsar el botón. El usuario selecciona WhatsApp/destinatario y confirma allí; no se afirma envío ni cambia el estado comercial.
- Alternativa: descargar PDF y abrir chat con mensaje y celular del cliente (o selector si no hay número válido); adjuntar manualmente. Números locales de ocho dígitos reciben 591.
- Ruta autenticada `cotizaciones.pdf` (`/ventas/cotizaciones/{cotizacion}/pdf`) en CotizacionPdfController, con controles del servicio existente y cache private/no-store. No se publica un enlace de acceso anónimo.
- Web Share exige HTTPS/navegador compatible. Sin envío real ni prueba del menú nativo en teléfono; pendientes de comprobación en el dispositivo del usuario.
- Validación ejecutada: php vendor/phpunit/phpunit/phpunit --filter=CotizacionPdfTest: 8 pruebas/35 aserciones correctas (PDF autenticado, alcance empresarial del servicio, modal y conservación de datos). Pint --test en controlador, rutas y prueba correcto; php -l CotizacionResource y git diff --check sin errores. Sin suite completa ni comprobación de envío real.

## 2026-10-05 — Corrección de descarga desde Compartir

- Descargar PDF en el modal usa el File ya preparado, URL de objeto y atributo download con nombre .pdf; no navega a la ruta ni reemplaza el panel por contenido binario. Se deshabilita mientras se prepara el archivo.
- CotizacionPdfController devuelve bytes con Content-Type application/pdf, Content-Disposition attachment, Content-Length, nosniff y cache private/no-store. Conserva controles de acceso del servicio.
- Se conserva el ajuste del usuario: acción llamada Compartir y retirada de imprimir_pdf independiente. La prueba anterior de esa acción se adapta a la descarga autenticada vigente.
- Verificación: CotizacionPdfTest y Pint ejecutados; resultado consignado a continuación. Pendiente comprobar descarga desde el navegador del usuario; no se envió ningún mensaje.
- Resultado: 8 pruebas/40 aserciones correctas; Pint --test en controlador y prueba correcto. diff --check detectó un espacio en blanco preexistente de la edición del usuario del Resource, retirado sin alterar la acción.

## 2026-10-12 — Prospectos y clientes reimplementados sobre versión estable

- Base de trabajo: commit estable `b5b07266f104eb3b8a1a171ed1096fa273c65911` (PDF de cotizaciones). Se añadieron códigos PRO sin separador adicional (`PRO-26001`); el formato CLI existente se conserva (`CLI-26001`). Sin columna/migración nueva.
- Alta rápida desde Cotización y su Relation Manager crea un prospecto; la ficha Clientes muestra badge y filtro por prefijo. El celular existente reutiliza la misma persona y no cambia su estado a prospecto.
- Al crear factura de venta se promueve el mismo registro PRO a CLI; sus documentos conservan `cliente_id`. Prueba añadida `tests/Feature/ProspectoClienteTest.php` para formato, conversión y reutilización.
- Actualizados `docs/ventas.md` y este registro. `php -l` en los 7 PHP modificados pasa, Pint `--test` en la prueba nueva pasa y `git diff --check` pasa. Pint detecta reglas de formato preexistentes en seis archivos base; no se formatearon para evitar cambios ajenos. `php artisan test --filter=ProspectoClienteTest --stop-on-failure` permaneció sin salida por más de un minuto y se interrumpió; prueba funcional pendiente. No se ejecutaron migraciones ni se conectó a la base operativa.

## 2026-10-12 — Correlativos PRO/CLI por máximo numérico

- Corregido `Cliente::generarCodigo()`: antes elegía el registro de mayor `id` y tomaba sus últimos tres dígitos, lo que podía retroceder el código si el orden de inserción no seguía el correlativo. Ahora obtiene el mayor sufijo numérico dentro del prefijo anual correspondiente y suma uno; `PRO-YYNNN` y `CLI-YYNNN` mantienen secuencias independientes e incluye soft deletes.
- Prueba añadida con registros cuyo orden por `id` contradice el correlativo, comprobando que CLI 253 produce 254 y PRO 002 produce 003.
- Verificación: `php -l` en `Cliente.php` y `ProspectoClienteTest.php`, Pint `--test` en la prueba y `git diff --check` pasan. PHPUnit inició, pero permaneció sin salida durante más de un minuto y se interrumpió; no se acredita resultado funcional. Sin migraciones ni consultas a datos operativos.

## 2026-10-12 — Logo correcto en PDF de cotización

- `CotizacionPdfService` ahora prioriza `logo_path` de la empresa asociada a la cotización (disco público `empresas/logos`), luego usa el `logo_path` configurado en Sistema. Se quitó el fallback automático a `public/images/logo.png` cuando no corresponde a un parámetro vigente, para no insertar marca ajena. Rutas locales se validan por raíz real, MIME y tamaño máximo de 2 MB.
- Añadida prueba donde el logo de la empresa y el del sistema son imágenes distintas, para verificar prioridad empresarial. Actualizada guía de ventas.
- `php -l` en servicio y prueba, Pint `--test` en ambos archivos y `git diff --check` pasan. PHPUnit del caso quedó sin salida por más de un minuto y se interrumpió; comprobación funcional pendiente. Sin cambios de esquema o base operativa.

## 2026-10-12 — Compactación de PDF de cotización

- Reducidos márgenes de página, espacios del encabezado/empresa/fecha, relleno de filas, tipografías auxiliares y tamaño máximo de fotos, sin retirar campos ni alterar cálculos.
- Render de muestra con 36 artículos mediante Dompdf: versión previa 4 páginas; plantilla compacta 2 páginas. Revisión visual de las dos páginas: títulos/encabezado repetido, filas, totales y observaciones legibles y sin cortes.
- `git diff --check` pasa. El render de muestra usa datos sintéticos; no se modifican datos de aplicación. Los artefactos de comparación quedaron en `tmp/pdfs/` porque la política automática bloqueó su limpieza con PowerShell.

## 2026-10-13 — Fecha de registro y serie de factura en Kardex del artículo

- En `KardexPorAlmacenRelationManager`, la columna de fecha ahora usa `created_at` y no agrega la fecha contable como descripción. Los filtros y el orden por `fecha_movimiento` se mantienen para conservar la consulta histórica por fecha del movimiento.
- La columna Documento agrega el campo `serie` de `ven_facturas` al número guardado en `documento_codigo` para movimientos tipo venta; otros tipos no cambian. No se alteraron modelos, esquema ni datos.
- Guía de inventario actualizada. `php -l`, Pint `--test` y `git diff --check` pasan. No se ejecutó PHPUnit ni prueba de UI.

## 2026-10-13 — Detalle y mapa de relaciones en historial Kardex del artículo

- El documento de venta del historial usa tres líneas: número de factura (ej. `FAC-260125`), serie y `VENTA: <id>`. Se añade la relación `Kardex::facturaVenta()` y se precarga factura, cliente, artículo, almacén y movimiento relacionado.
- **Operación realizada** combina el tipo de movimiento con la dirección entrada/salida. El modal muestra cliente, factura, serie, fecha de emisión y total para ventas.
- Añadido mapa gráfico con nodos conectados de venta → movimiento Kardex → artículo → almacén y movimiento relacionado si está asociado. Actualizada guía de inventario.
- `php -l` en modelo y Relation Manager, Pint `--test` en Relation Manager y `git diff --check` pasan. Pint `--test` del modelo Kardex falla por reglas de formato preexistentes (orden de imports, espacios unarios y finales CRLF); el diff del modelo conserva solo la importación y la relación nuevas. No se ejecutó prueba de interfaz ni PHPUnit. Sin migraciones ni cambios de datos.

## 2026-10-14 — Error de permisos al cargar imágenes de parámetros

- Los campos Logo Principal, Favicon y Fondo de Login de `ParametroResource` copiaban cargas a archivos dentro de `public/images`, que no deben ser destino de escritura en una instalación desplegada. Ahora los tres usan el disco público de Laravel bajo `branding/`; la vista previa resuelve archivos nuevos y rutas públicas antiguas.
- `PanelAppearance` resuelve rutas antiguas y relativas del disco público; el panel lee de esa configuración para logo, favicon y fondo. `CotizacionPdfService` ya soportaba rutas relativas del disco público. Actualizados mapa y continuidad; sin cambios de esquema ni datos.
- `php -l` pasa en los cuatro PHP modificados; Pint `--test` pasa en PanelAppearance y la prueba. Pint del Resource y del PanelProvider señala reglas de formato ya presentes (CRLF/estilo general e imports); se mantuvieron los cambios acotados. PHPUnit focalizado quedó sin salida durante el arranque y se interrumpió; validación funcional pendiente. `git diff --check` pasa.

## 2026-10-17 — Precarga de costo unitario en alta manual de Kardex

- `KardexResource` ahora propone el `ultimo_costo` de Existencias según artículo y almacén; si es cero/no existe, busca la última entrada confirmada. Al cambiar artículo o almacén actualiza la propuesta y recalcula el costo total. `Costo unitario` sigue editable.
- `KardexResourceCreateTest` comprueba la precarga de Bs 47,25 y que el usuario pueda sustituirla por Bs 35,50 al guardar. Resultado: 1 prueba/12 aserciones correctas. PHP lint, Pint de la prueba y `git diff --check` pasan. Pint del Resource existente falla por formato previo (atributos, sangría, CRLF); no se reformató el archivo completo.

## 2026-10-19 - Auditoria del historial de precios por articulo

- Anadida migracion `alm_precios_articulos_historial` y eventos Eloquent en `PrecioArticulo`: registra alta, cambio de precio/lista y eliminacion con valores anterior/nuevo, usuario y fecha/hora. Guarda snapshots de articulo y listas sin claves foraneas, para conservar evidencia al eliminar los registros vigentes.
- Anadido el Relation Manager de solo lectura **Auditoria de precios** a Articulos, visible cuando existe la tabla. La tabla permite filtrar por accion y ordena los cambios mas recientes primero. Actualizados modelo, prueba `PrecioArticuloHistorialTest` y guia `docs/inventario.md`.
- Verificacion ejecutada: PHPUnit `tests/Feature/PrecioArticuloHistorialTest.php` correcto (2 pruebas/22 aserciones); `php -l` en los siete archivos PHP modificados correcto; Pint `--test` en los cinco archivos nuevos/formateados correcto; `git diff --check` correcto. No se ejecutaron migraciones ni operaciones contra la base operativa.
- Limitacion: los cambios registrados mediante SQL directo o actualizaciones Eloquent masivas que omiten eventos de modelo no generan auditoria; editar/eliminar desde el Resource o guardar el modelo si dispara los eventos.
## 2026-10-19 - Auditoria de precios tambien a nivel de base de datos

- Para cerrar la brecha de las escrituras Eloquent masivas y SQL directo, se agrego `2026_10_19_000001_create_alm_precios_articulos_audit_triggers.php`: triggers AFTER INSERT/UPDATE/DELETE para MySQL y SQLite escriben snapshots de precio/lista/articulo. Los eventos del modelo ya no duplican filas; enriquecen la ultima fila con usuario autenticado. Triggers de borrado de articulo/lista cubren el borrado en cascada (con control de duplicados en SQLite). Sin contexto de usuario queda `Sistema (BD)`.
- La prueba focalizada ahora ejercita un `DB::table()->update()` y verifica que se registra el precio previo/nuevo. PHPUnit: 2 pruebas/27 aserciones correctas, incluidos borrados en cascada de articulo y lista. `php -l` en los siete PHP y Pint `--test` en cinco archivos nuevos/formateados correctos; `git diff --check` correcto. Pint del modelo `PrecioArticulo` se omite porque ese archivo ya tenía formato previo fuera del cambio y no se reformateó.
- La migracion de triggers exige soporte de triggers en el motor y privilegio `CREATE TRIGGER` en MySQL. No ejecutada en la base operativa. Los triggers auditables de precio cubren DML de la tabla; las operaciones DDL/TRUNCATE no disparan triggers de fila.
- Claude Mem: el observador rechazado informa `Claude Code reported the observer as signed out`; ademas, el wrapper local `claude.ps1` apunta a un `claude.exe` inexistente. No se reinicio el worker, conforme al aviso de que reiniciar no corrige credenciales. Se actualizo `docs/memoria/CLAUDE_MEM.md`; requiere reparar la instalacion del CLI y login interactivo antes de iniciar/verificar el worker.
## 2026-10-19 - Auditoria dentro de la vista del precio

- Se retiro la pestaña hermana `HistorialPreciosRelationManager` del Article Resource. La accion `Ver auditoria` ahora esta directamente en cada fila del Relation Manager `PreciosRelationManager`; abre el detalle de ese precio y una RepeatableEntry en modo tabla con fecha, accion, lista/precio anterior y nuevo, usuario.
- `PrecioArticulo::historial()` limita el contenido al ID del precio seleccionado, mientras los snapshots por articulo siguen conservados para auditoria aunque el registro vigente se elimine. La prueba verifica la relacion y existencia de la accion en la fila. `PrecioArticuloHistorialTest`: 2 pruebas/25 aserciones correctas. `php -l` en Resource, Relation Manager, modelo y prueba; Pint `--test` en la prueba y `git diff --check` pasan. La prueba de Livewire confirma la acción en cada fila; pendiente verificación visual del modal en navegador. Pint del modelo PrecioArticulo informa problemas de estilo en el archivo, por lo que no se reformateó. No se cambia la lógica ni migraciones de auditoría.
## 2026-10-19 - Formulario de edicion y resumen del precio

- En `PreciosRelationManager`, las secciones y campos del formulario de precio ocupan todo el ancho y se apilan en una columna; solo EditAction se amplio a `7xl` (CreateAction mantiene su ancho). En edicion se agrego una tarjeta de indicadores apilados: precio vigente/lista, variacion absoluta/porcentual respecto del valor anterior, numero de registros auditados y fecha/usuario de la ultima actividad. Se oculta al crear un precio nuevo.
- La prueba de historial confirma la disponibilidad de las acciones ver y editar en la fila. `PrecioArticuloHistorialTest` debe volver a ejecutarse para validar este ajuste. La tarjeta escapa el contenido dinamico y consulta la bitacora por relacion del precio actual. Sin migraciones ni cambios de negocio.
## 2026-10-19 — Auditoría al final del menú de acciones de precio

- La acción `Ver auditoría` quedó dentro del menú `Acciones` de cada precio, al final de la lista, después de `Editar` y `Eliminar`. Se conserva el panel lateral con tabla de historial e indicadores del precio.
- Verificación ejecutada: `php -l app/Filament/Resources/Inventario/ArticuloResource/RelationManagers/PreciosRelationManager.php` correcto; `php vendor/bin/phpunit tests/Feature/PrecioArticuloHistorialTest.php --stop-on-failure`: 2 pruebas/26 aserciones correctas; `git diff --check` correcto (solo aviso de normalización CRLF preexistente en `ArticuloResource.php`).
- Pendiente: comprobación visual manual en navegador.
## 2026-10-20 — Solicitudes de traspaso con aceptación en destino

- Las nuevas transferencias se guardan y envían como solicitud pendiente al crear. Se quitó la ruta/página de edición; la tabla filtra solicitudes pendientes sin salida Kardex y tiene acciones de aceptar, rechazar con motivo obligatorio y anular.
- Por compatibilidad con el `enum` histórico de estados, la solicitud pendiente usa `en_transito` sin movimientos Kardex; las transferencias históricas `en_transito` con salida permanecen fuera de la bandeja. Aceptar cambia a `recibida` y registra salida en origen + entrada en destino con costo/trazabilidad y vínculo de movimientos dentro de transacción. Rechazar usa `rechazada`; anular conserva el registro, lo cierra como `borrador` y no mueve stock.
- Cambios en Resource, modelo y página Create, eliminado EditTransferenciaAlmacen, prueba `TransferenciaAlmacenSolicitudTest` y guía `docs/inventario.md`. No se ejecutó migración ni se accedió a la BD operativa.
- Verificación: `php vendor/bin/phpunit tests/Feature/TransferenciaAlmacenSolicitudTest.php --stop-on-failure`: 3 pruebas/15 aserciones correctas; `php -l` en PHP afectados correcto; Pint `--test` pasa en prueba y página Create. Pint señala estilo/CRLF preexistente en Resource y modelo, que no se reformatearon para limitar el diff. `git diff --check` correcto con advertencias de normalización CRLF de Git.
- Pendiente: validación visual de acciones por receptor/creador y revisar la regla de si anular debe permitirse a algún rol adicional.
## 2026-10-20 — Acciones de traspaso visibles solo al receptor

- Se restringieron las acciones Aceptar, Rechazar y Anular a la persona identificada en `receptor_id` (responsable del almacén destino). El control está en la visibilidad del Resource y también en el modelo; se eliminó la excepción administrativa y del creador.
- Actualizadas `docs/inventario.md` y la prueba `TransferenciaAlmacenSolicitudTest`, que comprueba que usuarios distintos al receptor no pueden ejecutar ninguna acción.
- Verificación: pendiente de ejecutar en esta actualización.
## 2026-10-20 — Acciones de traspaso en menú de tres puntos

- Se agruparon Aceptar traspaso, Rechazar traspaso y Anular solicitud en un `ActionGroup` con icono de tres puntos y tooltip Acciones. Se conserva la condición de que solo el receptor asignado puede verlas.
- La guía de inventario describe el nuevo menú. `php -l` del Resource correcto; `TransferenciaAlmacenSolicitudTest`: 4 pruebas/20 aserciones correctas; `git diff --check` correcto (aviso habitual CRLF de Git).
## 2026-10-20 — Correlativo anual de códigos de traspaso

- `TransferenciaAlmacen::generarCodigo()` genera `TRA-YYNNNN`, toma el máximo sufijo numérico de cuatro dígitos del año actual y suma uno; ignora códigos de formato histórico como `TRA-YYYYMMDD-His-XXXX` y de otros años. Los códigos ya guardados no se modifican.
- Prueba ampliada en `TransferenciaAlmacenSolicitudTest`: primer código, continuación después de `0009` y exclusión del año anterior. PHPUnit: 5 pruebas/22 aserciones correctas; `php -l`, Pint `--test` de la prueba y `git diff --check` correctos (aviso CRLF de Git).
## 2026-10-20 — Vista de detalle y resolución del traspaso

- Se añadió `Ver traspaso` al grupo de tres puntos. Abre un slide-over de solo lectura con código, origen/destino, solicitante, responsable, fecha, observaciones y tabla de artículos (código, descripción, cantidad, series y lotes). La persona receptora tiene los botones Aceptar y Rechazar dentro del pie de la vista; Rechazar solicita motivo. Anular sigue disponible en el menú exterior.
- La prueba Livewire abre la acción de vista y comprueba información de artículo y botones. `TransferenciaAlmacenSolicitudTest`: 6 pruebas/30 aserciones correctas; `php -l` Resource y prueba; Pint `--test` prueba correcto tras ordenar imports; `git diff --check` correcto con aviso CRLF habitual.

## 2026-10-20 — Observaciones de resolución y anulación por creador

- Aceptar y rechazar permiten guardar observaciones opcionales en `observaciones_resolucion`, conservando `observaciones` como nota original y `motivo_rechazo` como motivo obligatorio. Migración aditiva `2026_10_20_120000_add_observaciones_resolucion_to_alm_transferencias_almacenes.php`.
- La opción Anular solo se muestra al usuario de `creado_por` si la solicitud continúa pendiente y no tiene movimientos Kardex. `TransferenciaAlmacen::anular()` verifica esa misma regla bajo bloqueo de fila; la autorización previa solo para receptor ya no aplica a esta opción.
- Actualizados `docs/inventario.md` y `TransferenciaAlmacenSolicitudTest` para verificar persistencia y autorizaciones. `php vendor/bin/phpunit tests/Feature/TransferenciaAlmacenSolicitudTest.php --stop-on-failure`: 7 pruebas/40 aserciones correctas; `php -l` en modelo, Resource, migración y prueba correcto; Pint `--test` en prueba y migración correcto; `git diff --check` correcto (aviso habitual de normalización CRLF en el Resource). No se ejecutó la migración sobre una base operativa ni se comprobó visualmente en navegador.

## 2026-10-20 — Errores amigables y notificaciones de resultado del traspaso

- El error real `Stock insuficiente para la salida` al aceptar una solicitud ahora se traduce en validación del formulario de aceptación con artículo, solicitado y disponible; la transacción garantiza estado pendiente y sin movimientos parciales. Se amplió el clasificador operativo Livewire para reconocer la excepción de stock insuficiente sin cambiar el control global para excepciones inesperadas.
- Aceptación, rechazo y anulación muestran notificación persistente con código, consecuencia sobre existencias/Kardex y estado nuevo. La vista incluye las observaciones de resolución guardadas.
- `TransferenciaAlmacenSolicitudTest`: 8 pruebas/45 aserciones correctas, incluido stock insuficiente sin movimientos parciales; `php -l` en servicio, modelo, Resource y prueba correcto; Pint `--test` de prueba y `git diff --check` correctos (aviso usual de CRLF). No se requiere migración para este ajuste ni se realizó verificación visual en navegador.

## 2026-10-20 — Notificación visible cuando el traspaso no tiene stock

- El campo de observaciones del modal secundario no mostraba de forma clara el error de falta de stock. Al detectar esa condición, la acción Aceptar ahora envía una notificación persistente, informa código, artículos/cantidades disponibles, que sigue Pendiente y que no hubo cambios en stock/Kardex; cierra el modal secundario y permite reintentar tras corregir existencias.
- El clasificador global reconoce `Exception` con variantes de mensaje de stock insuficiente; se conserva sin tratar `Error` técnico genérico como operativo. Cobertura añadida en `NotificacionExcepcionOperativaServiceTest`.
- Verificación: suites combinadas TransferenciaAlmacenSolicitudTest + NotificacionExcepcionOperativaServiceTest, 11 pruebas/51 aserciones; PHP lint, Pint `--test` y `git diff --check` correctos (aviso CRLF conocido). Sin migración.

## 2026-10-20 — Consulta de traspasos por usuarios con acceso a la bandeja

- La acción `Ver traspaso` quedó disponible para todos los usuarios que ya pueden consultar la bandeja/listado, incluidos creador y usuarios ajenos al destino. Aceptar y Rechazar siguen ocultos salvo para `receptor_id`, y el modelo conserva las verificaciones de autorización. Anular se mantiene solo para quien creó la solicitud.
- La prueba Livewire comprueba que un usuario distinto puede abrir el documento y ver sus artículos, pero no tiene botones de resolución. `TransferenciaAlmacenSolicitudTest`: 9 pruebas/54 aserciones correctas; PHP lint, Pint `--test` y `git diff --check` correctos (aviso CRLF usual). No requiere migración.

## 2026-10-20 — Filtro de pendientes e historial de traspasos

- El filtro permanente de consulta base por estado se reemplazó con filtro **Estado** predeterminado a Pendiente; se puede cambiar para consultar recibidos, rechazados y anulados, además de filtrar por origen, destino, creador, receptor y fechas de envío. Pendiente excluye traspasos históricos con movimientos Kardex.
- Las acciones Aceptar/Rechazar se restringen a solicitudes pendientes sin Kardex y siguen siendo visibles solo al receptor. Se añadió prueba del filtro predeterminado y consulta de recibidos. `TransferenciaAlmacenSolicitudTest`: 10 pruebas/60 aserciones correctas; PHP lint, Pint `--test` y `git diff --check` correctos (aviso CRLF usual). Sin migración.

## 2026-10-20 — Sin creación de documentos desde relaciones del cliente

- Se retiraron los `CreateAction` de Pedidos, Facturas y Cotizaciones dentro de `ClienteResource`; permanecen las consultas y acciones por registro y los botones de creación de sus Resources principales.
- Verificación: `TotalesDocumentosTest`: 2 pruebas/33 aserciones correctas; `php -l` en los tres Relation Managers y `git diff --check` correctos. Pint `--test` señala reglas de estilo preexistentes (operadores unarios) en los tres archivos; no se aplicó reformateo ajeno al cambio.

## 2026-10-20 — Monitor global de cambios y errores del sistema

- Implementado registro sin interfaz ni cambios en Resources/Relation Managers: listener Eloquent para altas, actualizaciones y eliminaciones; middleware global para respuestas 4xx/5xx; reporte global de excepciones con clase, línea y código HTTP sin mensaje sensible. La tabla `sys_eventos_auditoria` conserva actor, modelo/registro, diferencias, ruta, método, severidad, código HTTP, IP y agente; secretos se redactan.
- El registro se excluye a sí mismo y fallos de escritura de auditoría se envían al log Laravel sin interrumpir el flujo. Operaciones masivas/SQL directo que omiten eventos Eloquent no generan detalle de cambios de modelo. Migración aditiva pendiente de aplicar por el proceso normal; no se ejecutó contra base operativa. Guía `docs/auditoria-sistema.md`.
- `AuditoriaSistemaMonitorTest`: 3 pruebas/12 aserciones correctas sin warnings; PHP lint y Pint de archivos nuevos pasaron; `git diff --check` pasó. No se ejecutó la migración operativa.

## 2026-10-20 — Resource de consulta de auditoría en Sistema

- Añadido `AuditoriaResource` al Cluster Sistema con tabla, búsqueda, filtros de tipo/nivel/código HTTP/periodo y slide-over de detalle. Es de solo lectura y `canViewAny()` restringe el acceso a roles `admin` y `super_admin`; no se modificaron otros Resources ni Relation Managers.
- Añadido modelo de consulta `AuditoriaSistema` y prueba de visibilidad/solo lectura. `AuditoriaSistemaMonitorTest`: 4 pruebas/20 aserciones correctas; PHP lint, Pint `--test` y `git diff --check` correctos. `php artisan route:list --path=dashboard/configuracion/auditoria` confirma `GET dashboard/configuracion/auditorias`. La migración del monitor sigue pendiente para despliegue.

## 2026-10-20 — Conversión de cotización a Factura y conservación del IVA

- En cotizaciones aprobadas se añadió **Convertir a venta**, que abre el alta de Factura con la cotización precargada. El alta valida que siga abierta/vigente y al guardar crea el Pedido asociado y marca la cotización convertida dentro de la transacción existente; se mantienen las validaciones y el flujo de pagos de la factura.
- La carga de líneas copia `aplicar_iva` y `tasa_impuesto`. El cálculo del formulario, el cálculo local JavaScript, la normalización al guardar y los modelos usan la tasa de cada detalle. Al convertir cotización a Pedido también se conserva el indicador de IVA; el modelo de detalle del Pedido ya no agrega impuesto cuando el indicador está apagado.
- Verificación: `CalculoRepeaterTest` + `VentasFormularioRealTest`, 10 pruebas/61 aserciones; `tests/js/ventas-importes.test.cjs`, 5/5; `php -l` en 8 archivos PHP y `git diff --check` correctos. Pint `--test` identifica reglas de estilo preexistentes en archivos grandes y finales CRLF; no se reformatearon archivos completos. No se ejecutaron migraciones ni se probó el flujo en navegador.

## 2026-10-20 — Validación de unidades `1.00` en Cotizaciones y Facturas

- Los campos Cant. usaban regla `integer`, que rechaza el valor decimal serializado por Eloquent (`1.00`) aunque representa una unidad entera. Se usa `numeric` con la regla Laravel `CantidadEntera`: acepta `1`, `1.0` y `1.00`, y rechaza cantidades fraccionarias como `1.50`.
- Verificación: `VentasFormularioRealTest` dirigido, 1 prueba/4 aserciones para ambos formularios; sintaxis PHP y `git diff --check` correctos. Sin cambios de esquema ni migraciones.

## 2026-10-20 — Precarga de monto recibido con el total de Factura

- Al importar una cotización o elegir **Pago parcial y reserva de stock**, `Monto recibido ahora` se inicializa con el total de las líneas. Mientras conserve el último valor sugerido, el campo acompaña cambios de cantidad/precio/descuento/IVA. Si el usuario ingresa un abono distinto, se conserva y deja de sincronizarse automáticamente.
- El total sugerido se lleva también al abrir el alta directa desde **Convertir a venta**. La referencia automática es estado temporal del formulario (`dehydrated(false)`), no se persiste como columna.
- Verificación: `VentasFormularioRealTest` completo, 8 pruebas/33 aserciones, incluido el autofill y preservación del abono manual; `php -l` en Resource, página y prueba; `git diff --check` correctos. Sin migraciones.

## 2026-10-21 � Contador y aviso de solicitudes de traspaso

- El badge de navegaci�n de TransferenciaAlmacenResource cuenta solo solicitudes en_transito asignadas al usuario autenticado y sin movimientos Kardex; se oculta cuando el total es cero. Conserva la misma exclusi�n de hist�ricos del filtro pendiente.
- CreateTransferenciaAlmacen env�a al destinatario una notificaci�n Filament persistente en el centro de notificaciones despu�s de una creaci�n exitosa. Incluye c�digo, origen, destino, que el stock no se mueve hasta aceptar, y enlace a la bandeja; se despacha el evento para actualizaci�n del centro.
- Documentado en docs/inventario.md. Pruebas agregadas para contador y persistencia del aviso.
- Verificaci�n ejecutada: php artisan test --filter=TransferenciaAlmacenSolicitudTest, 12 pruebas/68 aserciones correctas; Pint en los tres archivos PHP, correcto. No se ejecut� suite completa ni prueba de navegador; sin cambios de esquema ni migraciones.

## 2026-10-21 � Filtro de receptor y errores 500 en traspasos

- Corregido el selector Receptor responsable de TransferenciaAlmacenResource: usuarios no administradores se restringen por empresa mediante empleado.historialActivo.empresa_id, sin consultar la columna inexistente users.empresa_id como SQL.
- Errores de base de datos dejan de tratarse como excepciones operativas y el render web de errores 500 muestra una notificaci�n persistente gen�rica y regresa a la p�gina anterior. Los errores HTTP 4xx conservan su manejo.
- AuditoriaSistemaService persiste en datos.error_markdown el m�todo/ruta/URL sin query, clase, mensaje, todas las causas y traza completa. Cookies/encabezados de autenticaci�n y l�neas con posibles credenciales/token/secretos quedan redactadas. Sin guardar encabezados/cuerpos completos.
- Actualizados docs/auditoria-sistema.md y docs/inventario.md. Pruebas a�adidas para query exception, notificaci�n gen�rica y Markdown auditado con sanitizaci�n.
- Verificaci�n: AuditoriaSistemaMonitorTest + TransferenciaAlmacenSolicitudTest, 18 pruebas/102 aserciones correctas. PHP lint correcto. Pint --test primero encontr� estilo en archivos cambiados; Pint aplicado a bootstrap, servicio operativo y prueba. Pendiente reejecutar Pint --test y git diff --check. No se ejecut� suite completa ni migraciones.

## 2026-10-21 - Badge de traspasos solo para el usuario receptor

- El distintivo usa Filament::auth() del panel activo y cuenta solo solicitudes pendientes cuyo receptor_id coincide con el usuario del panel, sin movimientos Kardex. Otros usuarios no ven el numero.
- Prueba ampliada con solicitudes para dos receptores y un usuario sin solicitudes; confirma contador aislado y ausencia del badge para este ultimo.
- Verificacion: TransferenciaAlmacenSolicitudTest, 12 pruebas/69 aserciones; Pint --test en Resource y prueba, y git diff --check correctos.

## 2026-10-21 - Alcance del resource Stock de almacenes

- StockAlmacenResource deja de reutilizar el filtro de AlmacenResource (que limita por sucursal) y ahora incluye todos los almacenes de la empresa activa del usuario. Usuarios administradores conservan acceso global; si un usuario carece de empresa activa, no ve registros. No cambia el alcance de administracion de almacenes ni se permite consultar otras empresas.
- Prueba de regresion con dos sucursales de la empresa y una empresa ajena: Stock lista tres almacenes propios; AlmacenResource mantiene solo la sucursal asignada y almacenes sin sucursal. StockAlmacenVisibilidadTest: 1 prueba/4 aserciones correcta.
- PHP lint, git diff --check correctos. Pint --test del Resource acusa reglas de formato existentes (separacion de atributos y finales CRLF); el archivo de prueba nuevo pasa. Sin migraciones.
