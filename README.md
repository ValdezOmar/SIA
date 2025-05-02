https://web.archive.org/web/20240828000332/http://novanexasrl.com.bo/

# Documentación del Sistema SIA (Sistema Integral de Administración)

## Stack Tecnológico

### Backend
- Laravel 12
- Filament 3 (Panel de administración)
- Filament Actions (Extensiones para Filament)
- Spatie Laravel Permissions (Gestión de permisos)
- `barryvdh/laravel-dompdf` (Generación de PDFs)
- `pxlrbt/filament-excel` (Exportación a Excel)

### Frontend
- Blade Template-sin Vite (No usar VITE para evitar compilaciones al front)
- Leaflet.js (para mapas interactivos)
- CSS/JS tradicional (sin frameworks frontend)

### Integraciones
- ZKTeco para marcaciones biométricas (No funciona se implemento en otra herramienta Python)
- GPS/Geolocalización

## Comandos para Inicialización

```bash
# Instalar dependencias de PHP
composer install

# Instalar dependencias de Node (para herramientas de desarrollo)
npm install

# Configurar ambiente (copiar .env.example y configurar)
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate

# Ejecutar migraciones y seeders
php artisan migrate --seed

# Iniciar servidor de desarrollo
php artisan serve

# Para producción (optimizar)
php artisan optimize
php artisan view:cache
php artisan route:cache
php artisan config:cache

# Módulos Principales

## Gestión de Empleados
- Registro completo de información personal y laboral
- Fotos y geolocalización
- Estados de contrato y afiliaciones

## Control de Asistencias
- Marcaciones remotas con GPS
- Justificación de marcaciones

## Reportes
- Exportación a PDF (laravel-dompdf)
- Exportación a Excel (filament-excel)
- Filtros avanzados

## Seguridad
- Autenticación de usuarios
- Roles y permisos (Spatie)
- Acceso restringido por funciones

# Consideraciones de Desarrollo
- **Sin Vite**: El sistema usa assets tradicionales para evitar compilación frontend
- **Geolocalización**: Implementada con Leaflet.js para mapas interactivos
- **Biométrico**: La integración con ZKTeco se realizó en Python por limitaciones del SDK
- **Plantillas Personalizadas**: Varios componentes Blade para Filament (avatar, mapas, etc.)
- **PDF/Excel**: Generación de reportes con estilos personalizados
# Lista de archivos importantes para modificar o actualizar
resources/
├── views/
│   ├── filament/
│   │   ├── forms/
│   │   │   ├── components/
│   │   │   │   ├── avatar-placeholder.blade.php (muestra abatar del emplead)
│   │   │   │   ├── gps-location.blade.php (Muestra el registro gps remoto de asistenacias)
│   │   │   │   └── map-picker.blade.php (Muestra el mapa para la ubicacion de croquis)
│   ├── pdf/
│   │   └── asistencias.blade.php (Archivo pdf de exportacion de asistencias)