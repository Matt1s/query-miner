<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ isset($title) ? $title . ' | ' : '' }}{{ config('app.name', 'Query Miner') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <!-- Minimal fallback to keep layout usable when vite assets are not built -->
        <style>
            :root {
                --bg: #F8FAFC;
                --card: #fff;
                --muted: #6b7280;
                --accent: #7c3aed
            }

            body {
                font-family: Inter, ui-sans-serif, system-ui, Arial;
                background: var(--bg);
                margin: 0
            }

            .site-header {
                background: transparent;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid rgba(15, 23, 42, 0.04)
            }

            .container {
                max-width: 1100px;
                margin: 0 auto;
                padding: 0 1rem
            }
        </style>
    @endif

    @stack('head')
</head>

<body class="min-h-screen antialiased text-slate-800 flex flex-col justify-center">

    <main class="h-full container py-8 px-4">
        @yield('content')
    </main>

    @stack('scripts')
</body>

</html>
