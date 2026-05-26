<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('eventable');
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['contact_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_events');
    }
};
