https://web.archive.org/web/20240828000332/http://novanexasrl.com.bo/

# Documentación del Sistema SIA (Sistema Integral de Administración)

## Stack Tecnológico

### Backend
- PHP 8.3.20 — Laravel 12.8.1
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

#Para desarrollo
php artisan db:seed  
php artisan db:seed EmpleadoSeeder 
php artisan shield:generate --all
php artisan db:seed RolePermissionSeeder

# Para desarrollo (limpiar)
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear
composer dump-autoload

# Para produccion (limpiar)
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan view:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan config:clear
sudo -u www-data composer dump-autoload

# Para producción (optimizar)
php artisan optimize
php artisan view:cache
php artisan route:cache
php artisan config:cache
composer dump-autoload

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

## Configuración del Job Listener con Supervisor (Ubuntu 24.04)

Para activar el procesamiento de colas de Laravel (como exportaciones, correos, reportes, etc.), se utiliza **Supervisor** para mantener activo un worker.

### 1. Instalar Supervisor
sudo apt update
sudo apt install supervisor
# 2. Editar el archivo worker
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/SIA/artisan queue:work --sleep=3 --tries=3 --timeout=90
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/SIA/storage/logs/laravel-worker.log
stopwaitsecs=3600
##recargar y reinicar el worker
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
sudo supervisorctl status

##Personalizar el Theme
vendor\nuxtifyts\dash-stack-theme\resources\css\theme.css
