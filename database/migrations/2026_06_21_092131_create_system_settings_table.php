<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Single-row table of platform settings. Each column is a concrete, used
     * setting — no fake/autoPublish-style toggles. The application reads via
     * App\Models\SystemSetting::current() which caches the single row.
     */
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            // When true, the MaintenanceMode middleware serves a 503 to public
            // traffic (admins always pass through).
            $table->boolean('maintenance_mode')->default(false);
            // Surface the pending-review count badge on the admin dashboard.
            $table->boolean('review_notifications')->default(true);
            // How long the glossary cache lives, in minutes.
            $table->unsignedInteger('glossary_cache_minutes')->default(60);
            $table->timestamps();
        });

        // Seed the single default row so reads never have to handle a missing row.
        DB::table('system_settings')->insert([
            'maintenance_mode' => false,
            'review_notifications' => true,
            'glossary_cache_minutes' => 60,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
