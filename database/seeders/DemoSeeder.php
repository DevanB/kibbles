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
        'Hades' => GameStatus::InProgress,
        'Stardew Valley' => GameStatus::Backlog,
        'Celeste' => GameStatus::Abandoned,
        'Hollow Knight' => GameStatus::Finished,
        'Baldur\'s Gate 3' => GameStatus::Backlog,
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

        $hades = $user->games()->where('title', 'Hades')->first();

        if ($hades instanceof Game && $hades->journalEntries()->doesntExist()) {
            $hades->journalEntries()->create([
                'body' => 'Cleared Tartarus on the first heat and died to the bone hydra anyway.',
            ]);
            $hades->journalEntries()->create([
                'body' => 'Duo boon with Aphrodite finally clicked; made it to Elysium.',
            ]);
        }
    }
}
