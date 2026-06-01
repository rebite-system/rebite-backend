<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('card_last4')->nullable();

            $table->string('vodafone_number')->nullable();

            $table->string('instapay_address')->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'card_last4',
                'vodafone_number',
                'instapay_address'
            ]);

        });
    }
};