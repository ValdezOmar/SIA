<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $equipo->codigo }} - {{ $equipo->descripcion }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <h1 class="text-3xl font-bold">SISTEMA DE GESTION Y SOPORTE DE EQUIPOS</h1>
                
            </div>
        </div>
    </header>

    <div class="container mx-auto px-4 py-8">
        <!-- Tarjeta Principal del Equipo -->
        <div class="max-w-6xl mx-auto">
            <div class="bg-white rounded-2xl shadow-xl card-hover overflow-hidden">
                <div class="md:flex">
                    <!-- Sección de Foto -->
                    <div class="md:w-2/5 p-8 flex items-center justify-center bg-gray-50">
                        @if($equipo->foto_equipo)
                            <img src="{{ asset('storage/' . $equipo->foto_equipo) }}" 
                                 alt="Foto del equipo {{ $equipo->codigo }}"
                                 class="w-full h-64 object-cover rounded-xl shadow-lg">
                        @else
                            <div class="text-center text-gray-400">
                                <i class="fas fa-camera text-6xl mb-4"></i>
                                <p class="text-lg">Imagen no disponible</p>
                            </div>
                        @endif
                    </div>

                    <!-- Sección de Información -->
                    <div class="md:w-3/5 p-8">
                        <!-- Header de la tarjeta -->
                        <div class="flex justify-between items-start mb-6">
                            <div>
                                <span class="inline-block px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                                    {{ $equipo->codigo }}
                                </span>
                                <h2 class="text-2xl font-bold text-gray-800 mt-2">{{ $equipo->descripcion }}</h2>
                            </div>
                            <div class="text-right">
                                <span class="status-badge inline-block px-3 py-1 {{ $equipo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} rounded-full text-sm font-semibold">
                                    {{ $equipo->activo ? '🟢 Activo' : '🔴 Inactivo' }}
                                </span>
                            </div>
                        </div>

                        <!-- Información básica en grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-tag text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Marca:</span>
                                    <span class="ml-2 text-gray-600">{{ $equipo->marca ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-cube text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Modelo:</span>
                                    <span class="ml-2 text-gray-600">{{ $equipo->modelo ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-barcode text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">N° Serie:</span>
                                    <span class="ml-2 text-gray-600">{{ $equipo->num_serie ?? 'N/A' }}</span>
                                </div>
                            </div>
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <i class="fas fa-building text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Empresa:</span>
                                    <span class="ml-2 text-gray-600">{{ $equipo->empresa->razon_social ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-users text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Cliente:</span>
                                    <span class="ml-2 text-gray-600">{{ $equipo->cliente->razon_social ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class="fas fa-map-marker-alt text-purple-500 w-6"></i>
                                    <span class="font-semibold text-gray-700 ml-2">Sucursal:</span>
                                    <span class="ml-2 text-gray-600">{{ $equipo->sucursalRelacion->nombre ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Botón de WhatsApp -->
                        @if($equipo->tel_soporte)
                        <div class="mb-6">
                            <a href="https://wa.me/{{ $equipo->tel_soporte }}?text={{ urlencode('Solicito soporte en el equipo ' . $equipo->codigo . ' - ' . $equipo->descripcion . '. Enlace: ' . url()->current()) }}" 
                               target="_blank"
                               class="inline-flex items-center justify-center w-full bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-4 rounded-lg transition duration-300">
                                <i class="fab fa-whatsapp text-xl mr-3"></i>
                                Solicitar Soporte por WhatsApp
                            </a>
                            <p class="text-sm text-gray-500 mt-2 text-center">
                                Número de soporte: {{ $equipo->tel_soporte }}
                            </p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información Adicional en Tarjetas -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <!-- Información de Venta y Garantía -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-shopping-cart text-blue-500 mr-3"></i>
                        Información de Venta
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Fecha de Venta:</span>
                            <span class="text-gray-600">{{ $equipo->fecha_venta ? $equipo->fecha_venta->format('d/m/Y') : 'N/A' }}</span>
                        </div>
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

                <!-- Información de Ubicación y Mantenimiento -->
                <div class="bg-white rounded-2xl shadow-lg p-6 card-hover">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-tools text-orange-500 mr-3"></i>
                        Ubicación y Mantenimiento
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Lugar:</span>
                            <span class="text-gray-600">{{ $equipo->lugar ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-gray-100">
                            <span class="font-semibold text-gray-700">Dirección:</span>
                            <span class="text-gray-600">{{ $equipo->direccion ?? 'N/A' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="font-semibold text-gray-700">Frec. Mantenimiento:</span>
                            <span class="text-gray-600">
                                @if($equipo->freq_mantenimiento && is_array($equipo->freq_mantenimiento))
                                    {{ implode(', ', $equipo->freq_mantenimiento) }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información Adicional -->
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
                            <p class="font-semibold text-gray-800">{{ $equipo->tecnico->nombre_completo ?? 'N/A' }}</p>
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

            <!-- Observaciones -->
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

            <!-- Footer -->
            <div class="text-center mt-8 text-gray-500">
                <p>© {{ date('Y') }} Sistema de Gestión de Equipos. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>

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
        });
    </script>
</body>
</html>