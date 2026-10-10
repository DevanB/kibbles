<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
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

        if ($user->playSessions()->doesntExist()) {
            $this->seedPlaySessions($user);
        }

        $this->seedBookmarks($user);
    }

    private function seedPlaySessions(User $user): void
    {
        $hades = $user->games()->where('title', 'Hades')->first();
        $hollowKnight = $user->games()->where('title', 'Hollow Knight')->first();
        $baldursGate = $user->games()->where('title', "Baldur's Gate 3")->first();

        if ($hades instanceof Game) {
            $closed = $hades->playSessions()->create([
                'user_id' => $user->id,
                'started_at' => now()->subHours(3),
                'ended_at' => now()->subHour(),
            ]);

            $hades->playSessions()->create([
                'user_id' => $user->id,
                'started_at' => now()->subMinutes(20),
            ]);

            $newerJournal = $hades->journalEntries()->newestFirst()->first();

            if ($newerJournal !== null) {
                $newerJournal->update([
                    'play_session_id' => $closed->id,
                ]);
            }
        }

        if ($hollowKnight instanceof Game) {
            $hollowKnight->playSessions()->create([
                'user_id' => $user->id,
                'started_at' => now()->subHours(5),
                'ended_at' => now()->subHours(4)->subMinutes(10),
            ]);
        }

        if ($baldursGate instanceof Game) {
            $baldursGate->playSessions()->create([
                'user_id' => $user->id,
                'started_at' => now()->subDays(2),
                'ended_at' => now()->subDays(2)->addMinutes(40),
            ]);
        }
    }

    private function seedBookmarks(User $user): void
    {
        if ($user->xConnection === null) {
            XConnection::factory()->recycle($user)->synced()->create([
                'username' => 'devan',
            ]);
        }

        if ($user->xBookmarks()->exists()) {
            return;
        }

        XBookmark::factory()->recycle($user)->create([
            'author_name' => 'Taylor Otwell',
            'author_username' => 'taylorotwell',
            'text' => 'Laravel 13 is out.',
            'first_seen_at' => now()->subMinutes(5),
        ]);
        XBookmark::factory()->recycle($user)->quoted()->create([
            'author_name' => 'Nuno Maduro',
            'author_username' => 'enunomaduro',
            'text' => 'Pest 5 browser testing is the good stuff.',
            'first_seen_at' => now()->subHour(),
        ]);
        XBookmark::factory()->recycle($user)->video()->create([
            'author_name' => 'X Engineering',
            'author_username' => 'xeng',
            'text' => 'A short clip from the lab.',
            'first_seen_at' => now()->subHours(3),
        ]);
        XBookmark::factory()->recycle($user)->longText()->create([
            'author_name' => 'Devan',
            'author_username' => 'devan',
            'first_seen_at' => now()->subDay(),
        ]);
    }
}
