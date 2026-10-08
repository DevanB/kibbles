<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Game;
use App\Models\PlaySession;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

final readonly class CreatePlaySession
{
    public function handle(Game $game, CarbonInterface $startedAt, CarbonInterface $endedAt): PlaySession
    {
        return DB::transaction(fn (): PlaySession => $game->playSessions()->create([
            'user_id' => $game->user_id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]));
    }
}
