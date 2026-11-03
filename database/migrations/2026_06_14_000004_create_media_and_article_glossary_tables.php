<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_name');
            $table->string('disk')->default('public');
            $table->string('path')->unique();
            $table->string('mime_type');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });

        Schema::create('article_glossary', function (Blueprint $table): void {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('glossary_id')->constrained()->cascadeOnDelete();
            $table->primary(['article_id', 'glossary_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_glossary');
        Schema::dropIfExists('media_assets');
    }
};
