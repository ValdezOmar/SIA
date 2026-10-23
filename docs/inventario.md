# Inventario y stock

[Índice de módulos](README.md) · Revisión: 7 de septiembre de 2026.

## Estructura del módulo

SIA separa el catálogo de artículos, las existencias por almacén, el libro de movimientos y las sesiones de conteo físico. Una modificación de la ficha del artículo no representa una entrada o salida de mercadería.

| Componente | Uso |
| --- | --- |
| Artículos | Código, modelo/código alterno, nombre, características, fabricante, grupo, unidad y controles de venta/inventario. |
| Precios | Precio del artículo por lista y moneda. |
| Stock por almacén | Consulta de existencias físicas, reservas y unidades libres. |
| Kardex | Registro de entradas/salidas, valoración, referencias y reversión de movimientos. |
| Series y lotes | Trazabilidad del producto y sus movimientos. |
| Inventarios físicos | Programación, referencia de stock, conteos, revisión e historial. |

En **Stock por almacén**, un usuario no administrador puede consultar todos los almacenes de su empresa, incluso los asignados a otras sucursales. La consulta no muestra almacenes de otras empresas. La administración de almacenes conserva el filtro de sucursal del usuario.

El widget **Inventario · Disponibilidad y riesgo** permite exportar un Excel para análisis de stock. Incluye una fila por artículo y almacén de la empresa del usuario (o alcance global para administradores), solo productos inventariables con al menos una unidad disponible. El archivo identifica empresa, sucursal y almacén, y presenta cantidades física, comprometida y libre, costos promedio/último, valor acumulado y precios de listas activas con su moneda. Las listas se agrupan en una celda para no duplicar cantidades de inventario.

## Configurar artículos

Registrar la empresa, el código y nombre del producto, su unidad, grupo, fabricante y modelo. Definir si está activo, es inventariable, comprable o vendible. Completar foto y características para facilitar la consulta comercial.

### Productos y servicios

En la pestaña **Inventario**, la opción **Controlar stock (producto físico)** guarda el campo `inventariable`:

| Configuración | Comportamiento |
| --- | --- |
| Activada: producto con stock | Necesita existencias para reservar o entregar; registra movimientos, costos y trazabilidad configurada. |
| Desactivada: servicio | Se vende y cobra sin exigir almacén ni existencias. No reserva, descuenta stock ni genera costo de inventario. |

Para registrar un servicio, desactivar el control de stock, mantener **Disponible para venta** y asignar un precio. Series, lotes y obligatoriedad de serie se desactivan al guardar; las opciones de costeo y stock se ocultan. **Disponible para compra** y **Disponible para venta** son opciones independientes del control de stock.

Una factura puede mezclar productos y servicios: el ingreso comercial incluye ambos, pero solo los productos generan reservas y salidas de Kardex. Los pagos parciales de servicios conservan su seguimiento comercial sin comprometer existencias. La recepción de compras tampoco ingresa servicios al almacén. El Kardex manual permite elegir productos inventariables y rechaza movimientos nuevos de servicios.

Cambiar un artículo existente a servicio no elimina ni corrige sus existencias o movimientos anteriores. Las reservas previas se liberan al completar o cancelar la operación; las anulaciones de movimientos históricos conservan su mecanismo de reversión. Revisar los saldos anteriores antes de cambiar la clasificación de un producto que todavía tenga stock.

