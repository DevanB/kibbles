<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Production (.env.production.example) and tests use SQLite. A table-level
        // CHECK is accepted only at CREATE TABLE time, so the table is created
        // with explicit SQL instead of a generated is_open column (MySQL).
        DB::statement('
            CREATE TABLE play_sessions (
                id varchar not null,
                user_id varchar not null,
                game_id varchar not null,
                started_at datetime not null,
                ended_at datetime,
                created_at datetime,
                updated_at datetime,
                primary key (id),
                foreign key (user_id) references users (id) on delete cascade,
                foreign key (game_id) references games (id) on delete cascade,
                check (ended_at is null or ended_at >= started_at)
            )
        ');

        Schema::table('play_sessions', function (Blueprint $table): void {
            $table->index(['game_id', 'started_at', 'id']);
        });

        DB::statement('CREATE UNIQUE INDEX play_sessions_user_open_unique ON play_sessions (user_id) WHERE ended_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('play_sessions');
    }
};
