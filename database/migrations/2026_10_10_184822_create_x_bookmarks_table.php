<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('x_bookmarks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('x_post_id');
            $table->string('author_name');
            $table->string('author_username');
            $table->string('author_avatar_url', 2048)->nullable();
            $table->text('text');
            $table->timestamp('posted_at');
            $table->timestamp('first_seen_at');
            $table->json('media');
            $table->json('quoted_post')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'x_post_id']);
            $table->index(['user_id', 'first_seen_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('x_bookmarks');
    }
};
