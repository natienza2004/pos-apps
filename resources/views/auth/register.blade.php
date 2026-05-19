<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | StockFlow</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #f8fafc; color: #0f172a; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        .auth { width: min(440px, calc(100% - 32px)); background: #fff; border: 1px solid #d9dee7; border-radius: 10px; box-shadow: 0 12px 36px rgba(15,23,42,.12); padding: 32px; }
        .brand { display: flex; align-items: center; gap: 10px; margin-bottom: 26px; font-weight: 900; }
        .mark { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; background: #4f36f5; color: #fff; }
        h1 { margin: 0; font-size: 28px; letter-spacing: -.03em; }
        .sub { margin: 8px 0 24px; color: #64748b; }
        label { display: block; margin-top: 16px; font-size: 12px; font-weight: 800; }
        input { width: 100%; min-height: 42px; margin-top: 8px; border: 1px solid #bfc8d7; border-radius: 7px; padding: 0 12px; font: inherit; outline-color: #4f36f5; }
        .btn { width: 100%; height: 42px; margin-top: 24px; border: 0; border-radius: 7px; background: #4f36f5; color: #fff; font-weight: 900; cursor: pointer; }
        .foot { margin-top: 18px; text-align: center; color: #64748b; font-size: 13px; }
        a { color: #4f36f5; font-weight: 800; text-decoration: none; }
        .error { margin-top: 14px; padding: 12px; border-radius: 7px; background: #fff1f2; color: #9f1239; font-size: 13px; }
        .hint { margin-top: 8px; color: #64748b; font-size: 12px; line-height: 1.4; }
    </style>
</head>
<body>
    <main class="auth">
        <div class="brand"><span class="mark">S</span><span>StockFlow</span></div>
        <h1>Create account</h1>
        <p class="sub">Register a staff account for the POS system.</p>

        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <label>Name
                <input name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>
            <label>Password
                <input type="password" name="password" autocomplete="new-password" required>
                <div class="hint">Use at least 8 characters with letters and numbers.</div>
            </label>
            <label>Confirm Password
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>
            <button class="btn" type="submit">Create Account</button>
        </form>

        <div class="foot">Already registered? <a href="{{ route('login') }}">Login</a></div>
    </main>
</body>
</html>
