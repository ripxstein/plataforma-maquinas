<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#103b73">
    <title>Servicio temporalmente no disponible</title>

    <style>
        :root {
            color-scheme: light;
            --blue-dark: #103b73;
            --blue: #1e5aa8;
            --blue-soft: #eaf3ff;
            --border: #c7d9ee;
            --text: #1f2d3d;
            --muted: #62748a;
            --surface: #ffffff;
        }

        * { box-sizing: border-box; }
        html { min-height: 100%; }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--text);
            font-family: Figtree, Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 12% 18%, rgba(111, 168, 220, 0.24), transparent 28rem),
                radial-gradient(circle at 88% 82%, rgba(30, 90, 168, 0.13), transparent 26rem),
                linear-gradient(145deg, #f7fbff 0%, #edf5fc 52%, #e5eff9 100%);
        }

        body::before {
            position: fixed;
            inset: 0;
            z-index: -1;
            content: "";
            opacity: 0.45;
            background-image:
                linear-gradient(rgba(16, 59, 115, 0.035) 1px, transparent 1px),
                linear-gradient(90deg, rgba(16, 59, 115, 0.035) 1px, transparent 1px);
            background-size: 32px 32px;
            mask-image: linear-gradient(to bottom, black, transparent 85%);
        }

        .page {
            display: grid;
            min-height: 100vh;
            place-items: center;
            padding: 32px 20px;
        }

        .card {
            width: min(100%, 760px);
            overflow: hidden;
            border: 1px solid rgba(199, 217, 238, 0.95);
            border-radius: 24px;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 24px 70px rgba(16, 59, 115, 0.14), 0 4px 14px rgba(16, 59, 115, 0.07);
        }

        .card::before {
            display: block;
            height: 5px;
            content: "";
            background: linear-gradient(90deg, var(--blue-dark), #367fc7 58%, #79b5e8);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 22px 30px;
            border-bottom: 1px solid #e5eef7;
        }

        .brand img {
            width: 48px;
            height: 48px;
            object-fit: contain;
        }

        .brand-divider {
            width: 1px;
            height: 36px;
            background: var(--border);
        }

        .brand-name {
            margin: 0;
            color: var(--blue-dark);
            font-size: 1rem;
            font-weight: 750;
            letter-spacing: -0.01em;
        }

        .brand-subtitle {
            margin: 3px 0 0;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .content {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 28px;
            padding: 42px 48px 36px;
        }

        .icon {
            position: relative;
            display: grid;
            width: 92px;
            height: 92px;
            place-items: center;
            border: 1px solid #d6e7f8;
            border-radius: 24px;
            color: var(--blue);
            background: linear-gradient(145deg, #f4f9ff, var(--blue-soft));
            box-shadow: inset 0 1px 0 #ffffff, 0 12px 28px rgba(30, 90, 168, 0.10);
        }

        .icon svg { width: 48px; height: 48px; }

        .icon-status {
            position: absolute;
            right: -5px;
            bottom: -5px;
            display: grid;
            width: 31px;
            height: 31px;
            place-items: center;
            border: 4px solid var(--surface);
            border-radius: 50%;
            color: #ffffff;
            font-size: 0.83rem;
            font-weight: 800;
            background: #d97706;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 13px;
            padding: 6px 10px;
            border: 1px solid #d9e8f7;
            border-radius: 999px;
            color: var(--blue);
            font-size: 0.75rem;
            font-weight: 750;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            background: #f3f8fe;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #d97706;
            box-shadow: 0 0 0 4px rgba(217, 119, 6, 0.12);
        }

        h1 {
            max-width: 560px;
            margin: 0;
            color: #15365f;
            font-size: clamp(1.75rem, 4vw, 2.45rem);
            line-height: 1.12;
            letter-spacing: -0.035em;
        }

        .message {
            max-width: 570px;
            margin: 16px 0 0;
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.7;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 27px;
        }

        .button {
            display: inline-flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 11px 18px;
            border: 1px solid transparent;
            border-radius: 10px;
            font: inherit;
            font-size: 0.93rem;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
        }

        .button-primary {
            color: #ffffff;
            background: linear-gradient(135deg, var(--blue), var(--blue-dark));
            box-shadow: 0 8px 18px rgba(30, 90, 168, 0.22);
        }

        .button-secondary {
            border-color: var(--border);
            color: var(--blue-dark);
            background: #ffffff;
        }

        .button:hover { transform: translateY(-1px); }
        .button-primary:hover { box-shadow: 0 11px 24px rgba(30, 90, 168, 0.28); }
        .button-secondary:hover { background: #f6faff; }

        .button:focus-visible {
            outline: 3px solid rgba(54, 127, 199, 0.3);
            outline-offset: 3px;
        }

        .button svg { width: 18px; height: 18px; }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 17px 30px;
            border-top: 1px solid #e5eef7;
            color: #708197;
            font-size: 0.78rem;
            background: #f8fbfe;
        }

        .footer strong {
            color: #51677f;
            font-weight: 650;
        }

        @media (max-width: 640px) {
            .page { padding: 18px 14px; }
            .card { border-radius: 18px; }
            .brand { padding: 18px 20px; }
            .brand img { width: 42px; height: 42px; }
            .brand-name { font-size: 0.9rem; }

            .content {
                grid-template-columns: 1fr;
                gap: 22px;
                padding: 30px 24px 28px;
            }

            .icon {
                width: 76px;
                height: 76px;
                border-radius: 20px;
            }

            .icon svg { width: 40px; height: 40px; }
            .actions { display: grid; }

            .footer {
                align-items: flex-start;
                flex-direction: column;
                padding: 15px 20px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .button { transition: none; }
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card" aria-labelledby="error-title">
            <header class="brand">
                <img src="{{ asset('images/branding/ipn-escudo.png') }}" alt="Escudo del Instituto Politécnico Nacional">
                <span class="brand-divider" aria-hidden="true"></span>
                <div>
                    <p class="brand-name">Diseño Básico de Elementos de Máquinas</p>
                    <p class="brand-subtitle">UPIIZ · Instituto Politécnico Nacional</p>
                </div>
            </header>

            <div class="content">
                <div class="icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="7" ry="3"></ellipse>
                        <path d="M5 5v6c0 1.66 3.13 3 7 3s7-1.34 7-3V5"></path>
                        <path d="M5 11v6c0 1.66 3.13 3 7 3 1.14 0 2.22-.12 3.16-.34"></path>
                    </svg>
                    <span class="icon-status">!</span>
                </div>

                <div role="alert">
                    <div class="status">
                        <span class="status-dot" aria-hidden="true"></span>
                        Error 503
                    </div>

                    <h1 id="error-title">Servicio temporalmente no disponible</h1>
                    <p class="message">
                        No pudimos conectar con el servicio de datos. Tu información permanece segura;
                        espera un momento y vuelve a intentarlo.
                    </p>

                    <div class="actions">
                        <button class="button button-primary" type="button" onclick="window.location.reload()">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 11a8.1 8.1 0 1 0 2 5.3"></path>
                                <path d="M20 4v7h-7"></path>
                            </svg>
                            Intentar nuevamente
                        </button>

                        <a class="button button-secondary" href="/">Volver al inicio</a>
                    </div>
                </div>
            </div>

            <footer class="footer">
                <span>Si el problema continúa, comunícate con el administrador de la plataforma.</span>
                <strong>Referencia: DB-503</strong>
            </footer>
        </section>
    </main>
</body>
</html>
