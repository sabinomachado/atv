<x-layouts.app title="Calendário do torneio">
    <div class="space-y-5 sm:space-y-6">
        <div class="space-y-3 sm:space-y-0 sm:flex sm:items-center sm:justify-between sm:gap-4">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/torneio-serrana-logo.png') }}" alt="2º Torneio Serrana de Tênis" class="h-12 sm:h-20 w-auto shrink-0">
                <div>
                    <h1 class="text-lg sm:text-2xl font-bold text-brand-navy leading-tight">Calendário do torneio</h1>
                    <p class="text-slate-600 text-sm hidden sm:block">Todos os jogos já marcados.</p>
                </div>
            </div>
            <a href="{{ route('player.login') }}" class="block text-center sm:inline-block w-full sm:w-auto rounded bg-brand-orange hover:bg-brand-orange-dark text-white font-semibold px-4 py-2.5 sm:py-2 text-sm">
                Marcar meu jogo
            </a>
        </div>

        <form method="GET" class="grid grid-cols-2 sm:flex sm:flex-wrap sm:items-end gap-3 sm:gap-4 bg-white rounded-lg shadow p-4">
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Quadra</label>
                <select name="court_id" onchange="this.form.submit()" class="w-full sm:w-auto rounded border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange">
                    <option value="">Todas</option>
                    @foreach ($courts as $court)
                        <option value="{{ $court->id }}" @selected(request('court_id') == $court->id)>{{ $court->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Categoria</label>
                <select name="category_id" onchange="this.form.submit()" class="w-full sm:w-auto rounded border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full sm:w-auto rounded border-slate-300 text-sm focus:border-brand-orange focus:ring-brand-orange">
                    <option value="">Todos</option>
                    <option value="scheduled" @selected(request('status') === 'scheduled')>Agendados</option>
                    <option value="completed" @selected(request('status') === 'completed')>Realizados</option>
                </select>
            </div>
            @if (request()->filled('court_id') || request()->filled('category_id') || request()->filled('status'))
                <a href="{{ route('public.schedule') }}" class="flex items-center text-sm text-slate-500 hover:underline">Limpar filtros</a>
            @endif
        </form>

        @if ($matches->isEmpty())
            <p class="text-sm text-slate-500">Nenhum jogo marcado ainda.</p>
        @else
            <div class="space-y-5 sm:space-y-6">
                @foreach ($matches as $day => $dayMatches)
                    <div>
                        <h2 class="text-sm font-bold text-brand-navy uppercase mb-2">
                            {{ \Illuminate\Support\Carbon::parse($day)->translatedFormat('l, d \d\e F') }}
                        </h2>
                        <div class="space-y-2">
                            @foreach ($dayMatches as $match)
                                <div class="bg-white rounded-lg shadow p-4">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="inline-block text-xs font-semibold uppercase bg-brand-navy text-white rounded px-2 py-0.5">
                                            {{ $match->category?->name ?? '—' }}
                                        </span>
                                        <span class="text-sm font-semibold text-brand-navy shrink-0">
                                            {{ $match->scheduled_at->format('H:i') }}
                                        </span>
                                    </div>
                                    <p class="font-medium">
                                        <span class="{{ $match->winner_player_id === $match->player1_id ? 'font-bold text-brand-green' : '' }}">{{ $match->player1?->name ?? 'Jogador removido' }}</span>
                                        x
                                        <span class="{{ $match->winner_player_id === $match->player2_id ? 'font-bold text-brand-green' : '' }}">{{ $match->player2?->name ?? 'Jogador removido' }}</span>
                                    </p>
                                    <div class="flex items-center justify-between gap-2 text-sm text-slate-600">
                                        <span>{{ $match->court?->name ?? 'Quadra a definir' }}</span>
                                        @if ($match->formattedScore())
                                            <span class="font-semibold text-brand-navy">{{ $match->formattedScore() }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.app>