Asignar precios en las listas correspondientes; la consulta de stock muestra precios de listas activas y su moneda. En **Precios por Lista**, la acción **Ver auditoría** abre el precio seleccionado y muestra en una tabla su historial de altas, modificaciones y eliminaciones: usuario, fecha/hora, lista y precio anterior/nuevo. La edición usa un formulario apilado a todo el ancho e incluye indicadores de precio vigente, variación, cantidad de cambios y última actividad. La bitácora es de solo lectura y mantiene copias de código y nombre del artículo y de la lista, para que la evidencia continúe disponible aunque se elimine el precio, el artículo o la lista. Triggers de base de datos registran también actualizaciones masivas, SQL directo y eliminaciones en cascada en MySQL y SQLite; las escrituras sin contexto de usuario quedan atribuidas a **Sistema (BD)**. El evento Eloquent agrega el usuario autenticado para las operaciones normales de la aplicación. Las operaciones DDL como `TRUNCATE` no disparan triggers de fila. Registrar los códigos de barras usados en las etiquetas. Si se usan series o lotes, configurar sus controles antes de registrar las operaciones que requieren esa información.

La valoración de salidas contempla costo promedio, estándar, LIFO y consumo de capas. Para costo estándar se exige un valor positivo. En la rama de capas, LIFO consume las más recientes; una capa específica indicada se utiliza cuando corresponde y, en los demás casos, se priorizan las más antiguas. No todos los movimientos equivalen a FIFO: depende de la configuración y los datos enviados.

### Stock negativo por almacén

En **Parámetros de Inventario > Almacenes**, cada almacén tiene la opción **Permitir salidas con stock negativo**. Está desactivada de forma predeterminada. Al activarla, una venta, ajuste o transferencia de salida puede dejar la existencia bajo cero solo en ese almacén; los demás almacenes siguen bloqueando salidas sin stock.

La salida negativa requiere un costo para proteger la contabilidad. El sistema usa, en este orden, el costo indicado en la salida, el último costo del almacén, el costo promedio o el costo estándar del artículo. Si ninguno tiene valor, bloquea la operación. Kardex guarda que la salida fue negativa y el costo provisional usado; Contabilidad registra el mismo importe entre costo de ventas/inventario o las cuentas del movimiento correspondiente. Una entrada posterior mantiene el valor acumulado y recalcula el costo promedio del saldo resultante.

No use esta opción para omitir recepciones o corregir diferencias físicas. Registre la entrada real lo antes posible y revise el Kardex y el asiento generado. Series y lotes siguen exigiendo identificación y saldo disponible, aunque el almacén permita stock negativo.

## Consulta rápida para vendedores

En **Stock por artículo**, buscar por código, modelo, nombre, descripción, marca o código de barras. Se muestran todos los registros de existencia del almacén sin paginación; esto no agrega automáticamente artículos del catálogo que nunca tuvieron una existencia registrada allí.

La fila muestra la identificación con foto, unidad, precios por lista, stock físico, reservado y **Libre para vender**. El botón **Ver ficha** abre descripción, características, códigos, estado de venta y fechas de movimientos. Se pueden habilitar columnas adicionales de mínimo, por recibir, grupo y actualización.

```text
Libre para vender = cantidad física disponible − cantidad comprometida
```

Ejemplo: 10 unidades físicas y 3 reservadas dejan 7 libres. El estado y los filtros de disponibilidad usan esas unidades libres. El filtro de artículos activos y vendibles es opcional; un artículo inactivo puede conservar stock.

## Movimientos y reservas

Las entradas, salidas y ajustes deben registrarse en Kardex para conservar cantidad, costo, usuario y documento de origen. La pantalla de Stock es de consulta.

En la pestaña Kardex de la ficha del artículo, la columna **Fecha y hora de registro** muestra cuándo se creó el movimiento (`created_at`), sin mezclarla con la fecha física del movimiento ni con la contable. Para movimientos de venta, **Documento** muestra la serie de la factura junto al número cuando la factura tiene una serie configurada.

En el historial, una venta identifica su documento en líneas separadas con el número de factura, la serie y el ID de venta. **Operación realizada** combina el tipo real de movimiento con su dirección (entrada/salida). Al abrir el movimiento, el resumen incorpora cliente, factura, serie, fecha de emisión y total cuando existe una factura asociada; el mapa gráfico enlaza venta, movimiento Kardex, artículo y almacén, e incluye el movimiento relacionado cuando existe.

