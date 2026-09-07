# Recursos Humanos

[Índice de módulos](README.md) · Revisión: 7 de septiembre de 2026.

## Alcance actual

El módulo administra empleados, historial laboral, directorio, perfil personal y consulta/registro de asistencias. Los horarios y sus asignaciones se configuran desde el recurso de horarios del área Sistema. Esta guía no atribuye al módulo cálculos de planilla, beneficios o liquidaciones que no se hayan verificado en su implementación.

## Alta y mantenimiento del empleado

1. Registrar la identificación y datos personales del empleado.
2. Completar su historial laboral con empresa, sucursal, cargo, fechas, contrato y datos corporativos.
3. Marcar como activo el historial que corresponde a su relación laboral vigente.
4. Comprobar que el correo corporativo coincida con el correo del usuario que ingresa al sistema.
5. Asignar al usuario los permisos que correspondan a su trabajo y revisar el acceso resultante.

El historial conserva salario, seguro, observaciones y documento de respaldo cuando se registran. Al guardar una relación laboral activa, el modelo desactiva las otras relaciones activas del mismo empleado; no elimina su historial anterior. Revisar las fechas y el estado antes de realizar un cambio de empresa o sucursal.

## Empresa, sucursal y perfil del usuario

El vínculo del usuario con el empleado se obtiene mediante el correo corporativo del historial. La empresa y sucursal del usuario se derivan del historial laboral activo. Por ello, un vínculo incorrecto puede afectar los filtros de Ventas, Inventario y otros recursos, además de RRHH.

**Mi Perfil** está orientado al empleado vinculado al usuario. Si no aparece o no identifica al empleado esperado, revisar correo de acceso, correo corporativo e historial activo antes de duplicar usuarios o empleados. El directorio permite consultar los datos disponibles en su recurso según los permisos asignados.

## Horarios de asistencia

Configurar nombre, código, días laborales, hora de entrada, tolerancia, hora de omisión y las horas de almuerzo/salida cuando correspondan. El horario puede marcarse activo y predeterminado.

En **Asignaciones a empleados**, seleccionar al empleado, fecha de inicio y, si corresponde, fecha de fin. La fecha de fin no puede ser anterior al inicio. El formulario valida solapamientos de asignaciones activas; evitar crear turnos simultáneos incompatibles para el mismo empleado.

Para resolver el horario de una fecha, el servicio busca una asignación activa vigente, priorizando el inicio más reciente. Si su horario no está activo o no existe una asignación utilizable, utiliza un horario activo predeterminado. Si tampoco existe, el resultado es **sin horario**.

La configuración inicial incluida en la migración crea el horario administrativo: lunes a viernes, entrada 08:30, tolerancia 5 minutos, omisión 10:00, almuerzo 12:30–14:30 y salida 18:30. Es una configuración editable del sistema.

## Registro y consulta de asistencia

**Registrar Asistencia** abre el formulario de marcación. El flujo remoto identifica al empleado por el correo corporativo de su historial activo, asigna su CI como identificador de marcación y usa `REMOTO` cuando no se recibe otro identificador de equipo. Se conserva la localización cuando está disponible y los datos de justificación del formulario cuando corresponden.

La consulta agrupa marcaciones por empleado y fecha. El período habitual va del día **26 del mes anterior al 25 del mes seleccionado**; el final se limita a la fecha actual. Por ejemplo, septiembre comprende desde el 26 de agosto hasta el 25 de septiembre, o hasta hoy si todavía no se alcanzó el día 25.

### Evaluación de puntualidad

| Estado | Regla del servicio |
| --- | --- |
| Sin horario | No hay asignación utilizable ni horario predeterminado activo. |
| Descanso | La fecha no pertenece a los días laborales del horario. |
| Falta | Día laboral sin marcaciones recibidas para evaluar. |
| Omisión | La primera marcación supera la hora de omisión configurada. |
| Retraso | La primera marcación supera la entrada más la tolerancia. |
| Puntual | La primera marcación está dentro del límite admitido. |

