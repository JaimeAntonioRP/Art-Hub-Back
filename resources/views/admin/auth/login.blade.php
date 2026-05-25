<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso — ArtHub Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: #0d1117; color: #e6edf3; min-height: 100vh;
               display: flex; align-items: center; justify-content: center; }
        .login-card {
            background: #161b22; border: 1px solid #30363d; border-radius: 12px;
            padding: 40px 36px; width: 100%; max-width: 380px;
        }
        .brand { text-align: center; margin-bottom: 32px; }
        .brand .logo { font-size: 28px; font-weight: 700; color: #c9a84c; letter-spacing: -0.5px; }
        .brand .sub  { font-size: 12px; color: #8b949e; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        label { font-size: 12.5px; color: #8b949e; }
        input {
            background: #0d1117; border: 1px solid #30363d; color: #e6edf3;
            border-radius: 6px; padding: 10px 12px; font-size: 14px; width: 100%;
            font-family: inherit;
        }
        input:focus { outline: none; border-color: #c9a84c; }
        .btn {
            width: 100%; padding: 11px; background: #c9a84c; color: #0d1117;
            border: none; border-radius: 6px; font-size: 14px; font-weight: 600;
            cursor: pointer; margin-top: 8px;
        }
        .btn:hover { background: #d4b45a; }
        .error { background: rgba(218,54,51,.15); border: 1px solid rgba(218,54,51,.4);
                 color: #f85149; padding: 10px 14px; border-radius: 6px; font-size: 13px;
                 margin-bottom: 16px; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="brand">
            <div class="logo">ArtHub</div>
            <div class="sub">Panel Administrativo</div>
        </div>

        @if($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}">
            @csrf
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Ingresar</button>
        </form>
    </div>
</body>
</html>
