<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('title_normalized')->storedAs('LOWER(title)');
            $table->timestamps();

            $table->unique(['user_id', 'title_normalized']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
