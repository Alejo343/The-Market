<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('transaction_id')->nullable();
            $table->enum('status', ['PENDING', 'APPROVED', 'DECLINED', 'ERROR'])->default('PENDING');
            $table->enum('payment_method', ['NEQUI', 'CARD', 'PSE'])->nullable();
            $table->string('customer_email');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_address');
            $table->string('customer_city');
            $table->json('items_data');
            $table->integer('total_amount_cents');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index('reference');
            $table->index('transaction_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
