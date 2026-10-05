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
