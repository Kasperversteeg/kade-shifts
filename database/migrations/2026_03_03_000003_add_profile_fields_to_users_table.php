<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('hourly_rate', 5, 2)->nullable()->after('google_id');
            $table->string('contract_type')->nullable()->after('hourly_rate');
            $table->date('contract_start_date')->nullable()->after('contract_type');
            $table->date('contract_end_date')->nullable()->after('contract_start_date');
            $table->date('birth_date')->nullable()->after('contract_end_date');
            $table->date('start_date')->nullable()->after('birth_date');
            $table->text('bsn')->nullable()->after('start_date');
            $table->string('phone')->nullable()->after('bsn');
            $table->string('address')->nullable()->after('phone');
            $table->string('city')->nullable()->after('address');
            $table->string('postal_code')->nullable()->after('city');
            $table->timestamp('contract_expiry_notified_at')->nullable()->after('postal_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate',
                'contract_type',
                'contract_start_date',
                'contract_end_date',
                'birth_date',
                'start_date',
                'bsn',
                'phone',
                'address',
                'city',
                'postal_code',
                'contract_expiry_notified_at',
            ]);
        });
    }
};
