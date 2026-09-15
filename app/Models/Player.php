<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'google_form_id',
    ];

    /**
     * Phones are stored digits-only so the self-service login can match
     * numbers regardless of how they were formatted when entered/imported.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        return filled($phone) ? preg_replace('/\D+/', '', $phone) : null;
    }

    /**
     * Renders a stored digits-only phone as (xx) xxxxx-xxxx (mobile, 11
     * digits) or (xx) xxxx-xxxx (landline, 10 digits).
     */
    public static function maskPhone(?string $phone): ?string
    {
        $digits = self::normalizePhone($phone);

        if (blank($digits)) {
            return null;
        }

        return match (strlen($digits)) {
            11 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)),
            10 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6)),
            default => $digits,
        };
    }

    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => self::normalizePhone($value),
        );
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function matchesAsPlayerOne(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'player1_id');
    }

    public function matchesAsPlayerTwo(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'player2_id');
    }
}
