<?php

declare(strict_types=1);

use App\ValueObjects\TagUsage;

it('formats unused, singular, plural, and mixed usage', function (int $xPosts, int $bookmarks, string $line): void {
    expect(new TagUsage($xPosts, $bookmarks)->line())->toBe($line);
})->with([
    'unused' => [0, 0, 'Not used'],
    'one X post' => [1, 0, '1 X post'],
    'two X posts' => [2, 0, '2 X posts'],
    'one bookmark' => [0, 1, '1 bookmark'],
    'two bookmarks' => [0, 2, '2 bookmarks'],
    'both' => [12, 3, '12 X posts · 3 bookmarks'],
]);

it('writes the delete warning from the usage line', function (): void {
    expect(new TagUsage(0, 0)->warning())
        ->toBe('This tag is not used on any posts or bookmarks. The bookmarks stay. This cannot be undone.')
        ->and(new TagUsage(12, 3)->warning())
        ->toBe('This removes the tag from 12 X posts · 3 bookmarks. The bookmarks stay. This cannot be undone.');
});
