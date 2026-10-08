<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GameStatus;
use Carbon\CarbonInterface;
use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $user_id
 * @property-read string $title
 * @property-read GameStatus $status
 * @property-read int|null $rawg_id
 * @property-read string|null $image_url
 * @property-read string|null $description
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Collection<int, JournalEntry> $journalEntries
 * @property-read Collection<int, PlaySession> $playSessions
 */
#[Fillable([
    'title',
    'status',
    'rawg_id',
    'image_url',
    'description',
])]
#[Hidden([
    'title_normalized',
])]
final class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'title' => 'string',
            'status' => GameStatus::class,
            'rawg_id' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<JournalEntry, $this>
     */
    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    /**
     * @return HasMany<PlaySession, $this>
     */
    public function playSessions(): HasMany
    {
        return $this->hasMany(PlaySession::class);
    }

    /**
     * @return array{id: string, title: string, status: string, statusLabel: string, rawgId: int|null, imageUrl: string|null, description: string|null}
     */
    public function toWire(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'rawgId' => $this->rawg_id,
            'imageUrl' => $this->image_url,
            'description' => $this->description,
        ];
    }
}
