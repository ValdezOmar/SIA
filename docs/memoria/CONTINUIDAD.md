# Continuidad entre sesiones y modelos

## 2026-10-05 — Preparación de memoria compartida

- Solicitud: evitar exploraciones repetidas del proyecto y facilitar relevo entre modelos; instalar Claude Mem.
- Árbol de trabajo inicialmente limpio. HEAD observado: `ddf2611c2` (estilos oscuros de login).
- Revisados inventario de archivos propios, dependencias, documentación de módulos, configuración de pruebas, rutas, proveedores, contexto de empresa, parámetros y puntos de operación de modelos financieros.
- Creada memoria compartida: `AGENTS.md`, `CLAUDE.md`, `PROYECTO.md` y este registro.
- Identificadas notas históricas contradictorias sobre stack, Vite y DashStack. El lock actual contiene Laravel 13.31.0, Filament 5.8.1 y Livewire 4.4.4.
- La revisión no constituye auditoría línea por línea ni validación de datos de producción.
- Instalado Claude Mem 13.31.0 para Claude Code y Codex CLI, con proveedor Claude y sincronización cloud desactivada. Worker verificado por `/api/health`: `status=ok`, `initialized=true`, `mcpReady=true`.
- Guardado estado inicial del proyecto en Claude Mem mediante `/api/work-state/entries`, lista `memoria-proyecto`, con referencias a la memoria versionada y stack vigente.
- Actualizados README, índice de guías y stack de documentación técnica. Validación: `git diff --check` correcto y enlaces locales de memoria existentes. No se ejecutaron suites funcionales porque no se modificó código de aplicación.
- Pendiente: abrir sesión nueva, habilitar hooks desde el cliente cuando lo solicite y validar captura/compresión con autenticación Claude funcional. El wrapper Claude de OpenClaw en PATH apunta a un ejecutable inexistente. Detalles en `CLAUDE_MEM.md`.

## 2026-10-05 — Corrección de inconsistencias

- README vigente con notas originales archivadas en `docs/historico/README-original.md`.
- Corregidas versiones, Vite, carga real del CSS por render hook, widgets, conteos físicos y responsabilidades de Kardex/trazabilidad. Notas de migración distinguen estado actual y validaciones históricas; reparada codificación de Filament 4.
- Retirada declaración duplicada de la ruta raíz; se conserva redirección a `/dashboard`.
- Reparado ejecutable Claude mediante `node install.cjs` del paquete de OpenClaw ya instalado. `claude --version`: 2.1.285. `claude auth status`: `loggedIn=false`; pendiente iniciar sesión con `claude auth login` y habilitar hooks en nueva sesión.
- Sintaxis PHP correcta. `artisan route:list` no finalizó y se canceló; no se acredita arranque completo ni suite funcional.
- Verificación aislada con Router Laravel: una sola ruta raíz, RedirectController y redirección 302 a `/dashboard`. `git diff --check` correcto.

## Formato para próximos relevos

Registrar fecha, objetivo, archivos afectados, decisiones con motivo, comandos y resultados reales, limitaciones y siguiente acción concreta. Conservar pendientes hasta resolverlos; no trasladar conclusiones antiguas como pruebas actuales.
