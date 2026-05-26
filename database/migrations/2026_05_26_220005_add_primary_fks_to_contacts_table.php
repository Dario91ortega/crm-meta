<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('contact_phone_id')
                ->nullable()
                ->after('avatar')
                ->constrained('contact_phones')
                ->nullOnDelete();
            $table->foreignId('contact_email_id')
                ->nullable()
                ->after('contact_phone_id')
                ->constrained('contact_emails')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('contact_phone_id');
            $table->dropConstrainedForeignId('contact_email_id');
        });
    }
};
