<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('alter table articles drop constraint if exists articles_status_check');
        }

        Schema::table('articles', function (Blueprint $table): void {
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("alter table articles add constraint articles_status_check check (status in ('draft', 'published', 'archived'))");

            return;
        }

        Schema::table('articles', function (Blueprint $table): void {
            $table->enum('status', [
                'draft',
                'published',
                'archived',
            ])->default('draft')->change();
        });
    }
};
