# Referencia DashStack: commit 660bc7c

Esta nota conserva las decisiones visuales e integraciones del commit
`660bc7c6112d4ee23bd75928b4b00449eeacb176` antes de retirar el paquete externo.

## Comportamiento retenido

- Paleta institucional primaria `#009BA4` y secundaria `#3066BE`.
- Fuente Nunito Sans, escala compacta de interfaz del 88%, navegación lateral
  plegable, grupos colapsables y breadcrumbs ocultos.
- Superficies claras `#F5F6FA` y blancas, tarjetas y listas con radios amplios,
  tabla con cabecera suave, botones compactos, búsqueda redondeada y estado
  activo de navegación con color institucional.
- Modo oscuro con lienzo `#100C1D`, superficie `#262432` y superficie elevada
  `#3D3B48`.
- Inicio de sesión con fondo de Sistema, superposición oscura y tarjeta de
  cristal translúcida y desenfocada.
- Patrón SVG para tarjetas de tablas en cuadrícula.

## Implementación nativa

El paquete `nuxtifyts/dash-stack-theme` fue retirado. Las reglas se mantienen en
`resources/css/filament/dashboard/theme.css` y `login.css`, que importan el
CSS oficial de Filament 4. El panel registra ese tema directamente con
`viteTheme()`, sin plugins de terceros.

`App\Support\PanelAppearance` obtiene los valores de `conf_parametros` de
forma tolerante a migraciones pendientes. El recurso Sistema > Parámetros
Generales permite configurar color primario, secundario, escala, estilo de
login, logo, favicon y fondo de acceso. Los valores predeterminados conservan
la apariencia del commit de referencia.