Una reserva aumenta la cantidad comprometida sin disminuir la existencia física. La entrega libera las reservas asociadas y registra la salida física. En el flujo actual de ventas, la entrega se procesa cuando el importe está completamente pagado; consultar [Ventas](ventas.md).

Cada salida exige existencia suficiente y una valoración posible, salvo que el almacén permita explícitamente stock negativo. La anulación usa un movimiento de reversión; no debe reemplazarse por el borrado manual del Kardex o una modificación directa de la cantidad disponible.

En **Kardex → Crear**, al seleccionar artículo y almacén se propone el último costo de una entrada confirmada registrado para ese almacén; si no hay costo allí, se busca la última entrada confirmada del artículo. El campo **Costo unitario** sigue siendo editable y el total se recalcula al cambiar cantidad o costo.

### Solicitudes de traspaso entre almacenes

El listado de traspasos tiene un filtro de estado que inicia en **Pendiente** y se puede cambiar para consultar las solicitudes **Recibidas**, **Rechazadas** o **Anuladas**. Se puede combinar con filtros de origen, destino, solicitante, receptor y fecha de solicitud. El contador del menú usa el usuario autenticado en el guard del panel Filament y muestra solo las solicitudes pendientes cuyo `receptor_id` coincide con ese usuario; excluye otros destinatarios y traspasos históricos en tránsito que ya tienen movimientos Kardex. Se puede ocultar cuando el contador es cero. En el selector de receptor, el alcance de empresa se resuelve mediante el historial laboral activo del empleado, no desde una columna `empresa_id` inexistente en `users`. Los traspasos históricos en tránsito que ya tienen movimientos Kardex no aparecen como pendientes y no ofrecen acciones de resolución. El código de cada solicitud sigue el formato `TRA-YYNNNN` (por ejemplo, `TRA-260001`) y usa el siguiente correlativo disponible del año; los códigos históricos permanecen intactos. Un traspaso se crea como solicitud **pendiente** y se envía automáticamente al guardar. Al guardarse correctamente, se registra una notificación en el centro de notificaciones del destinatario con código, almacenes de origen y destino, y un acceso a la bandeja; informa que el stock no se mueve hasta aceptar. No se puede editar después de registrarlo. Los usuarios con acceso a la bandeja pueden abrir **Ver traspaso** y revisar el documento y los artículos enviados. Solo la persona asignada como responsable del almacén de destino puede ver y ejecutar Aceptar o Rechazar (con motivo obligatorio para rechazar y observaciones opcionales al resolver). Anular permanece en el menú y solo es visible y ejecutable por quien creó la solicitud mientras siga pendiente y no tenga movimientos Kardex.

La solicitud pendiente se guarda con el estado existente `en_transito`, sin salida Kardex; las transferencias históricas en tránsito que sí tienen salida quedan excluidas del listado. Aceptar valida y registra en una sola transacción la salida del almacén de origen y la entrada al destino, con costo, series/lotes y movimientos relacionados en Kardex. Antes de aceptar, la solicitud no reserva ni descuenta existencias. Si el stock del origen es insuficiente, no se acepta ni se dejan movimientos parciales; se presenta una notificación persistente con artículos y cantidades solicitadas/disponibles, indica que el estado sigue **Pendiente** y que no hubo cambios en Kardex, además de sugerir registrar stock, liberar reservas o reducir la cantidad y volver a intentar. Las notificaciones de aceptación, rechazo y anulación informan la consecuencia en stock/Kardex y el estado nuevo. Rechazar conserva el registro y no mueve stock. Anular la marca como borrador cerrado y la retira de la bandeja; conserva el registro sin borrar ni mover stock. El listado de **Traspasos entre almacenes** muestra exclusivamente solicitudes pendientes. Los traspasos históricos que ya están en tránsito mantienen su Kardex anterior y no se vuelven a procesar.

## Series y lotes

