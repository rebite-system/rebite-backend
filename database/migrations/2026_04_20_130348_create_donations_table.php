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
       Schema::create('donations', function (Blueprint $table) {
    $table->id();

    $table->foreignId('donor_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('charity_id')->nullable()->constrained('users')->nullOnDelete();
    $table->decimal('amount', 10, 2);

    $table->decimal('platform_fee', 10, 2);
    $table->decimal('charity_amount', 10, 2);

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
