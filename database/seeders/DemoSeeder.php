<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Seeder;

final class DemoSeeder extends Seeder
{
    private const string EMAIL = 'devan@localhost.test';

    /**
     * @var list<string>
     */
    private const array TITLES = [
        'Catan',
        'Ticket to Ride',
        'Azul',
        'Wingspan',
        'Pandemic',
    ];

    public function run(): void
    {
        $user = User::query()->where('email', self::EMAIL)->first()
            ?? User::factory()->withoutTwoFactor()->create([
                'name' => 'Devan',
                'email' => self::EMAIL,
            ]);

        foreach (self::TITLES as $title) {
            if ($user->games()->where('title', $title)->exists()) {
                continue;
            }

            Game::factory()->for($user)->create([
                'title' => $title,
            ]);
        }

        $catan = $user->games()->where('title', 'Catan')->first();

        if ($catan instanceof Game && $catan->journalEntries()->doesntExist()) {
            $catan->journalEntries()->create([
                'body' => 'Opened with a wood and brick settlement and raced for longest road.',
                'next' => 'Contest the 8-wheat hex before the robber parks there.',
            ]);
            $catan->journalEntries()->create([
                'body' => 'Cities went down early; the robber wrecked the wheat engine.',
                'next' => null,
            ]);
        }
    }
}