Para un retraso, la duración se calcula desde la hora de entrada, no desde el fin de la tolerancia. Con entrada 08:30 y tolerancia 5 minutos, 08:35 no supera el límite; 08:36 registra 6 minutos de retraso. La comprobación de omisión se hace antes que la de retraso.

Esta evaluación utiliza la primera marcación del día. Aunque el horario contiene horas de almuerzo y salida, no debe asumirse que este método calcula por sí solo horas extra, sanciones, jornada neta o cumplimiento de todas las marcaciones.

## Permisos y reportes

La consulta de asistencias distingue alcance global, sucursal y propio. Los nombres actuales de los permisos específicos son:

- `ver_marcacion_todos_r::r::h::h::asistencia`.
- `ver_marcacion_sucursal_r::r::h::h::asistencia`.
- `ver_marcacion_propia_r::r::h::h::asistencia`.
- `exportar_pdf_r::r::h::h::asistencia` para la exportación.

La acción **Exportar a PDF** usa el período y el contexto de consulta. El reporte de asistencia utiliza el logo general del sistema. El logo por empresa incorporado para inventarios no implica que todos los demás reportes ya lo utilicen.

## Datos e instalación

La migración original [2026_08_22_000000_create_rh_horarios_asistencia_tables.php](../database/migrations/2026_08_22_000000_create_rh_horarios_asistencia_tables.php) crea `rh_horarios_asistencia` y `rh_asignaciones_horario_asistencia`, sus relaciones, índice y horario inicial.

La migración de reparación del 02/09/2026 fue retirada porque repetía esa estructura. En una instalación limpia se utiliza la original. Borrar una tabla manualmente no elimina su registro en la tabla `migrations`; el procedimiento de reconstrucción debe contemplar ambas cosas. La instalación habitual se realiza mediante las migraciones pendientes, sin ejecutar una reconstrucción destructiva sobre una base con datos.

El README del proyecto identifica la captura de ZKTeco como una integración trasladada a otra herramienta Python. No considerar comprobada una conexión biométrica directa desde este módulo sin revisar esa herramienta y su carga de marcaciones.

## Diagnóstico y comprobación manual

| Situación | Qué revisar |
| --- | --- |
| No aparece Mi Perfil | Coincidencia entre usuario, empleado y correo del historial activo. |
| Se ve otra empresa/sucursal | Historial activo del empleado y permisos efectivos. |
| Marcación sin empleado | CI de marcación y vínculo corporativo del usuario. |
| Estado sin horario | Asignación vigente y existencia de horario predeterminado activo. |
| Retraso inesperado | Hora de entrada, tolerancia, primera marcación y zona horaria configurada. |
| Falta una fecha en la consulta | Período 26–25, fecha actual, filtros y alcance de permisos. |
| Error de tabla de asignaciones | Estado de la migración original y presencia de ambas tablas. |

Comprobar con un empleado de prueba: asignación vigente, día de descanso, día sin marcación, entrada dentro de tolerancia, retraso y omisión. Verificar también los alcances propio/sucursal/global y el período exportado a PDF. No se identificó una suite específica de RRHH entre las pruebas de integración revisadas; estas comprobaciones se documentan como pendientes de automatizar, no como pruebas ya ejecutadas.

## Referencias técnicas

- [Empleado](../app/Models/RRHH/Empleado.php), [HistorialLaboral](../app/Models/RRHH/HistorialLaboral.php) y [User](../app/Models/User.php).
- [AsistenciaResource](../app/Filament/Resources/RRHH/AsistenciaResource.php) y [registro desde el listado](../app/Filament/Resources/RRHH/AsistenciaResource/Pages/ListAsistencias.php).
- [AsistenciaHorarioService](../app/Services/RRHH/AsistenciaHorarioService.php).
- [Horarios](../app/Filament/Clusters/Sistema/Resources/HorarioAsistenciaResource.php) y [asignaciones](../app/Filament/Clusters/Sistema/Resources/HorarioAsistenciaResource/RelationManagers/AsignacionesRelationManager.php).
- [PerfilEmpleadoResource](../app/Filament/Resources/RRHH/PerfilEmpleadoResource.php).