En una salida de artículos con series requeridas se debe indicar una serie disponible por unidad vendida. La validación comprueba artículo, almacén y estado. La serie vendida se vincula al cliente y conserva la fecha de venta recibida desde Facturas.

Para artículos con lotes, la suma de cantidades por lote debe coincidir con la cantidad de salida y debe existir saldo suficiente en cada lote del almacén. La trazabilidad registra los movimientos de series y lotes para permitir la reversión.

El escáner del inventario físico identifica el artículo a contar. No transforma cada escaneo en una unidad contada ni reemplaza el control individual de series y lotes utilizado en entradas/salidas.

## Fechas

En una venta, el Kardex usa la fecha de entrega como fecha física y la fecha de venta como fecha contable. El movimiento asociado y la entrega del pedido conservan la fecha de entrega. La fecha de venta de la serie corresponde a la factura.

La última salida de una existencia se obtiene de la fecha máxima de sus salidas confirmadas: registrar después un movimiento más antiguo no debe reemplazar una salida más reciente. Los campos de creación y modificación siguen reflejando cuándo se registraron los datos.

La corrección de fechas no reconstruye por sí sola la valoración histórica o los saldos anteriores de todos los movimientos. Ante operaciones retroactivas, revisar la secuencia y la valoración del Kardex además de sus fechas.

## Inventarios físicos

El flujo completo está en [Inventarios físicos](inventarios-fisicos.md):

1. Programar por empresa, sucursal, almacén y responsable.
2. Iniciar para fijar el stock de referencia y preparar los artículos.
3. Contar, registrar diferencias y justificar reconteos.
4. Enviar a revisión al llegar al 100 %.
5. Revisar cada artículo y cerrar con conclusión, o devolver a conteo.

El cierre conserva el resultado y la bitácora; no modifica el stock automáticamente. Los ajustes autorizados se registran por Kardex indicando el código de inventario como referencia. Coordinar los movimientos durante el conteo: la sesión no bloquea ventas, compras o transferencias.

La ficha y el listado permiten descargar el PDF; la ficha también exporta CSV. El PDF incluye logo, empresa, sucursal, almacén, responsables, fechas, avance, conteos, diferencias, conclusiones, auditoría y firmas. Los registros del módulo anterior permanecen disponibles como archivo de consulta.

## Problemas frecuentes

| Mensaje o situación | Revisión sugerida |
| --- | --- |
| Hay físico pero no disponibilidad | Comprobar compromisos de pedidos y unidades libres. |
| No existe almacén activo | Revisar empresa, sucursal y estado del almacén. |
| No hay capas suficientes | Revisar entradas, método de costo y cantidades disponibles de las capas. |
| El escáner no localiza un artículo | Comparar el contenido literal del código con los identificadores registrados y el alcance de la sesión. |
| El inventario no llega a 100 % | Registrar también los conteos de cero; vacío sigue siendo pendiente. |
| Diferencia entre conteo y stock actual | Comparar con la referencia tomada al iniciar y revisar movimientos posteriores. |

## Referencias y pruebas

- [Articulo](../app/Models/Inventario/Articulo.php), [Existencia](../app/Models/Inventario/Existencia.php) y [Kardex](../app/Models/Inventario/Kardex.php).
- [Consulta de stock](../app/Filament/Resources/Inventario/StockAlmacenResource/RelationManagers/ArticulosStockAlmacenRelationManager.php).
- [Trazabilidad](../app/Services/Inventario/TrazabilidadInventarioService.php), [sesiones físicas](../app/Services/Inventario/InventarioFisicoService.php) y [PDF](../app/Services/Inventario/InventarioPdfService.php).

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --filter="InventarioFisicoTest|InventarioNegativoTest|VentaFechasTest|KardexAccountingIntegrationTest"
```

Las pruebas de componentes no verifican la cámara física: comprobar una etiqueta real desde el teléfono con permiso de cámara y conexión HTTPS.
