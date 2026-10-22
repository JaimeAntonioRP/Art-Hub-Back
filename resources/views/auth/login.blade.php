<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Ingresar · {{ $brand }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Inter:wght@400;500;600&display=swap">
    <style>
        :root {
            --bg: #F7F2EB; --surface: #FFFDF9; --text: #0D1B2A; --muted: #6A5F51;
            --border: #E2D3BA; --border-strong: #8C7C63; --accent: #CBA24A; --accent-text: #7A5C1B;
            --danger: #93462A; --danger-bg: #F6E3D8; --focus: #3F5A74;
        }
        * { box-sizing: border-box; }
        html, body { margin: 0; }
        body {
            min-height: 100vh; background: var(--bg); color: var(--text);
            font: 15px/1.55 'Inter', system-ui, -apple-system, sans-serif; -webkit-font-smoothing: antialiased;
            display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        }
        a { color: inherit; }
        .art {
            position: relative; background: #0D1B2A url('{{ $frontend }}/obras/el-reposo-del-caminante.jpg') 70% center / cover no-repeat;
        }
        .art::after { content: ""; position: absolute; inset: 0; background: linear-gradient(0deg, rgba(13,27,42,.75), rgba(13,27,42,.1) 55%); }
        .art p {
            position: absolute; left: 48px; right: 48px; bottom: 44px; z-index: 1; margin: 0;
            font-family: 'Cormorant Garamond', serif; font-size: 30px; line-height: 1.15; color: #FFFDF9; max-width: 22ch;
        }
        main { display: flex; flex-direction: column; justify-content: center; padding: 48px clamp(24px, 6vw, 88px); }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; margin-bottom: 48px; }
        .brand img { height: 44px; width: auto; }
        .brand b { font-family: 'Cormorant Garamond', serif; font-size: 24px; font-weight: 600; letter-spacing: .14em; }
        .brand small { display: block; font-size: 9px; letter-spacing: .26em; text-transform: uppercase; color: var(--accent-text); }
        h1 { font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: clamp(36px, 4vw, 48px); line-height: 1.02; margin: 0; letter-spacing: -.01em; }
        .lead { margin: 10px 0 32px; color: var(--muted); max-width: 40ch; }
        form { display: flex; flex-direction: column; gap: 18px; max-width: 400px; }
        label span { display: block; font-size: 13px; font-weight: 500; margin-bottom: 6px; }
        input[type=email], input[type=password] {
            width: 100%; font: inherit; font-size: 16px; color: var(--text); background: var(--surface);
            border: 1px solid var(--border-strong); border-radius: 6px; padding: 11px 12px;
        }
        input:focus-visible, button:focus-visible, a:focus-visible { outline: 2px solid var(--focus); outline-offset: 2px; }
        [aria-invalid=true] { border-color: var(--danger) !important; }
        .row { display: flex; justify-content: space-between; align-items: center; gap: 12px; font-size: 14px; }
        .row label { display: flex; align-items: center; gap: 8px; color: var(--muted); cursor: pointer; }
        .row input { width: 16px; height: 16px; accent-color: var(--text); margin: 0; }
        .link { color: var(--accent-text); font-weight: 500; text-underline-offset: 3px; }
        .error { margin: 0; padding: 11px 14px; border-radius: 6px; background: var(--danger-bg); color: var(--danger); font-size: 14px; }
        button {
            font: inherit; font-weight: 500; font-size: 15px; cursor: pointer; border: 0; border-radius: 6px;
            padding: 13px 20px; background: var(--accent); color: var(--text); box-shadow: 0 2px 8px rgba(203,162,74,.28);
        }
        button:hover { background: #DDBE74; }
        .foot { margin: 36px 0 0; padding-top: 24px; border-top: 1px solid var(--border); max-width: 400px; font-size: 14px; color: var(--muted); display: grid; gap: 8px; }
        @media (max-width: 860px) {
            body { grid-template-columns: 1fr; }
            .art { display: none; }
            main { padding-top: 40px; }
            .brand { margin-bottom: 36px; }
        }
    </style>
</head>
<body>
    <div class="art" aria-hidden="true"><p>Arte peruano con historia verificable.</p></div>

    <main>
        <a class="brand" href="{{ $frontend }}">
            @if ($logo)
                <img src="{{ $logo }}" alt="">
            @endif
            <span><b>{{ $brand }}</b>@if ($tagline)<small>{{ $tagline }}</small>@endif</span>
        </a>

        <h1>Ingresar</h1>
        <p class="lead">Una sola cuenta para todo: te llevamos a tu panel según tu perfil.</p>

        <form method="post" action="{{ route('login') }}" novalidate>
            @csrf

            @if ($errors->any())
                <p class="error" role="alert">{{ $errors->first() }}</p>
            @endif

            <label>
                <span>Correo</span>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus
                       @if ($errors->has('email')) aria-invalid="true" @endif>
            </label>
            <label>
                <span>Contraseña</span>
                <input type="password" name="password" autocomplete="current-password" required
                       @if ($errors->has('password')) aria-invalid="true" @endif>
            </label>

            <div class="row">
                <label><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Recordarme</label>
                <a class="link" href="{{ $frontend }}/recuperar">¿Olvidaste tu contraseña?</a>
            </div>

            <button type="submit">Ingresar</button>
        </form>

        <div class="foot">
            <span>¿Eres artista y aún no tienes cuenta? <a class="link" href="{{ $frontend }}/registro">Regístrate</a></span>
            <a href="{{ $frontend }}">← Volver al sitio</a>
        </div>
    </main>
</body>
</html>
