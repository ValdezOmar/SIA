<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error 500 - Error interno del servidor</title>
    <script>
        let seconds = 10;
        function countdown() {
            if (seconds <= 0) {
                window.location.href = "{{ url('/dashboard') }}"; // cambia la ruta si tu main es otra
            } else {
                document.getElementById("countdown").innerText = seconds;
                seconds--;
                setTimeout(countdown, 1000);
            }
        }
        window.onload = countdown;
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            max-width: 500px;
        }
        h1 {
            font-size: 6rem;
            margin: 0;
            color: #facc15;
        }
        p {
            margin: 10px 0;
            font-size: 1.2rem;
        }
        .redirect {
            margin-top: 20px;
            font-size: 1rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>500</h1>
        <p>Ups... Ocurrió un error inesperado en el servidor.</p>
        <div class="redirect">
            Serás redirigido al inicio en <span id="countdown">10</span> segundos...
        </div>
    </div>
</body>
</html>
