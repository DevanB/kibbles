<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\GameStatus;
use App\Models\Game;
use App\Models\User;
use App\Models\XBookmark;
use App\Models\XConnection;
use Database\Factories\XBookmarkFactory;
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

        $this->seedBookmark($user, '1001', [
            'author_name' => 'Taylor Otwell',
            'author_username' => 'taylorotwell',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/463230?s=96&v=4',
            'text' => 'Laravel 13 is out.',
            'first_seen_at' => now()->subMinutes(5),
        ], fn ($bookmark) => $bookmark->hotlinkedPhoto(
            'https://placehold.co/800x531/1d4ed8/ffffff/jpeg',
            800,
            531,
        ));
        $this->seedBookmark($user, '1002', [
            'author_name' => 'Nuno Maduro',
            'author_username' => 'enunomaduro',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/5457236?s=96&v=4',
            'text' => 'Pest 5 browser testing is the good stuff.',
            'first_seen_at' => now()->subHour(),
        ], fn ($bookmark) => $bookmark->quotedWithMedia()->hotlinkedPhoto(
            'https://placehold.co/640x360/166534/ffffff/jpeg',
            640,
            360,
        ));
        $this->seedBookmark($user, '1003', [
            'author_name' => 'X Engineering',
            'author_username' => 'xeng',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/9919?s=96&v=4',
            'text' => 'A short clip from the lab.',
            'first_seen_at' => now()->subHours(3),
        ], fn ($bookmark) => $bookmark->hotlinkedVideo());
        $this->seedBookmark($user, '1004', [
            'author_name' => 'Devan',
            'author_username' => 'devan',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/354652?s=96&v=4',
            'first_seen_at' => now()->subDay(),
        ], fn ($bookmark) => $bookmark->longText()->withoutMedia());
        $this->seedBookmark($user, '1005', [
            'author_name' => 'Jess Archer',
            'author_username' => 'jessarchercodes',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/1794495?s=96&v=4',
            'text' => 'Portrait tiles make the masonry columns actually stagger.',
            'first_seen_at' => now()->subHours(6),
        ], fn ($bookmark) => $bookmark->hotlinkedPhoto(
            'https://placehold.co/480x720/0f172a/ffffff/jpeg',
            480,
            720,
        ));
        $this->seedBookmark($user, '1006', [
            'author_name' => 'Canary',
            'author_username' => 'canary',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/18133?s=96&v=4',
            'text' => 'Wide shot.',
            'first_seen_at' => now()->subHours(8),
        ], fn ($bookmark) => $bookmark->hotlinkedPhoto(
            'https://placehold.co/960x360/155e75/ffffff/jpeg',
            960,
            360,
        ));
        $this->seedBookmark($user, '1007', [
            'author_name' => 'Ada',
            'author_username' => 'ada',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/1024025?s=96&v=4',
            'text' => "Two lines.\nThen a bit more copy so this card sits taller than a one-liner.",
            'first_seen_at' => now()->subHours(12),
        ], fn ($bookmark) => $bookmark->withoutMedia());
        $this->seedBookmark($user, '1008', [
            'author_name' => 'Linus',
            'author_username' => 'torvalds',
            'author_avatar_url' => 'https://avatars.githubusercontent.com/u/1024025?s=96&v=4',
            'text' => 'Another photo bookmark for the third column.',
            'first_seen_at' => now()->subHours(18),
        ], fn ($bookmark) => $bookmark->hotlinkedPhoto(
            'https://placehold.co/640x480/7c3aed/ffffff/jpeg',
            640,
            480,
        ));
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  callable(XBookmarkFactory): XBookmarkFactory  $configure
     */
    private function seedBookmark(User $user, string $xPostId, array $attributes, callable $configure): void
    {
        $configure(XBookmark::factory()->recycle($user))->create([
            ...$attributes,
            'x_post_id' => $xPostId,
        ]);
    }
}
