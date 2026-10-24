<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_x_bookmark', function (Blueprint $table): void {
            $table->foreignUuid('tag_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('x_bookmark_id')->constrained()->cascadeOnDelete();

            $table->primary(['tag_id', 'x_bookmark_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_x_bookmark');
    }
};
