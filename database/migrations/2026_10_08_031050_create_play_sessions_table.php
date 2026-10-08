<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('play_sessions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('game_id')->constrained()->cascadeOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->uuid('open_user_id')->nullable()->storedAs('CASE WHEN ended_at IS NULL THEN user_id END');
            $table->timestamps();
            $table->index(['game_id', 'started_at', 'id']);
            $table->unique('open_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('play_sessions');
    }
};
