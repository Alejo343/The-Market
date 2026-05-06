<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_identification_type')->nullable()->after('customer_city');
            $table->string('customer_identification')->nullable()->after('customer_identification_type');
            $table->string('customer_business_name')->nullable()->after('customer_identification');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['customer_identification_type', 'customer_identification', 'customer_business_name']);
        });
    }
};
