# Memoria de trabajo de SIA

## Entrada para cualquier agente o modelo

1. Leer `docs/memoria/PROYECTO.md` y `docs/memoria/CONTINUIDAD.md` antes de explorar.
2. Consultar solamente la guía del módulo afectado en `docs/README.md` y sus archivos de implementación.
3. Revisar `git status --short` para conservar cambios existentes del usuario.
4. Al terminar, actualizar continuidad con cambios, decisiones, pruebas realmente ejecutadas y pendientes. Actualizar el mapa si cambian rutas, dependencias o reglas.

Esta memoria versionada es la referencia compartida entre modelos y equipos. Claude Mem complementa el historial local; su base de datos no reemplaza estos documentos ni garantiza que otro modelo los reciba automáticamente.

## Reglas del proyecto

- Comunicarse y documentar en español; conservar nombres y convenciones existentes.
- El código y los archivos de dependencias prevalecen sobre diagramas y notas antiguas. Stack bloqueado revisado el 2026-10-05: Laravel 13.31.0, Filament 5.8.1, Livewire 4.4.4.
- Revisar modelos y eventos antes de cambiar un Resource: pagos, reservas, entrega, Kardex y asientos tienen efectos cruzados y transacciones.
- Mantener contexto de empresa/sucursal, políticas y claves de permisos históricas. No resolver errores de acceso eliminando controles.
- Diferenciar fechas comerciales, físicas, contables y de auditoría. No reescribir asientos confirmados de forma automática.
- No editar `vendor/`, dependencias generadas ni datos históricos para implementar una funcionalidad. El paquete local se modifica en `packages/barcode-field/`.
- No almacenar secretos, contenido de `.env`, credenciales Google, datos personales o volcados de producción en memoria o documentación.
- Migraciones, seeders y regularizaciones sobre una base real requieren evaluar sus efectos; no ejecutar `migrate:fresh`, `db:wipe` o reprocesos para comprobar un cambio.
- Ejecutar verificaciones proporcionales al cambio; registrar comando, resultado y limitaciones. No presentar validaciones antiguas como recién ejecutadas.
- Usar búsquedas dirigidas (`rg`) desde el mapa; explorar toda la estructura solo cuando falte información o haya una reorganización.
