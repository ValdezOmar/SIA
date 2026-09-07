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

## Configurar artículos

Registrar la empresa, el código y nombre del producto, su unidad, grupo, fabricante y modelo. Definir si está activo, es inventariable, comprable o vendible. Completar foto y características para facilitar la consulta comercial.

Asignar precios en las listas correspondientes; la consulta de stock muestra precios de listas activas y su moneda. Registrar los códigos de barras usados en las etiquetas. Si se usan series o lotes, configurar sus controles antes de registrar las operaciones que requieren esa información.

La valoración de salidas contempla costo promedio, estándar, LIFO y consumo de capas. Para costo estándar se exige un valor positivo. En la rama de capas, LIFO consume las más recientes; una capa específica indicada se utiliza cuando corresponde y, en los demás casos, se priorizan las más antiguas. No todos los movimientos equivalen a FIFO: depende de la configuración y los datos enviados.

## Consulta rápida para vendedores

En **Stock por artículo**, buscar por código, modelo, nombre, descripción, marca o código de barras. Se muestran todos los registros de existencia del almacén sin paginación; esto no agrega automáticamente artículos del catálogo que nunca tuvieron una existencia registrada allí.

La fila muestra la identificación con foto, unidad, precios por lista, stock físico, reservado y **Libre para vender**. El botón **Ver ficha** abre descripción, características, códigos, estado de venta y fechas de movimientos. Se pueden habilitar columnas adicionales de mínimo, por recibir, grupo y actualización.

```text
Libre para vender = cantidad física disponible − cantidad comprometida
```

Ejemplo: 10 unidades físicas y 3 reservadas dejan 7 libres. El estado y los filtros de disponibilidad usan esas unidades libres. El filtro de artículos activos y vendibles es opcional; un artículo inactivo puede conservar stock.

## Movimientos y reservas

Las entradas, salidas y ajustes deben registrarse en Kardex para conservar cantidad, costo, usuario y documento de origen. La pantalla de Stock es de consulta.

Una reserva aumenta la cantidad comprometida sin disminuir la existencia física. La entrega libera las reservas asociadas y registra la salida física. En el flujo actual de ventas, la entrega se procesa cuando el importe está completamente pagado; consultar [Ventas](ventas.md).

Cada salida exige existencia suficiente y una valoración posible. La anulación usa un movimiento de reversión; no debe reemplazarse por el borrado manual del Kardex o una modificación directa de la cantidad disponible.

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
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --filter="InventarioFisicoTest|VentaFechasTest|KardexAccountingIntegrationTest"
```

Las pruebas de componentes no verifican la cámara física: comprobar una etiqueta real desde el teléfono con permiso de cámara y conexión HTTPS.
