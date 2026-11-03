<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('final_exam_attempts', function (Blueprint $table): void {
            $table->uuid('submission_token')->nullable();
            $table->json('question_snapshot')->nullable();
            $table->unique(['course_id', 'user_id', 'submission_token'], 'exam_submission_unique');
        });
    }

    public function down(): void
    {
        Schema::table('final_exam_attempts', function (Blueprint $table): void {
            $table->dropUnique('exam_submission_unique');
            $table->dropColumn(['submission_token', 'question_snapshot']);
        });
    }
};
