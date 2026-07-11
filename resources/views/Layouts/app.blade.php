<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Antrian</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        bg: '#F7F8FC',
                        panel: '#FFFFFF',
                        line: '#E8EAF2',
                        ink: '#1F2937',
                        muted: '#6B7280',
                        amber: '#E8730A',
                        cyan: '#0F9B8E',
                        mint: '#12A579',
                    },
                    fontFamily: {
                        mono: ['"JetBrains Mono"', 'monospace'],
                        sans: ['Sora', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&family=Sora:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-bg font-sans antialiased min-h-screen flex flex-col text-ink">

<nav class="sticky top-0 z-50 bg-panel border-b border-line">
    <div class="max-w-7xl mx-auto px-6">
        <div class="flex justify-between h-16 items-center">
            <div class="text-lg font-semibold tracking-wide text-ink">
                Antriin
            </div>

            <div class="flex items-center gap-8 text-sm">
                <a href="{{ route('kios.index') }}"
                   class="text-muted hover:text-cyan transition-colors">Kios</a>
                <a href="{{ route('display.index') }}" target="_blank"
                   class="text-muted hover:text-cyan transition-colors">Papan Display</a>
                <a href="{{ route('panel.index') }}"
                   class="text-muted hover:text-cyan transition-colors">Panel Petugas</a>

                @auth
                    <div class="flex items-center gap-4 border-l border-line pl-6 ml-2">
                        <span class="text-xs text-muted">
                            {{ Auth::user()->name }}
                        </span>
                        <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                            @csrf
                            <button type="submit"
                                class="text-xs font-medium text-muted hover:text-amber border border-line hover:border-amber/40 rounded px-3 py-1.5 transition-colors">
                                Keluar
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</nav>

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="text-center py-4 text-muted/60 text-xs">
        &copy; {{ date('Y') }} Sistem Antrian Multi-Loket
    </footer>

    @stack('scripts')
</body>
</html>