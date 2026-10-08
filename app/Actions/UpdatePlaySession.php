<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PlaySession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePlaySession
{
    public function handle(PlaySession $session, CarbonInterface $startedAt, CarbonInterface $endedAt): PlaySession
    {
        return DB::transaction(function () use ($session, $startedAt, $endedAt): PlaySession {
            $session->update([
                'started_at' => $startedAt,
                'ended_at' => $endedAt,
            ]);

            return $session;
        });
    }
}
