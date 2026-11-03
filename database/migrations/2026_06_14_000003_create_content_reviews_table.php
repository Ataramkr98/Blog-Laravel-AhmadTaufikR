<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_reviews', function (Blueprint $table): void {
            $table->id();
            $table->morphs('reviewable');
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->string('action');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reviews');
    }
};
