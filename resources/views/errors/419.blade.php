<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session expired — {{ config('app.name', 'IWSTGS') }}</title>
<style>
  :root { --bg:#fff; --text:#0d0d0f; --text-muted:#71717a; --btn-bg:#0d0d0f; --btn-text:#fff; }
  @media (prefers-color-scheme: dark) {
    :root { --bg:#0d0d0f; --text:#f4f4f5; --text-muted:#9b9ba1; --btn-bg:#f4f4f5; --btn-text:#0d0d0f; }
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
  button {
    border: none; background: var(--btn-bg); color: var(--btn-text); font: inherit; font-weight: 600;
    padding: .6rem 1.25rem; border-radius: 6px; cursor: pointer;
  }
</style>
</head>
<body>
    <div class="code">419</div>
    <h1>Your session expired</h1>
    <p>The page took too long to submit. Go back and try again.</p>
    <button type="button" onclick="history.back()">Go back</button>
</body>
</html>
