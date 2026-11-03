<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The reader's preferred academic level. Collected at registration
            // and used to bias homepage article recommendations. Nullable so
            // existing users (and guests) keep a neutral, recency-based feed.
            $table->string('academic_level')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('academic_level');
        });
    }
};
