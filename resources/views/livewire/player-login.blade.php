<div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
    <img src="{{ asset('images/tennis-ball-icon.png') }}" alt="" class="h-14 w-auto mb-2" aria-hidden="true">
    <h1 class="text-xl font-bold text-brand-navy mb-2">Entrar</h1>
    <p class="text-sm text-slate-600 mb-4">
        Digite o telefone cadastrado na sua inscrição do torneio para ver e marcar seus jogos.
    </p>

    <form wire:submit="login" class="space-y-4">
        <div>
            <label class="block text-sm font-medium mb-1" for="phone">Telefone</label>
            <input
                wire:model.blur="phone"
                id="phone"
                type="tel"
                placeholder="(48) 99999-8888"
                class="w-full rounded border-slate-300 focus:border-brand-orange focus:ring-brand-orange"
            >
            @error('phone')
                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="w-full bg-brand-orange hover:bg-brand-orange-dark text-white font-semibold rounded py-2">
            Entrar
        </button>
    </form>
</div>
