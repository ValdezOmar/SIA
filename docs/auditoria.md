# Auditoría del sistema

## Acceso y permisos

El recurso de solo lectura está en **Sistema → Control y seguridad → Auditoría** (`/dashboard/configuracion/auditorias`). `view_any_auditoria` habilita la consulta dentro del alcance empresarial; `ver_todas_auditorias` amplía la consulta a todas las empresas y sucursales. `super_admin` también tiene acceso. Los usuarios sin empresa quedan limitados a sus propios eventos. El modelo rechaza guardado y borrado por Eloquent.

## Qué registra

- Altas, modificaciones, eliminaciones y restauraciones de modelos Eloquent bajo `App\\Models`, con campos de antes y después saneados.
- Escrituras SQL detectadas (insert, replace, update, delete y truncate), incluidas operaciones con query builder. Estas entradas describen tabla y tipo de escritura, sin guardar SQL ni bindings.
- Peticiones web con ruta, método, código HTTP, duración e identificador de correlación.
- Accesos y fallos de autenticación, excepciones, mensajes de log mediante huella y trabajos de cola fallidos.

Severidades disponibles: debug, info, notice, warning, error, critical, alert y emergency. Las eliminaciones y cancelaciones/anulaciones Eloquent se marcan critical. El listado filtra por severidad, módulo, evento, usuario y fechas.

## Límites y puesta en marcha

La migración `2026_10_08_180000_create_sis_auditorias_table.php` debe ejecutarse en cada entorno antes de habilitar el módulo. Las pruebas automáticas usan SQLite en memoria; no actualizan la base del entorno desplegado.

Una operación Eloquent produce tanto una entrada de modelo como una entrada SQL. Una escritura SQL directa no incluye valores anteriores/nuevos. La auditoría comparte la conexión y transacción del negocio: un rollback puede revertir también sus entradas. Las operaciones de consola, colas u otras conexiones deben comprobar su contexto de actor; cuando no existe una sesión, el usuario aparece como sistema/sin usuario. No es un registro externo inmutable ni una garantía de captura absoluta.

Para evitar exponer datos personales o credenciales, SQL y bindings no se almacenan y los cambios de modelo usan una lista permitida de campos. Los demás valores aparecen como `[REDACTADO]`. La auditoría se desactiva con `AUDITORIA_ENABLED=false`; por defecto está habilitada.

La tabla crece con cada escritura y petición web. Definir y revisar una política de retención/archivo antes de operarla con alto volumen.
