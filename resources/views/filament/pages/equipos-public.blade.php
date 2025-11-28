<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $equipo->codigo }} - {{ strip_tags($equipo->descripcion) }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .info-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .status-badge {
            animation: pulse 2s infinite;
        }
        .image-container {
            max-height: 400px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .mini-map {
            height: 200px;
            border-radius: 8px;
            overflow: hidden;
        }
        /* Estilos para el contenido enriquecido */
        .rich-content {
            line-height: 1.6;
        }
        .rich-content ul, .rich-content ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .rich-content li {
            margin-bottom: 0.5rem;
        }
        .rich-content strong {
            font-weight: 600;
            color: #374151;
        }
        .rich-content em {
            font-style: italic;
        }
        .rich-content u {
            text-decoration: underline;
        }
        .rich-content a {
            color: #3b82f6;
            text-decoration: underline;
        }
        .rich-content a:hover {
            color: #1d4ed8;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="gradient-bg text-white py-6">
        <div class="container mx-auto px-4">
            <div class="flex justify-center items-center">
                <h1 class="text-3xl font-bold">SISTEMA DE GESTIÓN Y SOPORTE DE EQUIPOS</h1>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Tarjeta Principal del Equipo -->
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl card-hover overflow-hidden">
                <div class="md:flex">
                    <!-- Sección de Foto - MEJORADO -->
                    <div class="md:w-2/5 p-8 bg-gray-50">
                        <div class="image-container">
                            @if($equipo->foto_equipo)
                                <img src="{{ asset('storage/' . $equipo->foto_equipo) }}" 
                                     alt="Foto del equipo {{ $equipo->codigo }}"
                                     class="rounded-xl shadow-lg"
                                     onerror="this.src='{{ asset('images/default-product.jpg') }}'">
                            @else
                                <div class="text-center text-gray-400">
                                    <i class="fas fa-camera text-6xl mb-4"></i>
                                    <p class="text-lg">Imagen no disponible</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Sección de Información -->
                    <div class="md:w-3/5 p-8">
                        <!-- Header de la tarjeta -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                    {{ $equipo->codigo }}
                                </span>
                                <!-- Título con texto plano 
                                <p class="text-2xl font-bold text-gray-800 mt-2">{{ strip_tags($equipo->descripcion) }}</p>-->
                            </div>
                            <div class="text-right">
                                <span class="status-badge inline-block px-3 py-1 {{ $equipo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded-full text-sm font-semibold">
                                    {{ $equipo->activo ? '🟢 Activo' : '🔴 Inactivo' }}
                                </span>
                            </div>
                        </div>

                        <!-- Información básica en grid - TODO EN MAYÚSCULAS -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-tag text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Marca:</span>
                                    <span class="ml-2 text-gray-600 uppercase">{{ $equipo->marca ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-cube text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Modelo:</span>
                                    <span class="ml-2 text-gray-600 uppercase">{{ $equipo->modelo ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-barcode text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">N° Serie:</span>
                                    <span class="ml-2 text-gray-600 uppercase">{{ $equipo->num_serie ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-building text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Empresa:</span>
                                    <span class="ml-2 text-gray-600 uppercase">{{ $equipo->empresa->razon_social ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-users text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Cliente:</span>
                                    <span class="ml-2 text-gray-600 uppercase">{{ $equipo->cliente->razon_social ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Sucursal:</span>
                                    <span class="ml-2 text-gray-600 uppercase">{{ $equipo->direccion ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de WhatsApp - MEJORADO CON INPUT DE DESCRIPCIÓN -->
                        @if($equipo->tel_soporte)
                        <div class="mb-6 mt-6">
                            <div class="mb-4">
                                <label for="problemaDescripcion" class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-comment-dots mr-2"></i>Descripción del problema:
                                </label>
                                <textarea 
                                    id="problemaDescripcion" 
                                    name="problemaDescripcion" 
                                    rows="3" 
                                    placeholder="Por favor, describa el problema o necesidad de asistencia..."
                                    class="w-full px-3 py-2 text-gray-700 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">
                                    Si no escribe nada, se usará el mensaje por defecto.
                                </p>
                            </div>
                            
                            <button 
                                id="whatsappBtn"
                                class="inline-flex items-center justify-center w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300"
                            >
                                <i class="fab fa-whatsapp text-xl mr-3"></i>
                                Solicitar Soporte por WhatsApp
                            </button>
                            
                            <p class="text-sm text-gray-500 mt-2 text-center">
                                {{--  Número de soporte: {{ $equipo->tel_soporte }} --}}
                            </p>
                        </div>
                        @endif

                        <!-- Descripción Detallada con Formato Enriquecido -->
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>
                                Descripción Detallada:
                            </h3>
                            <div class="rich-content text-gray-700">
                                {!! $equipo->descripcion !!}
                            </div>
                        </div>

                        
                    </div>
                </div>
            </div>

            <!-- Información Adicional en Tarjetas - SOLO PARA USUARIOS LOGUEADOS -->
            @auth
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <!-- Información de Venta y Garantía - ACTUALIZADO CON NUEVOS CAMPOS -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-shopping-cart text-blue-500 mr-3"></i>
                        Información de Venta
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Tipo de Venta:</span>
                            <span class="text-gray-600 uppercase">{{ $equipo->tipo_venta ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Fecha de Entrega:</span>
                            <span class="text-gray-600">{{ $equipo->fecha_entrega ? $equipo->fecha_entrega->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Fecha de Instalación:</span>
                            <span class="text-gray-600">{{ $equipo->fecha_instalacion ? $equipo->fecha_instalacion->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        @if($equipo->fecha_devolucion)
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Fecha de Devolución:</span>
                            <span class="text-gray-600">{{ $equipo->fecha_devolucion->format('d/m/Y') }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Garantía Desde:</span>
                            <span class="text-gray-600">{{ $equipo->garantia_desde ? $equipo->garantia_desde->format('d/m/Y') : 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="font-semibold text-gray-700">Garantía Hasta:</span>
                            <span class="font-semibold {{ $equipo->garantia_hasta && $equipo->garantia_hasta->isFuture() ? 'text-green-600' : 'text-red-600' }}">
                                {{ $equipo->garantia_hasta ? $equipo->garantia_hasta->format('d/m/Y') : 'N/A' }}
                                @if($equipo->garantia_hasta)
                                    @if($equipo->garantia_hasta->isFuture())
                                        <i class="fas fa-check-circle ml-1"></i>
                                    @else
                                        <i class="fas fa-exclamation-circle ml-1"></i>
                                    @endif
                                @endif
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Información de Ubicación y Mantenimiento - CON MINI MAPA -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tools text-orange-500 mr-3"></i>
                        Ubicación y Mantenimiento
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <span class="font-semibold text-gray-700 block mb-2">Dirección:</span>
                            <span class="text-gray-600">{{ $equipo->direccion ?? 'N/A' }}</span>
                        </div>
                        
                        <!-- Mini Mapa GPS -->
                        @if($equipo->ubicacion_gps && isset($equipo->ubicacion_gps['lat']) && isset($equipo->ubicacion_gps['lng']))
                        <div>
                            <span class="font-semibold text-gray-700 block mb-2">Ubicación GPS:</span>
                            <div id="mini-map" class="mini-map"></div>
                            <div class="mt-2 text-center">
                                <a href="https://www.google.com/maps?q={{ $equipo->ubicacion_gps['lat'] }},{{ $equipo->ubicacion_gps['lng'] }}" 
                                   target="_blank"
                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-external-link-alt mr-1"></i>
                                    Abrir en Google Maps
                                </a>
                            </div>
                        </div>
                        @endif
                        
                        <div class="flex justify-between items-center pt-2 border-t border-gray-100">
                            <span class="font-semibold text-gray-700">Frec. Mantenimiento:</span>
                            <span class="text-gray-600">
                                @if($equipo->freq_mantenimiento && is_array($equipo->freq_mantenimiento))
                                    {{ $equipo->freq_mantenimiento['value'] ?? '' }} {{ $equipo->freq_mantenimiento['key'] ?? '' }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional - SOLO PARA USUARIOS LOGUEADOS -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Técnico Asignado -->
                @if($equipo->tecnico)
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-user-cog text-green-500 mr-3"></i>
                        Técnico Asignado
                    </h3>
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mr-4">
                            <i class="fas fa-user text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800 uppercase">{{ $equipo->tecnico->full_name ?? 'N/A' }}</p>
                            <p class="text-gray-600 text-sm">Técnico especializado</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Documentación -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-file-alt text-red-500 mr-3"></i>
                        Documentación
                    </h3>
                    <div class="space-y-2">
                        @if($equipo->doc_adjunto)
                        <a href="{{ asset('storage/' . $equipo->doc_adjunto) }}" 
                           target="_blank"
                           class="flex items-center text-blue-600 hover:text-blue-800 transition duration-300">
                            <i class="fas fa-download mr-2"></i>
                            Descargar documento adjunto
                        </a>
                        @else
                        <p class="text-gray-500">No hay documentos adjuntos</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Observaciones - SOLO PARA USUARIOS LOGUEADOS -->
            @if($equipo->observaciones)
            <div class="bg-white rounded-2xl shadow-lg p-6 mt-6 card-hover">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-sticky-note text-yellow-500 mr-3"></i>
                    Observaciones
                </h3>
                <p class="text-gray-700 leading-relaxed bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                    {{ $equipo->observaciones }}
                </p>
            </div>
            @endif
            @endauth

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-500">
                <p>© {{ date('Y') }} Sistema de Gestión de Equipos.</p>
                @guest
                <p class="text-sm mt-2">Algunas funciones están disponibles solo para usuarios registrados</p>
                @endguest
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Efectos de hover mejorados
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card-hover');
            
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Animación para el badge de estado
            const statusBadge = document.querySelector('.status-badge');
            if (statusBadge) {
                setInterval(() => {
                    statusBadge.style.opacity = statusBadge.style.opacity === '0.8' ? '1' : '0.8';
                }, 2000);
            }

            // Inicializar mini mapa si hay coordenadas GPS
            @if($equipo->ubicacion_gps && isset($equipo->ubicacion_gps['lat']) && isset($equipo->ubicacion_gps['lng']))
            initMiniMap();
            @endif

            // Configurar el botón de WhatsApp
            const whatsappBtn = document.getElementById('whatsappBtn');
            if (whatsappBtn) {
                whatsappBtn.addEventListener('click', function() {
                    const descripcionInput = document.getElementById('problemaDescripcion');
                    let descripcionProblema = descripcionInput.value.trim();
                    
                    // Mensaje por defecto si no se escribe nada
                    if (!descripcionProblema) {
                        descripcionProblema = "Por favor, necesito asistencia técnica para este equipo. ¡Gracias!";
                    }

                    const mensajeBase = `*SOLICITO SOPORTE TÉCNICO*

                                        *INFORMACIÓN DEL EQUIPO:*

                                        ┌─────────────────────────────
                                        │ *Marca:* {{ $equipo->marca ?? 'N/A' }}
                                        │ *Modelo:* {{ $equipo->modelo ?? 'N/A' }}
                                        │ *N° Serie:* {{ $equipo->num_serie ?? 'N/A' }}
                                        │ *Código:* {{ $equipo->codigo }}
                                        ├─────────────────────────────
                                        │ *Empresa:* {{ $equipo->empresa->razon_social ?? 'N/A' }}
                                        │ *Cliente:* {{ $equipo->cliente->razon_social ?? 'N/A' }}
                                        │ *Departamento:* {{ $equipo->sucursalRelacion->nombre ?? 'N/A' }}
                                        └─────────────────────────────

                                        *DESCRIPCIÓN DEL PROBLEMA:*
                                        ${descripcionProblema}

                                        *Enlace del equipo:* {{ url()->current() }}`;

                     // Asegurar que el número tenga código de país (ajusta el +51 según tu país)
                        let telefonoSoporte = '{{ $equipo->tel_soporte }}';
                        
                        // agregar código de país
                        if (!telefonoSoporte.startsWith('+')) {
                            // Remover cualquier espacio o caracter especial
                            telefonoSoporte = telefonoSoporte.replace(/\D/g, '');
                            // Agregar código de país 
                            telefonoSoporte = '+591' + telefonoSoporte;
                        }
                        
                        const mensajeCodificado = encodeURIComponent(mensajeBase);
                        const urlWhatsApp = `https://wa.me/${telefonoSoporte}?text=${mensajeCodificado}`;
                        
                        window.open(urlWhatsApp, '_blank');
                });
            }
        });

        function initMiniMap() {
            const lat = {{ $equipo->ubicacion_gps['lat'] }};
            const lng = {{ $equipo->ubicacion_gps['lng'] }};
            
            const map = L.map('mini-map').setView([lat, lng], 15);
            
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            // Agregar marcador
            L.marker([lat, lng])
                .addTo(map)
                .bindPopup('{{ $equipo->codigo }}<br>{{ strip_tags($equipo->cliente->razon_social) }}')
                .openPopup();
        }
    </script>
</body>
</html>