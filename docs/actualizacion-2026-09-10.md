# Actualización de cambios - 10 de septiembre de 2026

## Plataforma y apariencia

- El panel utiliza Laravel 13.31, Filament 5.8 y Livewire 4.4 con un tema nativo de SIA; DashStack fue retirado.
- La apariencia, identidad visual y fondo de inicio de sesión se administran desde **Sistema > Parámetros generales**.
- El inicio de sesión se mantiene en tema claro para preservar contraste y legibilidad.
- **Análisis comercial** ya no es una página del menú: se muestra como widget Livewire reactivo en el Escritorio, con filtro de período y actualización periódica.

## Integridad de operaciones

Ventas, Inventario y Contabilidad se validan como un flujo transaccional:

1. Una venta pagada registra los pagos, procesa la entrega de productos inventariables y crea sus asientos.
2. Un pago mixto crea una parte por efectivo y otra por QR, sin duplicar la entrega.
3. La reserva compromete stock y la entrega genera la salida en Kardex; los servicios no crean movimientos de stock.
4. La anulación revierte pagos, salida de inventario y asientos asociados.
5. Los asientos confirmados deben tener debe y haber iguales.

La auditoría local realizada el 10 de septiembre no encontró facturas con saldo inconsistente, asientos confirmados desbalanceados, Kardex confirmados sin movimiento auxiliar ni existencias negativas en almacenes que no las permiten.

## Esquema y migraciones

Se registró la migración `2026_09_07_000002_add_logo_path_to_conf_empresas.php`. La migración comprueba si la columna existe antes de crearla, por lo que puede aplicarse con seguridad cuando el esquema ya la contiene.

Verificar el estado antes de desplegar:

```powershell
php artisan migrate:status
```

## Validación automatizada

Validado tras la actualización a Laravel 13:

- Rutas del panel, cachés y componentes de Filament: correctos.
- Pruebas unitarias: 5 correctas, 44 aserciones.
- Pruebas JavaScript: 8 correctas.
- Integración ventas, pagos, inventario, Kardex y asientos: 19 pruebas correctas, incluidas venta al contado, pago mixto, reversión, stock negativo, servicios y transferencias internas.

Ejecutado durante esta actualización:

- 27 pruebas de integración financiera, 160 aserciones.
- 5 pruebas unitarias, 44 aserciones.
- 8 pruebas JavaScript de ventas e inventario de captura.
- Lint PHP y compilación de vistas Blade.

Para ejecutar pruebas de datos en Windows:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 artisan test
node --test tests/js/ventas-importes.test.cjs tests/js/barcode-scanner.test.cjs
```

Las pruebas que inspeccionan de forma recursiva la estructura interna de esquemas Filament 4 pueden consumir mucha memoria. Las pruebas deben comprobar estados, importes y efectos persistidos, en lugar de depender de contenedores internos del esquema.
