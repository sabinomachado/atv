<?php

namespace App\Livewire;

use App\Models\Player;
use Livewire\Component;

class PlayerLogin extends Component
{
    public string $phone = '';

    public function updatedPhone(): void
    {
        $digits = Player::normalizePhone($this->phone);

        if (in_array(strlen((string) $digits), [10, 11], true)) {
            $this->phone = Player::maskPhone($digits);
        }
    }

    public function login(): void
    {
        $this->validate([
            'phone' => ['required', 'string'],
        ]);

        $player = Player::where('phone', Player::normalizePhone($this->phone))->first();

        if (! $player) {
            $this->addError('phone', 'Telefone não encontrado. Fale com a organização do torneio.');

            return;
        }

        session()->put('player_id', $player->id);
        session()->regenerate();

        $this->redirect(route('player.dashboard'));
    }

    public function render()
    {
        return view('livewire.player-login');
    }
}
