<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#f4faf8">
    <title>@yield('code') · @yield('title') | SIA</title>
    <style>
        :root { color-scheme: light dark; --accent: @yield('accent', '#059669'); --soft: @yield('accent-soft', '#d1fae5'); --ink: #17222d; --muted: #65727f; --surface: rgba(255,255,255,.88); --line: rgba(15,23,42,.09); --page-a: #f4faf8; --page-b: #eef4fb; }
        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; overflow-x: hidden; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: var(--ink); background: radial-gradient(circle at 12% 15%, color-mix(in srgb, var(--soft) 70%, transparent), transparent 32rem), radial-gradient(circle at 86% 82%, rgba(125,211,252,.20), transparent 28rem), linear-gradient(145deg,var(--page-a),var(--page-b)); }
        .orb { position: fixed; border-radius: 999px; opacity: .34; filter: blur(2px); pointer-events: none; animation: drift 10s ease-in-out infinite alternate; }
        .orb.one { width: 10rem; height: 10rem; top: 8%; right: 8%; background: var(--soft); }
        .orb.two { width: 6rem; height: 6rem; bottom: 10%; left: 7%; background: #bae6fd; animation-delay: -4s; }
        .shell { position: relative; width: min(1100px,calc(100% - 2rem)); margin: 1rem; display: grid; grid-template-columns: minmax(0,1fr) minmax(300px,.85fr); align-items: center; gap: clamp(1rem,5vw,5rem); padding: clamp(1.5rem,5vw,4.5rem); overflow: hidden; background: var(--surface); border: 1px solid rgba(255,255,255,.78); border-radius: 2rem; box-shadow: 0 28px 80px rgba(31,58,74,.12),0 2px 8px rgba(31,58,74,.05); backdrop-filter: blur(18px); }
        .brand { display: flex; align-items: center; gap: .7rem; margin-bottom: clamp(2rem,5vw,4rem); font-weight: 800; letter-spacing: -.02em; }
        .brand-mark { width: 2.3rem; height: 2.3rem; display: grid; place-items: center; border-radius: .8rem; color: #fff; background: linear-gradient(145deg,var(--accent),#0f766e); box-shadow: 0 8px 18px color-mix(in srgb,var(--accent) 25%,transparent); }
        .eyebrow { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .75rem; border: 1px solid color-mix(in srgb,var(--accent) 16%,transparent); border-radius: 999px; color: var(--accent); background: color-mix(in srgb,var(--soft) 55%,transparent); font-size: .78rem; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .eyebrow::before { content: ""; width: .48rem; height: .48rem; border-radius: 50%; background: var(--accent); box-shadow: 0 0 0 .25rem color-mix(in srgb,var(--accent) 13%,transparent); }
        h1 { max-width: 13ch; margin: 1rem 0 .85rem; font-size: clamp(2.15rem,5vw,4.3rem); line-height: 1.02; letter-spacing: -.055em; text-wrap: balance; }
        .message { max-width: 39rem; margin: 0; color: var(--muted); font-size: clamp(1rem,1.7vw,1.14rem); line-height: 1.72; text-wrap: pretty; }
        .hint { display: flex; gap: .75rem; align-items: flex-start; max-width: 38rem; margin: 1.5rem 0 0; padding: .9rem 1rem; border: 1px solid var(--line); border-radius: 1rem; background: rgba(248,250,252,.62); color: #51606d; font-size: .9rem; line-height: 1.5; }
        .hint svg { flex: 0 0 auto; width: 1.2rem; margin-top: .08rem; color: var(--accent); }
        .actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 2rem; }
        .button { min-height: 3rem; display: inline-flex; align-items: center; justify-content: center; gap: .55rem; padding: .75rem 1.15rem; border: 1px solid var(--line); border-radius: .9rem; color: var(--ink); background: rgba(255,255,255,.72); font: inherit; font-size: .9rem; font-weight: 750; text-decoration: none; cursor: pointer; transition: transform .2s ease,box-shadow .2s ease,background .2s ease; }
        .button:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(31,58,74,.09); }
        .button:focus-visible { outline: 3px solid color-mix(in srgb,var(--accent) 28%,transparent); outline-offset: 3px; }
        .button.primary { border-color: transparent; color: #fff; background: linear-gradient(135deg,var(--accent),#0f766e); box-shadow: 0 9px 20px color-mix(in srgb,var(--accent) 22%,transparent); }
        .button svg { width: 1.15rem; height: 1.15rem; }
        .visual { position: relative; display: grid; place-items: center; min-height: 28rem; isolation: isolate; }
        .visual::before { content: ""; position: absolute; width: 78%; aspect-ratio: 1; border-radius: 50%; background: radial-gradient(circle,color-mix(in srgb,var(--soft) 75%,white),transparent 70%); filter: blur(8px); animation: breathe 4s ease-in-out infinite; z-index: -1; }
        .illustration { width: min(100%,29rem); height: auto; object-fit: contain; filter: drop-shadow(0 24px 24px rgba(48,76,92,.15)); animation: float 5.5s ease-in-out infinite; user-select: none; }
        .code { position: absolute; right: 2%; bottom: 9%; padding: .65rem .9rem; border: 1px solid rgba(255,255,255,.75); border-radius: 1rem; color: var(--accent); background: rgba(255,255,255,.82); box-shadow: 0 12px 30px rgba(31,58,74,.12); backdrop-filter: blur(12px); font-size: clamp(1.45rem,4vw,2.4rem); font-weight: 900; letter-spacing: -.05em; animation: badge 4s ease-in-out infinite; }
        @keyframes float { 0%,100% { transform: translateY(0) rotate(-.5deg); } 50% { transform: translateY(-14px) rotate(.8deg); } }
        @keyframes badge { 0%,100% { transform: translateY(0) rotate(2deg); } 50% { transform: translateY(-8px) rotate(-1deg); } }
        @keyframes breathe { 50% { transform: scale(1.08); opacity: .72; } }
        @keyframes drift { to { transform: translate3d(18px,-22px,0) scale(1.08); } }
        @media (max-width: 800px) { body { display: block; } .shell { grid-template-columns: 1fr; width: calc(100% - 1rem); margin: .5rem; border-radius: 1.5rem; } .brand { margin-bottom: 2rem; } .visual { grid-row: 1; min-height: 15rem; } .illustration { width: min(82%,20rem); } .code { right: 8%; bottom: 3%; } h1 { max-width: 16ch; } }
        @media (max-width: 480px) { .shell { padding: 1.25rem; } .actions { flex-direction: column; } .button { width: 100%; } .visual { min-height: 13rem; } }
        @media (prefers-reduced-motion: reduce) { *,*::before,*::after { scroll-behavior: auto !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; transition-duration: .01ms !important; } }
        @media (prefers-color-scheme: dark) { :root { --ink: #edf5f3; --muted: #a9b7bd; --surface: rgba(15,28,35,.88); --line: rgba(255,255,255,.11); --page-a: #0e191e; --page-b: #111d2a; } .shell { border-color: rgba(255,255,255,.08); box-shadow: 0 28px 80px rgba(0,0,0,.35); } .hint { color: #b8c5ca; background: rgba(17,32,39,.7); } .button { color: var(--ink); background: rgba(255,255,255,.06); } .code { background: rgba(14,29,35,.82); border-color: rgba(255,255,255,.12); } }
    </style>
</head>
<body>
    <div class="orb one" aria-hidden="true"></div><div class="orb two" aria-hidden="true"></div>
    <main class="shell">
        <section>
            <div class="brand"><span class="brand-mark">S</span><span>Sistema SIA</span></div>
            <div class="eyebrow">Error @yield('code')</div>
            <h1>@yield('title')</h1>
            <p class="message">@yield('message')</p>
            @hasSection('hint')
                <div class="hint"><svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg><span>@yield('hint')</span></div>
            @endif
            <div class="actions">
                @yield('actions')
                <button type="button" class="button" data-back><svg aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>Volver atrás</button>
            </div>
        </section>
        <div class="visual" aria-hidden="true"><img class="illustration" src="{{ asset('images/errors/error-guide.png') }}" alt="" width="1280" height="1280"><span class="code">@yield('code')</span></div>
    </main>
    <script>
        document.querySelector('[data-back]')?.addEventListener('click', () => {
            if (window.history.length > 1 && document.referrer) { window.history.back(); return; }
            window.location.assign(@json(url('/dashboard')));
        });
    </script>
</body>
</html>
