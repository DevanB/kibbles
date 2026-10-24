<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Tag;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final readonly class UniqueOwnedTagName implements ValidationRule
{
    public function __construct(private User $owner, private ?Tag $ignore = null)
    {
        //
    }

    public static function message(): string
    {
        return 'You already have a tag with this name.';
    }

    public static function conflict(): ValidationException
    {
        return ValidationException::withMessages([
            'name' => [self::message()],
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

        $unique = Rule::unique(Tag::class, 'name_normalized')
            ->where('user_id', $this->owner->id);

        if ($this->ignore instanceof Tag) {
            $unique->ignore($this->ignore);
        }

        $validator = validator(
            [$attribute => Str::lower($value)],
            [$attribute => [$unique]],
            [$attribute.'.unique' => self::message()],
        );

        if ($validator->fails()) {
            $fail(self::message());
        }
    }
}
