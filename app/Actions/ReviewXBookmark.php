<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\XBookmark;

final readonly class ReviewXBookmark
{
    public function handle(XBookmark $bookmark): XBookmark
    {
        $bookmark->update([
            'reviewed_at' => now(),
        ]);

        return $bookmark;
    }
}
