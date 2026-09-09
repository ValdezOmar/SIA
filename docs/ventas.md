# Ventas

[Índice de módulos](README.md) · Revisión: 7 de septiembre de 2026.

## Preparación

El cliente debe pertenecer a la empresa de la venta y la sucursal debe corresponder a esa empresa. Para entregar artículos se necesita un almacén activo, existencias suficientes y una valoración de costos válida. Los artículos con control de series o lotes requieren su trazabilidad.

## Clientes

Registrar código, nombre, documento, celular y datos comerciales. El sistema normaliza los textos principales a mayúsculas y el celular a dígitos. La creación puede reutilizar un cliente con el mismo celular dentro de la empresa; no se debe crear otra ficha para el mismo contacto. Al editar, un celular que pertenece a otro cliente de esa empresa se rechaza.

En Facturas, Pedidos y Cotizaciones, el selector muestra **nombre · celular**; si no hay celular, muestra el teléfono disponible. Permite buscar por nombre, código, celular o teléfono, también con espacios, guiones o paréntesis. Carga hasta 50 coincidencias por consulta y permite recuperar el cliente seleccionado aunque no esté entre las primeras opciones.

El registro rápido comienza con **Código → Nombre → Celular → CI / NIT**. Los demás campos se agrupan en **Contacto adicional**, **Ubicación** y **Datos comerciales**. El código se genera automáticamente; nombre y celular son obligatorios.

Las pestañas del cliente aparecen como **Facturas → Pedidos → Cotizaciones**. En la pestaña Pedidos está activo por defecto el filtro **Pendiente**; quitarlo permite revisar otros estados. La modificación de clientes conserva `updated_at`; no utiliza una columna `actualizado_por`.

### Contactos para el teléfono

1. Filtrar el listado de clientes que se desea exportar y pulsar **Descargar contactos**.
2. En la primera descarga, dejar vacíos los archivos anteriores.
3. Importar el archivo VCF en la aplicación de contactos del teléfono.
4. En futuras descargas, adjuntar todos los VCF previamente importados en ese teléfono o un VCF exportado desde sus contactos. El sistema excluirá los números contenidos en ellos.

El nombre tiene el formato **26001 JUAN QUISPE**. Se exportan celulares válidos sin repetir; los números locales de ocho dígitos reciben `+591`. No se exporta una ficha sin celular válido. El archivo tiene identificadores estables, pero reimportar el mismo VCF puede producir duplicados según la aplicación móvil. La descarga no sincroniza ni actualiza por sí sola los contactos del teléfono o WhatsApp.

## Cotizaciones

Registrar cliente, artículos, cantidades, precios, descuentos e impuestos aplicables. El listado ofrece acciones para marcar la cotización como enviada, aprobarla, rechazarla, duplicarla y convertirla en pedido según su estado. **Enviar** cambia el estado del documento; no debe interpretarse como confirmación de entrega de un correo.

Una cotización convertida debe continuarse mediante su pedido/factura asociados. Al usar una cotización de origen desde Facturas se comprueba que siga abierta y corresponda al cliente seleccionado.

## Pedidos y reservas

El listado general de Pedidos muestra **Pendiente y Reservado** por defecto. Este filtro es distinto del filtro de la pestaña del cliente, que muestra solamente **Pendiente**. Ambos se pueden cambiar.

**Reservar stock** compromete unidades, sin descontarlas del stock físico. **Preparar entrega** cambia un pedido reservado a pendiente. **Confirmar entrega** usa la factura asociada y exige que esté totalmente pagada. Cancelar un pedido desde el flujo de cancelación libera su reserva y registra el motivo.

En Facturas, el selector **Pedido asociado** ofrece únicamente pedidos pendientes o reservados del contexto permitido, con detalles válidos. Un pedido entregado o cancelado no aparece como nueva opción.

Al elegir un cliente, la condición de pago comienza y se conserva en **Contado** hasta que el vendedor la cambie. El selector de artículos muestra el stock disponible del almacén de venta: verde con más de 5 unidades, naranja entre más de 1 y 5, y rojo con 1 o menos; sin existencias muestra **Stock: Sin stock**. Al elegir un artículo, su **Lista de precios** queda disponible de inmediato y se actualiza al cambiar de artículo.

## Facturas y pagos

