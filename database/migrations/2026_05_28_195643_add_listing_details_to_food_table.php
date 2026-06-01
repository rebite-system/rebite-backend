<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food', function (Blueprint $table) {
            $table->string('category')->nullable();
            $table->text('notes')->nullable();
            $table->time('pickup_from')->nullable();
            $table->time('pickup_until')->nullable();
            $table->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::table('food', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'notes',
                'pickup_from',
                'pickup_until',
                'status',
            ]);
        });
    }
};