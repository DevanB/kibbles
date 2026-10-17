<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Game;
use App\Models\JournalEntry;
use App\Models\PlaySession;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final readonly class LinkablePlaySession implements ValidationRule
{
    public function __construct(private Game $game, private ?JournalEntry $current = null) {}

    public static function wrongGameMessage(): string
    {
        return 'The play session must belong to this game.';
    }

    public static function alreadyLinkedMessage(): string
    {
        return 'The play session is already linked to a journal entry.';
    }

    /**
     * @return list<string|self>
     */
    public static function rules(Game $game, ?JournalEntry $current = null): array
    {
        return ['nullable', 'uuid', 'bail', new self($game, $current)];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'play_session_id.uuid' => 'The play session is invalid.',
        ];
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $session = PlaySession::query()->whereKey($value)->first();

        if (! $session instanceof PlaySession || $session->game_id !== $this->game->id || $session->user_id !== $this->game->user_id) {
            $fail(self::wrongGameMessage());

            return;
        }

        $linked = $session->journalEntry;

        if ($linked instanceof JournalEntry && ! $linked->is($this->current)) {
            $fail(self::alreadyLinkedMessage());
        }
    }
}
