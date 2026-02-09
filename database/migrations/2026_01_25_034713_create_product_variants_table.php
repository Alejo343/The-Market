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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();

            $table->string('presentation'); // 500 g, 1 kg, 1 L
            $table->string('sku')->unique();;

            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();

            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);

            $table->foreignId('tax_id')->constrained('taxes');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