Los artículos identificados como **Servicio · sin stock** se facturan y cobran sin requerir existencias ni almacén. En documentos mixtos, solo los productos inventariables reservan y descuentan stock; los servicios mantienen su importe y su contabilización de venta. Ver [configuración de productos y servicios](inventario.md#productos-y-servicios).

### Venta al contado

1. Seleccionar cliente, artículos, precios, cantidades y las fechas de venta, pago y entrega.
2. Elegir **Contado** y completar el medio de pago y las referencias necesarias.
3. Guardar. Se recalculan los totales, se registra el pago automático y se procesa la venta.
4. Comprobar pago, pedido entregado cuando hay artículos, salida en Kardex y asientos asociados.

El flujo actual procesa la entrega al quedar la venta totalmente pagada. La fecha de entrega seleccionada se usa para fechar los registros; no constituye una tarea automática que espere hasta ese día para descontar stock.

### Pago parcial

Registrar el abono inicial y seleccionar **Pago parcial y reserva de stock**. Los pagos confirmados disminuyen el saldo y la existencia queda comprometida. Los abonos posteriores se registran con su propia fecha y medio de pago. Al completar el importe se procesa la salida y se liberan las reservas.

El pago debe ser mayor que cero y no superar el saldo. Una factura pagada o anulada no admite otro pago por el flujo de registro. Una factura sin artículos puede generar contabilidad sin generar un pedido o movimiento de almacén.

### Pago mixto: Efectivo + QR

En el pago inicial o en **Registrar pago**, elegir **Mixto: Efectivo + QR** e introducir ambos importes. Los dos deben ser positivos. En contado, la suma debe cubrir el saldo completo; en un abono parcial, la suma es el importe recibido y no puede superar el saldo.

El sistema guarda dos pagos con la misma fecha, uno por cada medio. El banco y la referencia corresponden a la parte QR. Cada parte genera su asiento en la cuenta receptora correspondiente; la entrega se procesa una sola vez. Si falla la operación, se revierten ambas partes y sus asientos.

### Captura y cálculo de los detalles

Las cantidades, precios y descuentos se escriben sin solicitar recálculos al servidor por cada pulsación. La fila y el resumen se actualizan localmente mientras se escribe; cambiar IVA actualiza también impuesto y total. **Calcular totales** permite solicitar un recálculo adicional. Guardar vuelve a calcular con los datos actuales, aunque no se haya pulsado el botón. Al elegir un artículo se carga su primera lista de precios y al cambiar de lista se actualiza de inmediato el precio de la fila.

El resumen local y las filas comparten los importes del formulario. Si un descuento supera el importe de la línea, la vista muestra **—** en lugar de conservar un total anterior; al guardar se informa el error de validación. Al incorporar esta actualización a una sesión ya abierta, recargar la página para cargar el script de cálculo.

Si se modifica el porcentaje o el importe del descuento, se usa el último de esos campos editado. **Subtotal neto** significa cantidad × precio menos descuento. El impuesto se calcula sobre ese subtotal y el total suma subtotal neto e impuesto; el descuento no se resta otra vez. En pedidos, el envío se agrega una sola vez al total del documento.

Ejemplo: 3 unidades × 100, descuento del 10 % → descuento 30, subtotal neto 270. Con IVA del 13 %, impuesto 35,10 y total 305,10. Los resúmenes usan las filas actuales del formulario; las cabeceras se actualizan después de guardar todas las filas, incluidas sus eliminaciones.

## Fechas de la operación

| Dato seleccionado / origen | Registros afectados |
| --- | --- |
| Fecha de venta (`fecha_emision`) | Fecha del asiento de venta y fecha contable de su Kardex; fecha de venta de las series. |
| Fecha de pago | Pago y asiento del cobro. Cada abono conserva su fecha. |
| Fecha de entrega (`fecha_vencimiento` en la tabla actual) | Fecha del pedido generado, salida física en Kardex, movimiento de inventario y entrega real del pedido. |
| Primer pago confirmado | Fecha de la reserva del pedido generado desde la factura. |
| Fecha posterior entre venta y último pago confirmado | Aplicación contable de anticipos del cliente. |
| Momento real de guardado/autorización | Campos de auditoría; no reemplazan las fechas anteriores. |

Ejemplo: venta del **20/12/2025**, pago del **18/12/2025**, entrega del **23/12/2025**, registrada en septiembre de 2026. El asiento de venta corresponde al 20/12, el cobro al 18/12, la aplicación al 20/12 y la salida física al 23/12. Los saldos contables se imputan al período correspondiente, no al mes de captura.

Cambiar entre contado y parcial no reemplaza las fechas elegidas por hoy. Si se omiten fechas en el flujo de pago automático, se usan las fechas disponibles de la factura como respaldo. La regularización contable puede indicar una fecha contable explícita: consultar [Contabilidad](contabilidad.md).

## Consultas, anulación y errores frecuentes

Facturas y Pedidos muestran por defecto código/número, cliente, fecha, estado, total y saldo. Cotizaciones muestra código, cliente, fecha, estado y total. Las demás columnas se activan desde el selector; una cotización no tiene saldo de cobros.

La acción **Anular** de la factura solicita un motivo y ejecuta el flujo de reversión de inventario, pagos y contabilidad. No equivale a borrar filas. Los estados históricos se consultan quitando los filtros del listado.

| Situación | Qué revisar |
| --- | --- |
| No aparece un pedido | Estado, empresa/sucursal y validez de sus detalles. |
| Stock insuficiente | Almacén utilizado, físico menos reservado y cantidades de la venta. |
| No se puede valorar una salida | Método de costo y capas disponibles del artículo. |
| Serie o lote rechazado | Artículo, almacén, disponibilidad y cantidades indicadas. |
| Período cerrado o bloqueado | Fecha efectiva del asiento y estado del período; no cambiar la fecha para eludirlo. |
| Asiento antiguo conserva una fecha incorrecta | Los cambios de fechas no reprocesan automáticamente asientos ya confirmados. |

## Referencias y pruebas

- [FacturaResource](../app/Filament/Resources/Ventas/FacturaResource.php), [creación de facturas](../app/Filament/Resources/Ventas/FacturaResource/Pages/CreateFactura.php).
- [Modelo Factura](../app/Models/Ventas/Factura.php), [Pedido](../app/Models/Ventas/Pedido.php), [Cliente](../app/Models/Ventas/Cliente.php).
- [Exportación de contactos](../app/Services/Ventas/ExportarContactosService.php).
- [Pruebas de fechas](../tests/Feature/VentaFechasTest.php) y [contado](../tests/Feature/FacturaContadoTest.php).

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --filter="VentaFechasTest|FacturaContadoTest|ExportarContactosServiceTest"
```
