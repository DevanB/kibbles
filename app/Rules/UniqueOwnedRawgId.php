<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Game;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class UniqueOwnedRawgId implements ValidationRule
{
    public function __construct(private User $owner, private ?Game $ignore = null)
    {
        //
    }

    public static function message(): string
    {
        return 'You already have this game.';
    }

    public static function conflict(): ValidationException
    {
        return ValidationException::withMessages([
            'rawg_id' => [self::message()],
        ]);
    }

    public static function fromConstraint(UniqueConstraintViolationException $exception): ValidationException
    {
        $driverMessage = $exception->errorInfo[2] ?? $exception->getPrevious()?->getMessage() ?? '';

        return str_contains($driverMessage, 'rawg_id')
            || str_contains($driverMessage, 'games_user_id_rawg_id_unique')
            ? self::conflict()
            : UniqueOwnedGameTitle::conflict();
    }

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            return;
        }

        $unique = Rule::unique(Game::class, 'rawg_id')
            ->where('user_id', $this->owner->id);

        if ($this->ignore instanceof Game) {
            $unique->ignore($this->ignore);
        }

        $validator = validator(
            [$attribute => $value],
            [$attribute => [$unique]],
            [$attribute.'.unique' => self::message()],
        );

        if ($validator->fails()) {
            $fail(self::message());
        }
    }
}
