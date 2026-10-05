<?php

declare(strict_types=1);

namespace App\Enums;

enum GameStatus: string
{
    case Backlog = 'backlog';
    case InProgress = 'in_progress';
    case Abandoned = 'abandoned';
    case Finished = 'finished';

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
            ],
            self::cases(),
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::InProgress => 'In Progress',
            self::Abandoned => 'Abandoned',
            self::Finished => 'Finished',
        };
    }
}
