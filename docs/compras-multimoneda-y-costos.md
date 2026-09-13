# Compras: moneda funcional y costos adicionales

La recepción conserva la moneda negociada (`BOB`, `USD` o `EUR`) y su tipo de cambio a BOB. Kardex, existencias y contabilidad se valorizan en BOB para no mezclar monedas en el costo promedio ni en los asientos.

## Flujo operativo

1. Registre la orden con moneda y tipo de cambio pactados.
2. Al crear la recepción, confirme moneda y tipo de cambio; se copian desde la orden.
3. En **Costos adicionales**, registre flete, seguro, arancel, despacho, impuesto no recuperable u otro gasto.
4. Marque como capitalizable lo que debe aumentar el costo del inventario y elija el prorrateo por valor o cantidad.
5. Procese inventario solo después de revisar esos datos. El sistema convierte cada línea a BOB, distribuye los gastos capitalizables y registra el costo resultante en Kardex.
6. La factura y los pagos conservan su moneda original; los asientos de compra y pagos se expresan en BOB mediante su tipo de cambio.

Los gastos no capitalizables quedan documentados en la recepción y no alteran el costo de existencias. La imputación a una cuenta de gasto específica será el siguiente paso antes de contabilizarlos como gasto de período.