<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Per-visitor, per-day view tracking. The visitor_hash is a non-reversible
     * hash of IP + User-Agent (salted) so no PII is stored, while still
     * allowing one-hit-per-article-per-visitor-per-day deduplication.
     * The existing articles.view_count column remains the headline number;
     * this table powers the periodic analytics (1/7/30-day trends).
     */
    public function up(): void
    {
        Schema::create('article_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            // sha256(ip + ua + salt) — 64 hex chars.
            $table->string('visitor_hash', 64);
            $table->date('viewed_on');
            $table->timestamps();

            // One row per article/visitor/day.
            $table->unique(['article_id', 'visitor_hash', 'viewed_on']);
            $table->index(['article_id', 'viewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_views');
    }
};
