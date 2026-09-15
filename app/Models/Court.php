<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Court extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'available_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'available_days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Only the courts users are allowed to pick from — inactive courts
     * (e.g. temporarily out of use) are hidden from listings.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class);
    }

    public function blockedSlots(): HasMany
    {
        return $this->hasMany(BlockedSlot::class);
    }

    /**
     * Whether this court operates on the given day of week.
     * A null available_days means the court is available every day.
     */
    public function isAvailableOnDay(string $dayOfWeek): bool
    {
        if (empty($this->available_days)) {
            return true;
        }

        return in_array(strtolower($dayOfWeek), array_map('strtolower', $this->available_days), true);
    }

    /**
     * Portuguese labels for the English day-of-week keys stored in
     * available_days (kept in English internally to match Carbon's
     * locale-independent format('l') used by isAvailableOnDay()).
     *
     * @return array<string, string>
     */
    public static function dayLabels(): array
    {
        return [
            'monday' => 'Segunda',
            'tuesday' => 'Terça',
            'wednesday' => 'Quarta',
            'thursday' => 'Quinta',
            'friday' => 'Sexta',
            'saturday' => 'Sábado',
            'sunday' => 'Domingo',
        ];
    }
}
