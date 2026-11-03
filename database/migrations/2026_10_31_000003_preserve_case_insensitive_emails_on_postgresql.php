<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('create extension if not exists citext with schema public');

        foreach (['users', 'newsletter_subscriptions', 'password_reset_tokens'] as $table) {
            DB::statement("alter table {$table} alter column email type public.citext");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach (['users', 'newsletter_subscriptions', 'password_reset_tokens'] as $table) {
            DB::statement("alter table {$table} alter column email type varchar(255)");
        }
    }
};
