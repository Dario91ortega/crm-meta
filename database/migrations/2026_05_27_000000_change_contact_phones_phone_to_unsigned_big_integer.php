<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Strip every non-digit character from existing phones so the
        // upcoming column-type change does not fail.
        // Rows whose phone has zero digits after stripping become '' and
        // would fail the BIGINT cast; delete them.
        DB::statement("UPDATE contact_phones SET phone = REGEXP_REPLACE(phone, '[^0-9]', '')");
        DB::statement("DELETE FROM contact_phones WHERE phone = '' OR phone IS NULL");

        Schema::table('contact_phones', function (Blueprint $table) {
            $table->unsignedBigInteger('phone')->change();
        });
    }

    public function down(): void
    {
        Schema::table('contact_phones', function (Blueprint $table) {
            $table->string('phone')->change();
        });
    }
};
