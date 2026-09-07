# Contabilidad

[Índice de módulos](README.md) · Revisión: 7 de septiembre de 2026.

Esta guía describe las reglas implementadas en SIA y su integración con los documentos comerciales. No sustituye la definición de políticas contables de la empresa.

## Componentes

| Componente | Función |
| --- | --- |
| Plan de cuentas | Identifica cuentas, naturaleza, estado y posibilidad de recibir movimientos. |
| Asientos contables | Cabecera, fechas, documento de origen y partidas Debe/Haber. |
| Centros de costo y proyectos | Clasificación adicional de partidas cuando corresponde. |
| Períodos contables | Control de empresa, sucursal cuando aplica, año, mes y estado abierto/cerrado/bloqueado. |
| Saldos de cuentas | Acumulan movimientos por cuenta, año, mes, centro de costo y proyecto. |

## Registro y confirmación

Al crear un asiento, revisar empresa/sucursal, fecha, tipo, concepto y documento de origen. En las partidas seleccionar las cuentas y distribuir los importes en Debe o Haber. Recalcular los totales antes de confirmar.

La confirmación exige al menos dos partidas, cuentas activas que permitan movimiento, importes en un solo lado por partida y un asiento balanceado con total positivo. Un período encontrado como cerrado o bloqueado impide confirmar. La fecha de autorización conserva el momento real de esa acción.

Al confirmar se acumulan los movimientos en el mes y año de `fecha_asiento`. La anulación utiliza la lógica de reversión de saldos. Para documentos originados en Ventas o Kardex debe usarse su flujo de anulación, de modo que no queden inventario y contabilidad desconectados.

## Integración con ventas

| Evento | Registro implementado |
| --- | --- |
| Cobro confirmado | Fondos según el medio de pago contra anticipos de clientes. |
| Venta procesada | Cliente, ingreso e impuesto aplicable; costo de venta e inventario cuando existe costo. |
| Aplicación de anticipos | Cancelación del anticipo contra la cuenta del cliente. |

El total exigible de la factura es la referencia para construir el asiento. Una venta bonificada al 100 % sin costo de inventario no genera un hecho contable; con costo puede generar su asiento correspondiente.

Las cuentas auxiliares usadas por la integración se buscan o crean mediante el modelo contable. Revisar el plan antes de operar: la existencia de una cuenta no garantiza que esté activa o admita movimientos.

## Fechas y registros retroactivos

El asiento de venta usa la fecha de venta de la factura. El asiento del pago usa la fecha de ese pago. La aplicación de anticipos toma la fecha posterior entre la venta y el último pago confirmado. En el Kardex de venta se distingue fecha física de entrega y fecha contable de venta.

La fecha contable explícita enviada a `crearDesdeVenta()` tiene prioridad; se utiliza, por ejemplo, en regularización. Consultar el [ejemplo completo de fechas](ventas.md#fechas-de-la-operación).

Registrar hoy una venta de diciembre no debe trasladar sus saldos a septiembre. Sin embargo, el período de diciembre debe admitir la operación. Una venta que falla dentro de la transacción por período cerrado revierte los pagos y salidas generados durante ese intento.

Los asientos confirmados existentes se reutilizan en los procesos automáticos para evitar duplicación. Modificar una fecha en la factura no reescribe por sí solo su asiento confirmado ni redistribuye los saldos históricos.

## Kardex y regularización

Los movimientos con efecto contable se enlazan con sus asientos. Ventas y compras que ya tienen su asiento de documento no deben contabilizarse otra vez como un asiento independiente de inventario.

`RegularizacionKardexService` recorre movimientos confirmados, con un filtro opcional de empresa y una fecha contable proporcionada. Distingue movimientos contabilizados, ya cubiertos, sin efecto, sin valor y errores. Las transferencias y consignaciones contempladas por ese servicio se omiten como movimientos sin efecto; no deben interpretarse como ventas.

La regularización es un procedimiento técnico de recuperación de contabilización faltante. No es una herramienta para corregir en masa las fechas de asientos confirmados: los registros ya cubiertos se omiten. Revisar el resultado y sus errores por movimiento.

## Controles de revisión

1. Abrir el documento de origen y comprobar empresa, sucursal y fechas.
2. Verificar estado confirmado, igualdad de Debe/Haber y cuentas usadas.
3. Comparar pago, venta y aplicación de anticipos por sus tipos de documento e identificadores.
4. Revisar el período efectivo y los saldos de cuentas del mismo año y mes.
5. Ante una anulación, comprobar la reversión del documento y del stock asociado.

No se documenta un botón de cierre de períodos como parte de Asientos: la validación reside en el modelo y debe respetarse desde cualquier procedimiento de administración de períodos.

## Datos y referencias técnicas

- [AsientoContable](../app/Models/Contabilidad/AsientoContable.php): creación automática, validación, confirmación y saldos.
- [AsientoContableResource](../app/Filament/Resources/Contabilidad/AsientoContableResource.php): formularios y consulta.
- [RegularizacionKardexService](../app/Services/Contabilidad/RegularizacionKardexService.php): recuperación de asientos faltantes.
- [Períodos](../database/migrations/2026_08_12_124602_create_con_periodos_contables_table.php) y [saldos](../database/migrations/2026_08_10_165537_create_con_saldos_cuentas.php).

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --filter="VentaFechasTest|KardexAccountingIntegrationTest"
```

Estas pruebas cubren fechas de venta y cobro, período cerrado, saldos del período, asientos de Kardex y reversión contable. No equivalen a una auditoría de datos productivos ni a un reproceso histórico.
