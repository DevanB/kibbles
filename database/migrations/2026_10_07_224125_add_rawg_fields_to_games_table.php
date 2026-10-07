<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->unsignedInteger('rawg_id')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->text('description')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table): void {
            $table->dropColumn(['rawg_id', 'image_url', 'description']);
        });
    }
};
