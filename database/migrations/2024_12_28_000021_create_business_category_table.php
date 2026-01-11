<?php

// ============================================
// database/migrations/2024_12_28_000021_create_business_category_table.php
// PIVOT TABLE
// Business to Categories (business level, not branch)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_category', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->primary(['business_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_category');
    }
};