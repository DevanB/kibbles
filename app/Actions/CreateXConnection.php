<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\XConnection;
use Carbon\CarbonInterface;

final readonly class CreateXConnection
{
    public function handle(
        User $user,
        string $xUserId,
        string $username,
        string $accessToken,
        string $refreshToken,
        CarbonInterface $expiresAt,
    ): XConnection {
        return $user->xConnection()->updateOrCreate([], [
            'x_user_id' => $xUserId,
            'username' => $username,
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_at' => $expiresAt,
        ]);
    }
}
