<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Game;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class UniqueOwnedGameTitle implements ValidationRule
{
    public function __construct(private User $owner, private ?Game $ignore = null)
    {
        //
    }

    public static function message(): string
    {
        return 'You already have a game with this title.';
    }

    public static function conflict(): ValidationException
    {
        return ValidationException::withMessages([
            'title' => self::message(),
        ]);
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $normalizedTitle = Str::lower($value);

        $unique = Rule::unique(Game::class, 'title_normalized')
            ->where('user_id', $this->owner->id)
            ->where(fn ($query) => $query->whereRaw('LOWER(title) = ?', [$normalizedTitle]));

        if ($this->ignore instanceof Game) {
            $unique->ignore($this->ignore);
        }

        $validator = validator(
            [$attribute => $normalizedTitle],
            [$attribute => [$unique]],
            [$attribute.'.unique' => self::message()],
        );

        if ($validator->fails()) {
            $fail(self::message());
        }
    }
}
