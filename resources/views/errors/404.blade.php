<!DOCTYPE html>
<html lang="es" class="filament">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 404 - Página no encontrada</title>
    
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
        });
    </script>
    
    <style>
        :root {
            --primary-50: 236 253 245;
            --primary-600: 5 150 105;
            --primary-700: 4 120 87;
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
            background-color: rgb(var(--gray-50));
            color: rgb(var(--gray-900));
            position: relative;
            overflow: hidden;
            line-height: 1.5;
            transition: background-color 0.3s ease;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            right: -50%;
            bottom: -50%;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(var(--danger-400), 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(var(--primary-600), 0.05) 0%, transparent 50%),
                url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M0 0h80v80H0V0zm20 20v40h40V20H20zm10 10h20v20H30V30z' fill='%239C92AC' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            animation: float 20s ease-in-out infinite;
            z-index: 0;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) rotate(0deg); }
            25% { transform: translate(-2%, 2%) rotate(0.5deg); }
            50% { transform: translate(2%, -1%) rotate(-0.5deg); }
            75% { transform: translate(-1%, -2%) rotate(0.5deg); }
        }
        
        .error-container {
            position: relative;
            z-index: 10;
            max-width: 32rem;
            width: 100%;
            padding: 3rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.25rem;
            box-shadow: 
                0 20px 40px -15px rgba(0, 0, 0, 0.15),
                0 10px 20px -10px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(0, 0, 0, 0.04);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .error-container:hover {
            transform: translateY(-4px);
            box-shadow: 
                0 25px 50px -20px rgba(0, 0, 0, 0.2),
                0 15px 25px -15px rgba(0, 0, 0, 0.1);
        }
        
        .dark .error-container {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 
                0 20px 40px -15px rgba(0, 0, 0, 0.4),
                0 10px 20px -10px rgba(0, 0, 0, 0.25);
        }
        
        .error-icon-container {
            position: relative;
            width: 7rem;
            height: 7rem;
            margin: 0 auto 2rem;
        }
        
        .error-icon {
            width: 100%;
            height: 100%;
            color: rgb(var(--danger-600));
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.1));
        }
        
        .error-icon-bg {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 8rem;
            height: 8rem;
            background: radial-gradient(circle, rgba(var(--danger-400), 0.15) 0%, transparent 70%);
            border-radius: 50%;
            z-index: -1;
        }
        
        .error-code {
            font-size: 5rem;
            font-weight: 800;
            line-height: 1;
            color: rgb(var(--danger-600));
            margin-bottom: 0.5rem;
            letter-spacing: -0.025em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .error-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: rgb(var(--gray-900));
            margin-bottom: 1rem;
        }
        
        .error-message {
            color: rgb(107, 114, 128);
            margin-bottom: 2rem;
            font-size: 1.125rem;
            max-width: 28rem;
            margin-left: auto;
            margin-right: auto;
        }
        
        .dark .error-message {
            color: rgb(156, 163, 175);
        }
        
        .redirect-info {
            background: linear-gradient(135deg, rgba(var(--danger-400), 0.1), rgba(var(--primary-600), 0.05));
            padding: 1.25rem;
            border-radius: 1rem;
            margin: 2rem 0;
            border-left: 4px solid rgb(var(--danger-600));
        }
        
        .dark .redirect-info {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(20, 184, 166, 0.1));
        }
        
        .countdown {
            font-weight: 700;
            color: rgb(var(--danger-600));
            font-size: 1.5rem;
            display: inline-block;
            min-width: 2rem;
        }
        
        .button-group {
            display: flex;
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
            border: 1px solid transparent;
            cursor: pointer;
            gap: 0.5rem;
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
        
        .btn-icon {
            width: 1.25rem;
            height: 1.25rem;
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
                font-size: 3.5rem;
            }
            
            .error-icon-container {
                width: 5rem;
                height: 5rem;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Efecto de partículas para el fondo */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(var(--danger-400), 0.1);
            border-radius: 50%;
            animation: particle-float 15s infinite linear;
        }
        
        @keyframes particle-float {
            from { transform: translateY(100vh) rotate(0deg); }
            to { transform: translateY(-100px) rotate(360deg); }
        }
    </style>
</head>
<body class="{{ config('filament.dark_mode') ? 'dark' : '' }}">
    <!-- Partículas de fondo -->
    <div class="particles" id="particles"></div>
    
    <div class="error-container">
        <div class="error-icon-container">
            <div class="error-icon-bg"></div>
            <svg class="error-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <h1 class="error-code">404</h1>
        <h2 class="error-title">Página no encontrada</h2>
        <p class="error-message">
            Lo sentimos, la página que buscas no existe o ha sido movida. 
            Verifica la URL o regresa a la página principal.
        </p>
        
        <div class="redirect-info">
            <p>Redirigiendo automáticamente en <span class="countdown" id="countdown">10</span> segundos...</p>
        </div>
        
        <div class="button-group">
            <a href="{{ filament()->getUrl() }}" class="btn btn-primary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Ir al Dashboard
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Volver atrás
            </a>
        </div>
    </div>

    <script>
        // Crear partículas dinámicas
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño aleatorio entre 2px y 6px
                const size = Math.random() * 4 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posición aleatoria
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                // Opacidad aleatoria
                particle.style.opacity = Math.random() * 0.3 + 0.1;
                
                // Animación con delay aleatorio
                const delay = Math.random() * 5;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${Math.random() * 10 + 10}s`;
                
                particlesContainer.appendChild(particle);
            }
        });
    </script>
</body>
</html>