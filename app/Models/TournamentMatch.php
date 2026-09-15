<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'category_id',
        'player1_id',
        'player2_id',
        'court_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'score',
        'winner_player_id',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
            'score' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'player2_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(Player::class, 'winner_player_id');
    }

    /**
     * Renders the stored sets as "7-6, 6-7, 10-8", or null if no result yet.
     */
    public function formattedScore(): ?string
    {
        if (blank($this->score)) {
            return null;
        }

        return collect($this->score)
            ->map(fn (array $set) => "{$set['player1']}-{$set['player2']}")
            ->join(', ');
    }

    public function scopeForPlayer(Builder $query, int $playerId): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('player1_id', $playerId)->orWhere('player2_id', $playerId));
    }

    public function involvesPlayer(int $playerId): bool
    {
        return $this->player1_id === $playerId || $this->player2_id === $playerId;
    }

    public function opponentFor(Player $player): ?Player
    {
        return $this->player1_id === $player->id ? $this->player2 : $this->player1;
    }
}
