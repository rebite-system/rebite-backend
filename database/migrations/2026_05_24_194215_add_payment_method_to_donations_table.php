<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('charity_amount');
            $table->string('payment_reference')->nullable()->after('payment_method');
            $table->string('payment_status')->default('paid')->after('payment_reference');
        });
    }

    public function down(): void
    {
        Schema::table('donations', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference', 'payment_status']);
        });
    }
};