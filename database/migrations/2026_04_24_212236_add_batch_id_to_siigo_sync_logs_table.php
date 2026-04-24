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
        Schema::table('siigo_sync_logs', function (Blueprint $table) {
            $table->uuid('batch_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('siigo_sync_logs', function (Blueprint $table) {
            $table->dropColumn('batch_id');
        });
    }
};
