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
     * @var array<string, array{status: GameStatus, rawg_id: int|null, image_url: string|null}>
     */
    private const array TITLES = [
        'Hades' => [
            'status' => GameStatus::InProgress,
            'rawg_id' => 274755,
            'image_url' => 'https://media.rawg.io/media/games/1f4/1f47a270b8f241e4676b14d39ec620f7.jpg',
        ],
        'Stardew Valley' => [
            'status' => GameStatus::Backlog,
            'rawg_id' => 654,
            'image_url' => 'https://media.rawg.io/media/games/713/713269608dc8f2f40f5a670a14b2de94.jpg',
        ],
        'Celeste' => [
            'status' => GameStatus::Abandoned,
            'rawg_id' => null,
            'image_url' => null,
        ],
        'Hollow Knight' => [
            'status' => GameStatus::Finished,
            'rawg_id' => 9767,
            'image_url' => 'https://media.rawg.io/media/games/4cf/4cfc6b7f1850590a4634b08bfab308ab.jpg',
        ],
        "Baldur's Gate 3" => [
            'status' => GameStatus::Backlog,
            'rawg_id' => 324997,
            'image_url' => 'https://media.rawg.io/media/games/699/69907ecf13f172e9e144069769c3be73.jpg',
        ],
    ];

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first()
            ?? User::factory()->withoutTwoFactor()->create([
                'name' => 'Devan',
                'email' => self::EMAIL,
            ]);

        foreach (self::TITLES as $title => $catalog) {
            $game = $user->games()->where('title', $title)->first();

            if ($game instanceof Game) {
                $game->update([
                    'status' => $catalog['status'],
                    'rawg_id' => $catalog['rawg_id'],
                    'image_url' => $catalog['image_url'],
                ]);

                continue;
            }

            Game::factory()->for($user)->create([
                'title' => $title,
                'status' => $catalog['status'],
                'rawg_id' => $catalog['rawg_id'],
                'image_url' => $catalog['image_url'],
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
