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
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->foreignUuid('play_session_id')->nullable()->constrained()->nullOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX journal_entries_play_session_id_unique ON journal_entries (play_session_id) WHERE play_session_id IS NOT NULL');
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('play_session_id');
        });
    }
};
