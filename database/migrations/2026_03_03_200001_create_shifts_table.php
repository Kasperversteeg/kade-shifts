<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('published')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['date', 'user_id']);
            $table->index(['date', 'published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
