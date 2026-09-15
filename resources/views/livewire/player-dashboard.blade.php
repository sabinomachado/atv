<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-brand-navy">Olá, {{ $player->name }}</h1>
        <p class="text-slate-600 text-sm">Aqui você marca o horário dos seus jogos pendentes e acompanha os já agendados.</p>
    </div>

    @if ($successMessage)
        <div class="rounded bg-brand-green/10 border border-brand-green/30 text-brand-green px-4 py-3 text-sm">
            {{ $successMessage }}
        </div>
    @endif

    <section>
        <h2 class="text-lg font-semibold mb-3">Jogos pendentes de marcação</h2>

        @if ($this->pendingMatches->isEmpty())
            <p class="text-sm text-slate-500">Você não tem jogos pendentes no momento.</p>
        @else
            <div class="space-y-4">
                @foreach ($this->pendingMatches as $match)
                    @php $opponent = $match->opponentFor($player); @endphp
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <span class="inline-block text-xs font-semibold uppercase bg-brand-navy text-white rounded px-2 py-0.5 mb-1">
                                    {{ $match->category?->name ?? '—' }}
                                </span>
                                <p class="font-medium truncate">
                                    vs {{ $opponent?->name ?? 'A definir' }}
                                </p>
                            </div>

                            @if ($activeMatchId !== $match->id)
                                <button
                                    wire:click="openScheduler({{ $match->id }})"
                                    class="shrink-0 rounded bg-brand-orange hover:bg-brand-orange-dark text-white text-sm font-semibold px-3 py-1.5"
                                >
                                    Marcar horário
                                </button>
                            @else
                                <button wire:click="closeScheduler" class="shrink-0 text-sm text-slate-500 hover:underline">
                                    Cancelar
                                </button>
                            @endif
                        </div>

                        @if ($activeMatchId === $match->id)
                            <div class="mt-4 border-t pt-4 space-y-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Quadra</label>
                                    <select wire:model.live="courtId" class="w-full rounded border-slate-300 focus:border-brand-orange focus:ring-brand-orange">
                                        <option value="">Selecione</option>
                                        @foreach ($this->courts as $court)
                                            <option value="{{ $court->id }}">{{ $court->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium mb-1">Data</label>
                                    <div class="flex gap-2 overflow-x-auto pb-1 -mx-4 px-4 sm:mx-0 sm:px-0">
                                        @foreach ($this->dateOptions as $option)
                                            <button
                                                type="button"
                                                wire:click="selectDate('{{ $option['value'] }}')"
                                                class="flex flex-col items-center justify-center shrink-0 w-14 h-16 rounded-lg border text-xs font-semibold transition-colors
                                                    {{ $date === $option['value']
                                                        ? 'bg-brand-orange border-brand-orange text-white'
                                                        : 'border-slate-300 text-slate-700 active:border-brand-orange' }}"
                                            >
                                                <span class="uppercase">{{ $option['weekday'] }}</span>
                                                <span class="text-base leading-tight">{{ $option['day'] }}</span>
                                                <span class="uppercase">{{ $option['month'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                @error('slot')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror

                                @if ($courtId && $date)
                                    <div>
                                        <p class="text-sm font-medium mb-2">Horários disponíveis</p>

                                        @if (empty($availableSlots))
                                            <p class="text-sm text-slate-500">Nenhum horário livre nessa quadra/data. Tente outra data ou quadra.</p>
                                        @else
                                            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                                @foreach ($availableSlots as $slot)
                                                    <button
                                                        wire:click="confirmSlot('{{ $slot }}')"
                                                        class="rounded border border-brand-green text-brand-green active:bg-brand-green/10 text-sm font-semibold py-2"
                                                    >
                                                        {{ \Illuminate\Support\Carbon::parse($slot)->format('H:i') }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <section>
        <h2 class="text-lg font-semibold mb-3">Seus próximos jogos</h2>

        @if ($this->upcomingMatches->isEmpty())
            <p class="text-sm text-slate-500">Nenhum jogo marcado ainda.</p>
        @else
            <div class="space-y-2">
                @foreach ($this->upcomingMatches as $match)
                    @php $opponent = $match->opponentFor($player); @endphp
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between gap-2 mb-1">
                            <span class="inline-block text-xs font-semibold uppercase bg-brand-navy text-white rounded px-2 py-0.5">
                                {{ $match->category?->name ?? '—' }}
                            </span>
                            <span class="text-sm font-semibold text-brand-navy shrink-0">
                                {{ $match->scheduled_at->translatedFormat('d/m \à\s H:i') }}
                            </span>
                        </div>
                        <p class="font-medium truncate">vs {{ $opponent?->name ?? 'A definir' }}</p>
                        <p class="text-sm text-slate-600">{{ $match->court?->name ?? 'Quadra a definir' }}</p>
                    </div>
                @endforeach
            </div>
        @endif
    </section>
</div>
