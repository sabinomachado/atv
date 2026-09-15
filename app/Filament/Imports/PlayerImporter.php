<?php

namespace App\Filament\Imports;

use App\Models\Category;
use App\Models\Player;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;

class PlayerImporter extends Importer
{
    protected static ?string $model = Player::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('name')
                ->requiredMapping()
                ->rules(['required', 'string', 'max:255']),
            ImportColumn::make('phone')
                ->rules(['nullable', 'string', 'max:30']),
            ImportColumn::make('categories')
                ->label('Categorias')
                ->helperText('Separe múltiplas categorias por vírgula (ex: "A, Feminino B").')
                ->rules(['nullable', 'string']),
            ImportColumn::make('google_form_id')
                ->rules(['nullable', 'string', 'max:255']),
        ];
    }

    public function resolveRecord(): ?Player
    {
        if (filled($this->data['google_form_id'] ?? null)) {
            return Player::firstOrNew([
                'google_form_id' => $this->data['google_form_id'],
            ]);
        }

        return new Player;
    }

    protected function afterSave(): void
    {
        $rawCategories = $this->data['categories'] ?? null;

        if (blank($rawCategories)) {
            return;
        }

        $categoryIds = collect(explode(',', $rawCategories))
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->map(fn (string $name) => Category::firstOrCreate(['name' => $name])->id);

        $this->record->categories()->syncWithoutDetaching($categoryIds);
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'A importação de jogadores foi concluída: '.number_format($import->successful_rows).' '.str('linha')->plural($import->successful_rows).' importada(s).';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('linha')->plural($failedRowsCount).' falharam.';
        }

        return $body;
    }
}
