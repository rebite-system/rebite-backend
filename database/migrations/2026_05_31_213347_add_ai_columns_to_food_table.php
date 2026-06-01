<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food', function (Blueprint $table) {
            $table->string('ai_priority_level')->nullable()->after('status');
            $table->integer('ai_priority_score')->default(0)->after('ai_priority_level');
            $table->text('ai_priority_reason')->nullable()->after('ai_priority_score');
            $table->text('ai_recommended_action')->nullable()->after('ai_priority_reason');
        });
    }

    public function down(): void
    {
        Schema::table('food', function (Blueprint $table) {
            $table->dropColumn([
                'ai_priority_level',
                'ai_priority_score',
                'ai_priority_reason',
                'ai_recommended_action',
            ]);
        });
    }
};
