<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Avatar columns hold provider-supplied URLs (e.g. Google avatars often
     * exceed 1000 chars). VARCHAR(255) is too tight; switch to TEXT for
     * both users.avatar and contacts.avatar.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->text('avatar')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->string('avatar')->nullable()->change();
        });
    }
};
