<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('claims') && !Schema::hasTable('claim')) {
            Schema::rename('claims', 'claim');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('claim')) {
            Schema::rename('claim', 'claims');
        }
    }
};