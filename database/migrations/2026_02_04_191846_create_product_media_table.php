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
        Schema::create('product_media', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('media_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->boolean('is_primary')->default(false);

            $table->unsignedInteger('order')->default(0);

            $table->timestamps();

            // Evita duplicar la misma imagen en el mismo producto
            $table->unique(['product_id', 'media_id']);
            $table->index(['product_id', 'is_primary']);
            $table->index(['product_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
