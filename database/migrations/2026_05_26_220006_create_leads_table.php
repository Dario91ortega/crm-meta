<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();

            $table->string('platform');
            $table->string('platform_lead_id');
            $table->string('form_id')->nullable();
            $table->string('ad_id')->nullable();
            $table->string('campaign_id')->nullable();

            $table->json('payload');
            $table->string('status')->default('pending')->index();

            $table->timestamp('captured_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['platform', 'platform_lead_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
