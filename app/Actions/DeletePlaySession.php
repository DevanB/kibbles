<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\PlaySession;
use Illuminate\Support\Facades\DB;

final readonly class DeletePlaySession
{
    public function handle(PlaySession $session): void
    {
        DB::transaction(function () use ($session): void {
            $session->delete();
        });
    }
}
