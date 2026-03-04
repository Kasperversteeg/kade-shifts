<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        // Migrate existing users to Spatie roles
        User::all()->each(function (User $user) {
            if ($user->role === 'admin') {
                $user->assignRole('admin');
            } else {
                $user->assignRole('user');
            }
        });

        // Drop the role column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->after('email_verified_at');
        });

        User::all()->each(function (User $user) {
            $user->update(['role' => $user->hasRole('admin') ? 'admin' : 'user']);
        });
    }
};
