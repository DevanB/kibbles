<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PlaySession;
use Illuminate\Support\Facades\DB;

final readonly class FinishPlaySession
{
    public function handle(PlaySession $session, ?string $body = null): PlaySession
    {
        return DB::transaction(function () use ($session, $body): PlaySession {
            if ($session->ended_at !== null) {
                throw PlaySession::alreadyStopped();
            }

            $session->update([
                'ended_at' => now(),
            ]);

            if (is_string($body) && $body !== '') {
                $session->game->journalEntries()->create([
                    'body' => $body,
                    'play_session_id' => $session->id,
                ]);
            }

            return $session->refresh();
        });
    }
}
