# Claude Mem en este equipo

Instalado el 2026-10-05: versión 13.31.0, integraciones Claude Code y Codex CLI. Es una herramienta de desarrollo del usuario, no una dependencia Laravel ni un servicio de producción de SIA.

## Ubicación y configuración

- Fuente: `%USERPROFILE%\.claude\plugins\marketplaces\thedotmack`.
- Caché Codex: `%USERPROFILE%\.codex\plugins\cache\claude-mem-local\claude-mem\13.31.0`.
- Datos locales: `%USERPROFILE%\.claude-mem`; SQLite `claude-mem.db`, configuración `settings.json` y logs.
- Proveedor de compresión: `claude`, cuenta autenticada de Claude. Consume el plan de esa cuenta; la instalación no confirma que sus credenciales funcionen.
- Sincronización cloud desactivada por el instalador. No se activó CMEM Pro.
- `claude auth status`, comprobado el 2026-10-05: `loggedIn=false`. Ejecutar `claude auth login` antes de validar compresión automática.
- Worker local: `http://127.0.0.1:37777`; salud: `/api/health`.

```powershell
npx.cmd --yes claude-mem@13.31.0 start
Invoke-RestMethod http://127.0.0.1:37777/api/health
```

## Activación en nuevas sesiones

Abrir una nueva sesión en `D:\Sistemas\SIA`. En Codex CLI, el instalador indica revisar y confiar los cinco hooks cuando el cliente lo solicite y reiniciar las sesiones anteriores. Esa confianza se gestiona desde el cliente; no se eludió aquí. La instalación en CLI no demuestra captura automática en todos los clientes gráficos ni en esta sesión ya abierta.

El wrapper `claude.ps1` de OpenClaw se reparó el 2026-10-05 ejecutando el `install.cjs` del paquete ya instalado. `claude --version` responde 2.1.285. Queda pendiente validar captura y compresión en una sesión nueva con autenticación funcional.

## Memoria disponible para otros modelos

Leer primero `AGENTS.md`, `PROYECTO.md` y `CONTINUIDAD.md`. Esos archivos se pueden compartir mediante Git. La base de Claude Mem permanece en este equipo y sus hooks solo operan en clientes compatibles habilitados. Un modelo nuevo no recibe automáticamente toda la base ni los secretos del proyecto.

Actualizar los hechos duraderos en los archivos compartidos aunque también queden observaciones en Claude Mem. Para diagnóstico, verificar salud, logs, autenticación del proveedor y captura de una nueva sesión; distinguir instalación de funcionamiento completo.

Referencia: [instalación oficial](https://github.com/thedotmack/claude-mem/blob/main/docs/public/installation.mdx).
