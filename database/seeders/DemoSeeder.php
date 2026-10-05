<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoSeeder extends Seeder
{
    private const string EMAIL = 'devan@localhost.test';

    /**
     * @var array<string, GameStatus>
     */
    private const array TITLES = [
        'Catan' => GameStatus::InProgress,
        'Ticket to Ride' => GameStatus::Backlog,
        'Azul' => GameStatus::Abandoned,
        'Wingspan' => GameStatus::Finished,
        'Pandemic' => GameStatus::Backlog,
    ];

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first()
            ?? User::factory()->withoutTwoFactor()->create([
                'name' => 'Devan',
                'email' => self::EMAIL,
            ]);

        foreach (self::TITLES as $title => $status) {
            $game = $user->games()->where('title', $title)->first();

            if ($game instanceof Game) {
                $game->update(['status' => $status]);

                continue;
            }

            Game::factory()->for($user)->create([
                'title' => $title,
                'status' => $status,
            ]);
        }

        $catan = $user->games()->where('title', 'Catan')->first();

        if ($catan instanceof Game && $catan->journalEntries()->doesntExist()) {
            $catan->journalEntries()->create([
                'body' => 'Opened with a wood and brick settlement and raced for longest road.',
            ]);
            $catan->journalEntries()->create([
                'body' => 'Cities went down early; the robber wrecked the wheat engine.',
            ]);
        }
    }
}
