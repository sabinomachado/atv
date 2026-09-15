@props(['title' => null])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand-navy': '#134C8F',
                        'brand-orange': '#E47725',
                        'brand-orange-dark': '#CD5D2B',
                        'brand-green': '#015C33',
                        'brand-lime': '#D6E161',
                        'brand-cream': '#F7F2EE',
                    },
                },
            },
        }
    </script>
    @livewireStyles
</head>
<body class="bg-brand-cream text-slate-900 min-h-screen flex flex-col">
    <header class="bg-brand-cream text-brand-navy border-b-2 border-brand-lime sticky top-0 z-10">
        <div class="max-w-5xl mx-auto px-4 py-2.5 sm:py-3 flex items-center justify-between gap-3">
            <a href="{{ route('public.schedule') }}" class="flex items-center gap-3 min-w-0">
                <img src="{{ asset('images/logo-mark.png') }}" alt="ATV" class="h-9 sm:h-11 w-auto shrink-0">
                <span class="font-bold leading-tight hidden sm:block">
                    Associação dos<br>Tenistas de Valença
                </span>
            </a>
            <nav class="text-sm flex items-center gap-3 sm:gap-4 shrink-0">
                <a href="{{ route('public.schedule') }}" class="hover:text-brand-orange">Calendário</a>
                @if (session('player_id'))
                    <a href="{{ route('player.dashboard') }}" class="hover:text-brand-orange">Meus jogos</a>
                    <form method="POST" action="{{ route('player.logout') }}">
                        @csrf
                        <button type="submit" class="hover:text-brand-orange">Sair</button>
                    </form>
                @else
                    <a href="{{ route('player.login') }}" class="rounded bg-brand-orange px-3 py-1.5 font-semibold text-white hover:bg-brand-orange-dark">Sou jogador</a>
                @endif
            </nav>
        </div>
    </header>

    <main class="max-w-5xl mx-auto w-full px-4 py-5 sm:py-8 flex-1">
        {{ $slot }}
    </main>

    <footer class="text-center text-xs text-slate-400 py-4">
        Associação dos Tenistas de Valença
    </footer>

    @livewireScripts
</body>
</html>
