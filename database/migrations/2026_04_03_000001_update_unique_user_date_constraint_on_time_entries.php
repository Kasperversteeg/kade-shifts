<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Replace the unique constraint on (user_id, date) with one on
     * (user_id, date, deleted_at) so that soft-deleted rows no longer
     * block new entries for the same user and date.
     *
     * In MySQL, NULL values are treated as distinct in unique indexes,
     * so only one non-deleted row per user+date is allowed while
     * soft-deleted rows will not cause conflicts.
     */
    public function up(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date']);
            $table->unique(['user_id', 'date', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('time_entries', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'date', 'deleted_at']);
            $table->unique(['user_id', 'date']);
        });
    }
};
