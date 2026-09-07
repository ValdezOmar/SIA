# Inventarios físicos

El recurso de Almacenes ahora administra sesiones por empresa, sucursal y almacén. Usa `alm_articulos`, sus códigos de barras/series y `alm_existencias`. Los datos del módulo anterior se conservan en `alm_inventarios` y se consultan desde **Registros del módulo anterior**, sin edición.

## Activación

Aplicar la migración nueva en la base de datos del sistema:

```sh
php artisan migrate --path=database/migrations/2026_09_07_000001_create_inventarios_fisicos.php
```

Se crean tres tablas: sesiones, conteos y eventos. No se cambian los saldos ni se migran automáticamente los registros antiguos, porque su empresa y almacén estaban guardados como texto y no identifican de forma fiable una sesión.

Se conservan los permisos existentes:

- `view_any_almacen::inventario`: consultar sesiones, resultados e historial.
- `update_almacen::inventario`: contar, recontar y enviar a revisión.
- `programar_inventario_almacen::inventario`: programar, iniciar, revisar, devolver, cerrar y cancelar.

Las operaciones exigen además acceso de consulta y respetan la empresa y sucursal asignadas al usuario. Los usuarios sin contexto asignado mantienen el alcance global usado por el sistema. El responsable identifica a quien coordina el conteo; otros usuarios autorizados de ese contexto pueden contar.

## Flujo

1. **Programar inventario**: elegir empresa, sucursal, almacén, fecha y responsable. Solo puede existir una sesión abierta por almacén; se pueden programar otros almacenes en paralelo.
2. **Iniciar conteo**: en la fecha programada o después. Se toma una referencia del stock físico y reservado. Se incluyen todos los artículos inventariables activos de la empresa, incluso con cero existencias, y los artículos con existencias en el almacén, aunque estén inactivos. Los artículos creados después del inicio se cuentan en una nueva sesión.
3. **Contar**: buscar por código/nombre o escanear. El escáner original reconoce códigos de artículo, modelo, códigos registrados en Artículos y números de serie del almacén. Un QR debe contener uno de esos identificadores; no interpreta enlaces o documentos QR arbitrarios. El escaneo abre el artículo y no suma cantidades automáticamente.
4. Registrar la cantidad física total, incluidas las unidades reservadas. Cero cuenta como registro realizado; vacío significa pendiente. Se admiten seis decimales. Una diferencia exige observaciones y un reconteo exige motivo. Un formulario antiguo no sobrescribe cambios guardados por otra persona.
5. **Enviar a revisión** exige 100 % de los artículos contados. Un usuario con permiso de programación revisa cada artículo y deja su conclusión; puede devolver toda la sesión a conteo, invalidando las revisiones actuales y conservándolas en la bitácora.
6. **Cerrar inventario** exige todos los artículos revisados y una conclusión de cierre. La sesión cerrada no admite nuevos conteos. Cancelar exige motivo y tampoco elimina la sesión.

## Auditoría y movimientos

Cada cambio del flujo y cada conteo/reconteo conserva usuario, fecha, valores anteriores/nuevos y motivo cuando corresponde. La bitácora no ofrece edición ni borrado; su modelo también rechaza ambas operaciones. Los registros se protegen mediante claves foráneas, sin borrado en cascada.

El stock de referencia permanece fijo. La revisión muestra el stock actual y registra ese saldo en la bitácora para ayudar a explicar movimientos posteriores al inicio. Coordinar la pausa de entradas/salidas durante el conteo: este módulo no bloquea operaciones comerciales ni registra ajustes automáticos. La diferencia es `contado - referencia`; no debe aplicarse directamente al saldo actual si hubo movimientos intermedios.

Los ajustes autorizados deben registrarse en Kardex, siguiendo su control de series, lotes y costos, y usando el código del inventario como referencia. El cierre del conteo no significa que esos ajustes se hayan realizado.

## Seguimiento

El listado conserva sesiones abiertas, cerradas y canceladas. Permite buscar por empresa/sucursal/almacén, filtrar por estado y fechas y consultar el porcentaje contado, artículos revisados y diferencias. La ficha actualiza el progreso cada 30 segundos y después de cada conteo. **Descargar conteos** genera CSV con referencia, cantidades, diferencias, usuarios y conclusiones.

El archivo anterior se limita por los nombres históricos de empresa y almacén cuando el usuario tiene contexto asignado. Los registros antiguos sin correspondencia necesitan consulta administrativa; no se inventan asociaciones con los almacenes actuales.

## Verificación

Pruebas de integración del flujo y de los componentes Livewire:

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit --filter=InventarioFisicoTest
```

Las opciones de extensión son necesarias solo si SQLite no está habilitado en el PHP local. Verificar en un teléfono el permiso de cámara y la lectura de una etiqueta real; esta parte depende del navegador y de HTTPS.
