<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Access denied — {{ config('app.name', 'Areyna') }}</title>
<style>
  :root { --bg:#fff; --text:#0d0d0f; --text-muted:#71717a; }
  @media (prefers-color-scheme: dark) {
    :root { --bg:#0d0d0f; --text:#f4f4f5; --text-muted:#9b9ba1; }
  }
  * { box-sizing: border-box; }
  body {
    margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center;
    background: var(--bg); color: var(--text); text-align: center; padding: 2rem;
    font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif;
  }
  .code { font-size: 14px; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--text-muted); margin-bottom: .5rem; }
  h1 { font-size: 1.5rem; font-weight: 650; margin: 0 0 .75rem; }
  p { color: var(--text-muted); font-size: .95rem; max-width: 40ch; margin: 0 0 1.5rem; }
  a { color: var(--text); font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
</style>
</head>
<body>
    <div class="code">403</div>
    <h1>Access denied</h1>
    <p>You don't have permission to view this page.</p>
    <a href="{{ url('/') }}">Go back home</a>
</body>
</html>
