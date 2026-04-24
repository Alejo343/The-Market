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
        Schema::create('siigo_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('event_type', ['import', 'webhook', 'polling']);
            $table->string('topic')->nullable();
            $table->string('siigo_code')->nullable();
            $table->string('siigo_id')->nullable();
            $table->enum('status', ['success', 'error', 'skipped']);
            $table->text('message');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siigo_sync_logs');
    }
};
