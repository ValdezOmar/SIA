<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('code') - @yield('title') | SIA</title>
    <style>
        :root {
            color-scheme: light dark;
            --accent: @yield('accent', '#059669');
            --soft: @yield('accent-soft', '#d1fae5');
            --ink: #17212b;
            --muted: #64717d;
            --surface: #fff;
            --line: #e7ecef;
            --page: #f4f7f6;
        }

        * { box-sizing: border-box; }

        html, body { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            min-height: 100dvh;
            display: grid;
            place-items: center;
            padding: 1rem;
            overflow-x: hidden;
            color: var(--ink);
            background: var(--page);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .card {
            width: min(100%, 40rem);
            padding: clamp(1.4rem, 4vw, 2.5rem);
            text-align: center;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 1.5rem;
            box-shadow: 0 18px 50px rgba(28, 45, 55, .08);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            color: var(--muted);
            font-size: .85rem;
            font-weight: 750;
        }

        .brand-mark {
            width: 1.8rem;
            height: 1.8rem;
            display: grid;
            place-items: center;
            border-radius: .55rem;
            color: #fff;
            background: var(--accent);
        }

        .visual {
            position: relative;
            width: min(62vw, 15rem);
            height: min(62vw, 15rem);
            display: grid;
            place-items: center;
            margin: .6rem auto 0;
        }

        .visual::before {
            content: "";
            position: absolute;
            inset: 16%;
            border-radius: 50%;
            background: var(--soft);
            opacity: .65;
            animation: pulse 3.8s ease-in-out infinite;
        }

        .visual::after {
            content: "✦";
            position: absolute;
            top: 17%;
            right: 8%;
            color: var(--accent);
            font-size: 1.35rem;
            animation: sparkle 2.6s ease-in-out infinite;
        }

        .illustration {
            position: relative;
            z-index: 1;
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 14px 14px rgba(33, 57, 69, .13));
            animation: float 5s ease-in-out infinite;
        }

        .code {
            position: relative;
            z-index: 2;
            display: inline-block;
            margin-top: -.25rem;
            color: var(--accent);
            font-size: .82rem;
            font-weight: 850;
            letter-spacing: .11em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 19ch;
            margin: .55rem auto .75rem;
            font-size: clamp(1.75rem, 5vw, 2.6rem);
            line-height: 1.12;
            letter-spacing: -.04em;
            text-wrap: balance;
        }

        .message, .hint {
            max-width: 34rem;
            margin: 0 auto;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.6;
            overflow-wrap: anywhere;
        }

        .hint {
            margin-top: .65rem;
            font-size: .88rem;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: .7rem;
            margin-top: 1.5rem;
        }

        .button {
            min-height: 2.8rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .7rem 1rem;
            border: 1px solid var(--line);
            border-radius: .8rem;
            color: var(--ink);
            background: transparent;
            font: inherit;
            font-size: .9rem;
            font-weight: 750;
            text-decoration: none;
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .button:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(28, 45, 55, .09); }
        .button:focus-visible { outline: 3px solid var(--soft); outline-offset: 3px; }
        .button.primary { color: #fff; border-color: var(--accent); background: var(--accent); }
        .button svg { width: 1.1rem; height: 1.1rem; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(-.4deg); }
            50% { transform: translateY(-9px) rotate(.5deg); }
        }

        @keyframes pulse {
            50% { transform: scale(1.08); opacity: .42; }
        }

        @keyframes sparkle {
            0%, 100% { transform: scale(.8) rotate(0); opacity: .35; }
            50% { transform: scale(1.15) rotate(18deg); opacity: 1; }
        }

        @media (max-height: 700px) and (min-width: 520px) {
            .card { padding-block: 1.25rem; }
            .visual { width: 9rem; height: 9rem; }
            h1 { font-size: 1.8rem; }
            .message { font-size: .92rem; }
        }

        @media (max-width: 480px) {
            body { align-items: start; padding: .5rem; }
            .card { margin: .5rem 0; padding: 1.25rem; border-radius: 1.2rem; }
            .actions { flex-direction: column; }
            .button { width: 100%; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
        }

        @media (prefers-color-scheme: dark) {
            :root { --ink: #edf3f2; --muted: #a7b4b9; --surface: #152126; --line: #2b3a40; --page: #0e171b; }
            .card { box-shadow: 0 18px 50px rgba(0, 0, 0, .28); }
        }
    </style>
</head>
<body>
    <main class="card">
        <div class="brand"><span class="brand-mark">S</span><span>Sistema SIA</span></div>

        <div class="visual" aria-hidden="true">
            <img class="illustration" src="/images/errors/error-guide.png" alt="" width="1254" height="1254">
        </div>

        <span class="code">Error @yield('code')</span>
        <h1>@yield('title')</h1>
        <p class="message">@yield('message')</p>

        @hasSection('hint')
            <p class="hint">@yield('hint')</p>
        @endif

        <div class="actions">@yield('actions')</div>
    </main>
</body>
</html>
