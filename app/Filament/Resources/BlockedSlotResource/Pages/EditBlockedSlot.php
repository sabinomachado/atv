<?php

namespace App\Filament\Resources\BlockedSlotResource\Pages;

use App\Filament\Resources\BlockedSlotResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlockedSlot extends EditRecord
{
    protected static string $resource = BlockedSlotResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
