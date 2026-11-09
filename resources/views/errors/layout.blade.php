<!doctype html>
<html lang="en-IN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | Arsh Perfume</title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700&family=Poppins:wght@300;400&display=swap" rel="stylesheet">
    <style>
        :root { --ink:#080808; --gold:#C8A24D; --muted:#B8B8B8; --line:#2A2A2A; }
        @media (prefers-color-scheme: light) { :root:not([data-theme="dark"]) { } }
        body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center; background:var(--ink); color:#fff; font-family:Poppins,system-ui,sans-serif; padding:24px; box-sizing:border-box; }
        main { max-width:520px; text-align:center; }
        h1 { font-family:Cinzel,serif; font-weight:500; letter-spacing:.05em; font-size:2rem; margin:.5rem 0 1rem; }
        p { color:var(--muted); line-height:1.7; font-size:.95rem; }
        .code { color:var(--gold); letter-spacing:.3em; font-size:.8rem; text-transform:uppercase; }
        a.btn { display:inline-block; margin-top:1.5rem; border:1px solid var(--gold); color:var(--gold); padding:.75rem 1.5rem; text-decoration:none; text-transform:uppercase; letter-spacing:.15em; font-size:.8rem; }
        a.btn:hover { background:var(--gold); color:var(--ink); }
    </style>
</head>
<body>
<main>
    <p class="code">@yield('code')</p>
    <h1>@yield('heading')</h1>
    <p>@yield('message')</p>
    <a class="btn" href="{{ url('/') }}">Back to Arsh Perfume</a>
</main>
</body>
</html>
