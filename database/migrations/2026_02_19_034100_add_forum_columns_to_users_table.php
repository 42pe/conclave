<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 40)->unique()->after('name');
            $table->string('first_name', 100)->nullable()->after('username');
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
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
