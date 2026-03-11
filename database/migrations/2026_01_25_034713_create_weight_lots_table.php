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
        Schema::create('weight_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();

            $table->decimal('initial_weight', 8, 3); // kg
            $table->decimal('available_weight', 8, 3);

            $table->decimal('price_per_kg', 10, 2);

            $table->date('expires_at')->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('weight_lots');
    }
};
