<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->after('id');
            $table->string('first_name', 100)->nullable()->after('name');
            $table->string('last_name', 100)->nullable()->after('first_name');
            $table->string('preferred_name', 100)->nullable()->after('last_name');
            $table->text('bio')->nullable()->after('preferred_name');
            $table->string('avatar_path', 500)->nullable()->after('bio');
            $table->string('role', 20)->default('user')->after('avatar_path');
            $table->boolean('is_deleted')->default(false)->after('role');
            $table->boolean('is_suspended')->default(false)->after('is_deleted');
            $table->timestamp('deleted_at')->nullable()->after('is_suspended');
            $table->boolean('show_real_name')->default(true)->after('deleted_at');
            $table->boolean('show_email')->default(false)->after('show_real_name');
            $table->boolean('show_in_directory')->default(true)->after('show_email');
        });

        // Generate unique usernames for any existing users
        DB::table('users')->orderBy('id')->each(function ($user) {
            $username = strtolower(explode('@', $user->email)[0]);
            $username = preg_replace('/[^a-z0-9_-]/', '', $username);

            // Ensure it starts with a letter and meets minimum length
            if (! preg_match('/^[a-z]/', $username)) {
                $username = 'user' . $username;
            }

            if (strlen($username) < 5) {
                $username = $username . str_repeat('x', 5 - strlen($username));
            }

            $username = substr($username, 0, 16);

            // Ensure uniqueness
            $base = $username;
            $suffix = 1;
            while (DB::table('users')->where('username', $username)->where('id', '!=', $user->id)->exists()) {
                $username = substr($base, 0, 12) . $suffix;
                $suffix++;
            }

            DB::table('users')->where('id', $user->id)->update(['username' => $username]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn([
                'username',
                'first_name',
                'last_name',
                'preferred_name',
                'bio',
                'avatar_path',
                'role',
                'is_deleted',
                'is_suspended',
                'deleted_at',
                'show_real_name',
                'show_email',
                'show_in_directory',
            ]);
        });
    }
};
