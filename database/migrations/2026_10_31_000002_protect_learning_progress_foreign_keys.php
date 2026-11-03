<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_lessons', function (Blueprint $table): void {
            $table->dropForeign(['article_id']);
            $table->foreign('article_id')->references('id')->on('articles')->restrictOnDelete();
        });

        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->dropForeign(['course_lesson_id']);
            $table->foreign('course_lesson_id')->references('id')->on('course_lessons')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('lesson_progress', function (Blueprint $table): void {
            $table->dropForeign(['course_lesson_id']);
            $table->foreign('course_lesson_id')->references('id')->on('course_lessons')->cascadeOnDelete();
        });

        Schema::table('course_lessons', function (Blueprint $table): void {
            $table->dropForeign(['article_id']);
            $table->foreign('article_id')->references('id')->on('articles')->cascadeOnDelete();
        });
    }
};
