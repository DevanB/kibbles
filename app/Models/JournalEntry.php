<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\JournalEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $game_id
 * @property-read string|null $play_session_id
 * @property-read string $body
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read Game $game
 * @property-read PlaySession|null $playSession
 */
#[Fillable([
    'body',
    'play_session_id',
])]
final class JournalEntry extends Model
{
    /** @use HasFactory<JournalEntryFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'game_id' => 'string',
            'play_session_id' => 'string',
            'body' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return BelongsTo<PlaySession, $this>
     */
    public function playSession(): BelongsTo
    {
        return $this->belongsTo(PlaySession::class);
    }

    /**
     * @return array{id: string, body: string, createdAt: string, updatedAt: string, playSessionId: string|null}
     */
    public function toWire(): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'createdAt' => $this->created_at->toIso8601String(),
            'updatedAt' => $this->updated_at->toIso8601String(),
            'playSessionId' => $this->play_session_id,
        ];
    }

    /**
     * @param  Builder<JournalEntry>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->latest()->orderByDesc('id');
    }
}
