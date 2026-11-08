<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Models\XBookmark;

final readonly class XBookmarkReviewQueue
{
    private function __construct(
        public ?XBookmark $next,
        public int $due,
        public int $leftToday,
    ) {}

    public static function caughtUp(): self
    {
        return new self(null, 0, 0);
    }

    public static function doneForToday(int $due): self
    {
        return new self(null, $due, 0);
    }

    public static function reviewing(XBookmark $next, int $due, int $leftToday): self
    {
        return new self($next, $due, $leftToday);
    }

    /**
     * @return array{status: 'reviewing', bookmark: array<string, mixed>, due: int, leftToday: int}|array{status: 'done-for-today', due: int}|array{status: 'caught-up'}
     */
    public function toWire(): array
    {
        if ($this->next instanceof XBookmark) {
            return [
                'status' => 'reviewing',
                'bookmark' => $this->next->toWire(),
                'due' => $this->due,
                'leftToday' => $this->leftToday,
            ];
        }

        if ($this->due > 0) {
            return [
                'status' => 'done-for-today',
                'due' => $this->due,
            ];
        }

        return [
            'status' => 'caught-up',
        ];
    }
}
