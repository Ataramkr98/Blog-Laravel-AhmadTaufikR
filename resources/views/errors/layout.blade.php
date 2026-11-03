<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('heading') — BioLearn</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f8faf9;color:#172c25;font:16px/1.7 system-ui,sans-serif;min-height:100vh;display:grid;place-items:center;padding:24px}main{max-width:560px;background:white;border:1px solid #dce7e0;border-radius:28px;padding:clamp(24px,6vw,48px);box-shadow:0 12px 40px #123d2410}p{color:#455b50}h1{font-size:clamp(24px,5vw,34px);line-height:1.25}small{font-weight:700;letter-spacing:.18em;color:#047857}a{display:inline-flex;align-items:center;min-height:44px;border-radius:12px;padding:10px 18px;background:#047857;color:white;text-decoration:none;font-weight:600;margin-top:12px}a:focus-visible{outline:3px solid #047857;outline-offset:4px}
    </style>
</head>
<body><main><small>BIOLEARN · @yield('code')</small><h1>@yield('heading')</h1><p>@yield('message')</p><a href="{{ url('/') }}">Kembali ke Beranda</a></main></body>
</html>
