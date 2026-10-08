<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PlaySessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Date;
use Illuminate\Validation\ValidationException;

/**
 * @property-read string $id
 * @property-read string $user_id
 * @property-read string $game_id
 * @property-read CarbonInterface $started_at
 * @property-read CarbonInterface|null $ended_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 * @property-read User $user
 * @property-read Game $game
 * @property-read JournalEntry|null $journalEntry
 */
#[Fillable([
    'user_id',
    'game_id',
    'started_at',
    'ended_at',
])]
final class PlaySession extends Model
{
    /** @use HasFactory<PlaySessionFactory> */
    use HasFactory;

    use HasUuids;

    public static function fromBrowserLocal(string $local, string $timezone): CarbonInterface
    {
        return Date::parse($local, $timezone)->utc();
    }

    public static function openConflictMessage(string $title): string
    {
        return "Stop your session on {$title} first.";
    }

    public static function alreadyStoppedMessage(): string
    {
        return 'This session is already stopped.';
    }

    public static function openConflict(string $title): ValidationException
    {
        return ValidationException::withMessages([
            'play_session' => [self::openConflictMessage($title)],
        ]);
    }

    public static function alreadyStopped(): ValidationException
    {
        return ValidationException::withMessages([
            'play_session' => [self::alreadyStoppedMessage()],
        ]);
    }

    public static function openFor(User $user): ?self
    {
        return self::query()->whereBelongsTo($user)->open()->with('game')->first();
    }

    public static function totalPlayedMinutesFor(Game $game): ?int
    {
        $sessions = $game->playSessions()->closed()->get();

        if ($sessions->isEmpty()) {
            return null;
        }

        $seconds = $sessions->sum(
            fn (self $session): int => (int) $session->started_at->diffInSeconds($session->ended_at),
        );

        return (int) floor($seconds / 60);
    }

    public static function formatMinutes(int $minutes): string
    {
        $hours = intdiv($minutes, 60);
        $remaining = $minutes % 60;

        if ($hours > 0 && $remaining > 0) {
            return $hours.'h '.$remaining.'m';
        }

        if ($hours > 0) {
            return $hours.'h';
        }

        return $remaining.'m';
    }

    public static function totalPlayedLabel(?int $minutes): string
    {
        return $minutes === null ? 'No time logged' : self::formatMinutes($minutes);
    }

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'game_id' => 'string',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
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
     * @return BelongsTo<Game, $this>
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * @return HasOne<JournalEntry, $this>
     */
    public function journalEntry(): HasOne
    {
        return $this->hasOne(JournalEntry::class);
    }

    public function durationMinutes(): ?int
    {
        if ($this->ended_at === null) {
            return null;
        }

        return (int) floor($this->started_at->diffInSeconds($this->ended_at) / 60);
    }

    /**
     * @return array{id: string, startedAt: string, endedAt: string|null, durationMinutes: int|null, journalEntryId: string|null}
     */
    public function toWire(): array
    {
        return [
            'id' => $this->id,
            'startedAt' => $this->started_at->toIso8601String(),
            'endedAt' => $this->ended_at?->toIso8601String(),
            'durationMinutes' => $this->durationMinutes(),
            'journalEntryId' => $this->journalEntry?->id,
        ];
    }

    /**
     * @param  Builder<PlaySession>  $query
     */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('ended_at');
    }

    /**
     * @param  Builder<PlaySession>  $query
     */
    #[Scope]
    protected function closed(Builder $query): void
    {
        $query->whereNotNull('ended_at');
    }

    /**
     * @param  Builder<PlaySession>  $query
     */
    #[Scope]
    protected function newestFirst(Builder $query): void
    {
        $query->latest('started_at')->orderByDesc('id');
    }
}
