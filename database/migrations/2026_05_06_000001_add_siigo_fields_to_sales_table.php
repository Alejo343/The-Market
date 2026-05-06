<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('siigo_invoice_id')->nullable()->after('total');
            $table->string('customer_identification')->nullable()->after('siigo_invoice_id');
            $table->string('customer_name')->nullable()->after('customer_identification');
            $table->string('customer_email')->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['siigo_invoice_id', 'customer_identification', 'customer_name', 'customer_email']);
        });
    }
};
