<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final readonly class BrowserLocalDateTime
{
    public static function toUtc(string $local, string $timezone): CarbonInterface
    {
        return Carbon::parse($local, $timezone)->utc();
    }
}
