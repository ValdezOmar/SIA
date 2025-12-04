<!DOCTYPE html>
<html lang="es" class="filament">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 500 - Error interno del servidor</title>
    
    <!-- Incluir estilos de Filament para consistencia -->
    @if(config('filament.dark_mode'))
        <style>
            :root { color-scheme: dark; }
        </style>
    @endif
    
    <script>
        let seconds = 10;
        function countdown() {
            const countdownElement = document.getElementById("countdown");
            if (!countdownElement) return;
            
            if (seconds <= 0) {
                // Redirigir al dashboard de Filament
                window.location.href = "{{ filament()->getUrl() }}";
            } else {
                countdownElement.innerText = seconds;
                seconds--;
                setTimeout(countdown, 1000);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            countdown();
            
            // Actualizar hora y fecha
            function updateDateTime() {
                const now = new Date();
                const dateElement = document.getElementById('current-date');
                const timeElement = document.getElementById('current-time');
                
                if (dateElement) {
                    dateElement.textContent = now.toLocaleDateString('es-ES', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                }
                
                if (timeElement) {
                    timeElement.textContent = now.toLocaleTimeString('es-ES', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });
                }
            }
            
            updateDateTime();
            setInterval(updateDateTime, 1000);
        });
        
        // Función para copiar información de error
        function copyErrorInfo() {
            const errorId = document.getElementById('error-id')?.textContent || 'N/A';
            const timestamp = document.getElementById('current-date')?.textContent + ' ' + 
                            document.getElementById('current-time')?.textContent;
            
            const errorInfo = `Error 500 - Internal Server Error\n` +
                            `Error ID: ${errorId}\n` +
                            `Timestamp: ${timestamp}\n` +
                            `URL: ${window.location.href}\n` +
                            `User Agent: ${navigator.userAgent}\n` +
                            `---\n` +
                            `Please contact support if this issue persists.`;
            
            navigator.clipboard.writeText(errorInfo).then(() => {
                const copyBtn = document.querySelector('.copy-btn');
                const originalText = copyBtn.innerHTML;
                
                copyBtn.innerHTML = `
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    ¡Copiado!
                `;
                copyBtn.classList.add('copied');
                
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                    copyBtn.classList.remove('copied');
                }, 2000);
            });
        }
        
        // Función para mostrar detalles técnicos
        function toggleTechnicalDetails() {
            const details = document.getElementById('technical-details');
            const toggleBtn = document.querySelector('.details-btn');
            
            if (details.style.maxHeight) {
                details.style.maxHeight = null;
                toggleBtn.innerHTML = `
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                    Ver detalles técnicos
                `;
                toggleBtn.classList.remove('active');
            } else {
                details.style.maxHeight = details.scrollHeight + "px";
                toggleBtn.innerHTML = `
                    <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                    </svg>
                    Ocultar detalles
                `;
                toggleBtn.classList.add('active');
            }
        }
    </script>
    
    <style>
        :root {
            --primary-50: 236 253 245;
            --primary-600: 5 150 105;
            --primary-700: 4 120 87;
            --warning-400: 251 191 36;
            --warning-600: 217 119 6;
            --gray-50: 249 250 251;
            --gray-100: 243 244 246;
            --gray-900: 17 24 39;
            --danger-400: 248 113 113;
            --danger-600: 220 38 38;
        }
        
        .dark {
            --primary-50: 4 47 46;
            --primary-600: 20 184 166;
            --primary-700: 15 118 110;
            --warning-400: 251 191 36;
            --warning-600: 217 119 6;
            --gray-50: 31 41 55;
            --gray-100: 17 24 39;
            --gray-900: 243 244 246;
            --danger-400: 248 113 113;
            --danger-600: 239 68 68;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgb(var(--gray-100)), rgb(var(--gray-50)));
            color: rgb(var(--gray-900));
            position: relative;
            overflow-x: hidden;
            line-height: 1.5;
            transition: background-color 0.3s ease;
        }
        
        .dark body {
            background: linear-gradient(135deg, rgb(15, 23, 42), rgb(30, 41, 59));
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(var(--danger-400), 0.15) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(var(--warning-400), 0.1) 0%, transparent 40%),
                url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%239C92AC' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            animation: background-pulse 20s ease-in-out infinite;
            z-index: 0;
        }
        
        @keyframes background-pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .error-container {
            position: relative;
            z-index: 10;
            max-width: 40rem;
            width: 100%;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 1.5rem;
            box-shadow: 
                0 25px 50px -20px rgba(0, 0, 0, 0.2),
                0 15px 30px -15px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
        }
        
        .dark .error-container {
            background: rgba(30, 41, 59, 0.97);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 25px 50px -20px rgba(0, 0, 0, 0.4),
                0 15px 30px -15px rgba(0, 0, 0, 0.3),
                inset 0 1px 0 0 rgba(255, 255, 255, 0.05);
        }
        
        .error-icon-container {
            position: relative;
            width: 8rem;
            height: 8rem;
            margin: 0 auto 2rem;
        }
        
        .error-icon {
            width: 100%;
            height: 100%;
            color: rgb(var(--warning-600));
            filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.15));
            animation: icon-shake 5s ease-in-out infinite;
        }
        
        @keyframes icon-shake {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            2%, 8% { transform: translateY(-5px) rotate(2deg); }
            4%, 10% { transform: translateY(5px) rotate(-2deg); }
            6% { transform: translateY(0) rotate(0deg); }
        }
        
        .error-icon-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10rem;
            height: 10rem;
            background: radial-gradient(circle, rgba(var(--warning-400), 0.2) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1;
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
            50% { transform: translate(-50%, -50%) scale(1.1); opacity: 0.5; }
        }
        
        .error-code {
            font-size: 5.5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, rgb(var(--warning-600)), rgb(var(--danger-600)));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: rgb(var(--gray-900));
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: rgb(107, 114, 128);
            margin-bottom: 2rem;
            font-size: 1.125rem;
            max-width: 32rem;
            margin-left: auto;
            margin-right: auto;
        }
        
        .dark .error-message {
            color: rgb(156, 163, 175);
        }
        
        .error-meta {
            background: linear-gradient(135deg, rgba(var(--warning-400), 0.1), rgba(var(--danger-400), 0.05));
            padding: 1.25rem;
            border-radius: 1rem;
            margin: 2rem 0;
            text-align: left;
            border-left: 4px solid rgb(var(--warning-600));
        }
        
        .dark .error-meta {
            background: linear-gradient(135deg, rgba(217, 119, 6, 0.15), rgba(220, 38, 38, 0.1));
        }
        
        .meta-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(var(--warning-400), 0.1);
        }
        
        .meta-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .meta-label {
            font-weight: 600;
            color: rgb(var(--warning-600));
        }
        
        .meta-value {
            color: rgb(var(--gray-700));
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.875rem;
        }
        
        .dark .meta-value {
            color: rgb(var(--gray-300));
        }
        
        .redirect-info {
            background: rgba(var(--primary-600), 0.1);
            padding: 1.25rem;
            border-radius: 1rem;
            margin: 2rem 0;
            border: 2px dashed rgba(var(--primary-600), 0.3);
        }
        
        .dark .redirect-info {
            background: rgba(20, 184, 166, 0.1);
        }
        
        .countdown {
            font-weight: 700;
            color: rgb(var(--primary-700));
            font-size: 1.5rem;
            display: inline-block;
            min-width: 2rem;
        }
        
        .technical-details {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease;
            background: rgba(var(--gray-100), 0.5);
            border-radius: 0.75rem;
            margin: 1rem 0;
            text-align: left;
        }
        
        .dark .technical-details {
            background: rgba(17, 24, 39, 0.5);
        }
        
        .details-content {
            padding: 1.5rem;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.875rem;
            color: rgb(var(--gray-700));
            line-height: 1.6;
        }
        
        .dark .details-content {
            color: rgb(var(--gray-300));
        }
        
        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            border-radius: 0.75rem;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid transparent;
            cursor: pointer;
            gap: 0.5rem;
            min-width: 140px;
        }
        
        .btn-primary {
            background: rgb(var(--primary-600));
            color: white;
            box-shadow: 0 4px 12px rgba(var(--primary-600), 0.3);
        }
        
        .btn-primary:hover {
            background: rgb(var(--primary-700));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--primary-600), 0.4);
        }
        
        .btn-secondary {
            background: transparent;
            color: rgb(var(--gray-700));
            border-color: rgb(var(--gray-300));
        }
        
        .btn-secondary:hover {
            background: rgb(var(--gray-100));
            transform: translateY(-2px);
            border-color: rgb(var(--gray-400));
        }
        
        .dark .btn-secondary {
            color: rgb(var(--gray-300));
            border-color: rgb(var(--gray-600));
        }
        
        .dark .btn-secondary:hover {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-500));
        }
        
        .btn-warning {
            background: rgb(var(--warning-600));
            color: white;
            box-shadow: 0 4px 12px rgba(var(--warning-600), 0.3);
        }
        
        .btn-warning:hover {
            background: rgb(var(--warning-700));
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(var(--warning-600), 0.4);
        }
        
        .btn-icon {
            width: 1.25rem;
            height: 1.25rem;
        }
        
        .copied {
            background: rgb(var(--primary-700)) !important;
        }
        
        .active {
            background: rgb(var(--gray-200));
        }
        
        .dark .active {
            background: rgb(var(--gray-800));
        }
        
        /* Evitar que aparezcan elementos de Filament detrás */
        body > :not(.error-container):not(script):not(style) {
            display: none !important;
        }
        
        @media (max-width: 640px) {
            .error-container {
                margin: 1rem;
                padding: 2rem;
            }
            
            .error-code {
                font-size: 4rem;
            }
            
            .error-icon-container {
                width: 6rem;
                height: 6rem;
            }
            
            .error-title {
                font-size: 1.5rem;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .meta-item {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
        
        /* Líneas animadas para efecto de sistema */
        .system-lines {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }
        
        .line {
            position: absolute;
            background: linear-gradient(90deg, transparent, rgba(var(--danger-400), 0.2), transparent);
            height: 1px;
            animation: line-scan 20s linear infinite;
        }
        
        @keyframes line-scan {
            0% { left: -100%; width: 50%; }
            100% { left: 100%; width: 50%; }
        }
    </style>
</head>
<body class="{{ config('filament.dark_mode') ? 'dark' : '' }}">
    <!-- Líneas de sistema animadas -->
    <div class="system-lines" id="system-lines"></div>
    
    <div class="error-container">
        <div class="error-icon-container">
            <div class="error-icon-bg"></div>
            <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <h1 class="error-code">500</h1>
        <h2 class="error-title">Error interno del servidor</h2>
        <p class="error-message">
            ¡Ups! Algo salió mal en nuestro servidor. Nuestro equipo ha sido notificado 
            y está trabajando para resolverlo lo antes posible.
        </p>
        
        <div class="error-meta">
            <div class="meta-item">
                <span class="meta-label">Error ID:</span>
                <span class="meta-value" id="error-id">ERR-{{ strtoupper(substr(md5(uniqid()), 0, 8)) }}</span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Fecha:</span>
                <span class="meta-value" id="current-date"></span>
            </div>
            <div class="meta-item">
                <span class="meta-label">Hora:</span>
                <span class="meta-value" id="current-time"></span>
            </div>
        </div>
        
        <button class="btn btn-secondary details-btn" onclick="toggleTechnicalDetails()">
            <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
            Ver detalles técnicos
        </button>
        
        <div class="technical-details" id="technical-details">
            <div class="details-content">
                <strong>Detalles del error 500:</strong><br>
                • Tipo: Internal Server Error<br>
                • Código: HTTP 500<br>
                • Servidor: {{ $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' }}<br>
                • PHP: {{ phpversion() }}<br>
                • Laravel: {{ app()->version() }}<br>
                • Ambiente: {{ app()->environment() }}<br>
                • URL: {{ request()->fullUrl() }}<br>
                • Método: {{ request()->method() }}<br>
                • IP: {{ request()->ip() }}<br><br>
                <em>Esta información puede ser útil para el soporte técnico.</em>
            </div>
        </div>
        
        <div class="redirect-info">
            <p>Redirigiendo automáticamente en <span class="countdown" id="countdown">10</span> segundos...</p>
        </div>
        
        <div class="button-group">
            <a href="{{ filament()->getUrl() }}" class="btn btn-primary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Ir al Dashboard
            </a>
            
            <button class="btn btn-warning copy-btn" onclick="copyErrorInfo()">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Copiar información
            </button>
            
            <a href="javascript:location.reload()" class="btn btn-secondary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reintentar
            </a>
        </div>
    </div>

    <script>
        // Crear líneas de sistema animadas
        document.addEventListener('DOMContentLoaded', function() {
            const linesContainer = document.getElementById('system-lines');
            
            for (let i = 0; i < 15; i++) {
                const line = document.createElement('div');
                line.className = 'line';
                
                // Posición vertical aleatoria
                const top = Math.random() * 100;
                line.style.top = `${top}%`;
                
                // Delay aleatorio para animación
                const delay = Math.random() * 20;
                line.style.animationDelay = `${delay}s`;
                
                // Opacidad aleatoria
                const opacity = Math.random() * 0.3 + 0.1;
                line.style.opacity = opacity;
                
                linesContainer.appendChild(line);
            }
            
            // Generar ID de error único
            const errorId = 'ERR-' + Math.random().toString(36).substr(2, 8).toUpperCase();
            document.getElementById('error-id').textContent = errorId;
        });
    </script>
</body>
</html>