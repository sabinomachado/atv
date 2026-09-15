<?php

namespace App\Filament\Resources\TournamentMatchResource\Pages;

use App\Filament\Resources\TournamentMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTournamentMatches extends ListRecords
{
    protected static string $resource = TournamentMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
