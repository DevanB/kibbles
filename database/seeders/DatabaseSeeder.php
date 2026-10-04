<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command->warn('Skipping demo seed in production.');

            return;
        }

        $this->call([
            DemoSeeder::class,
        ]);
    }
}
