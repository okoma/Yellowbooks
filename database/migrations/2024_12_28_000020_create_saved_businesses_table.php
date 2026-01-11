<?php

// ============================================
// database/migrations/2024_12_28_000020_create_saved_businesses_table.php
// User bookmarks (can save specific branches)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_businesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['user_id', 'business_branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_businesses');
    }
};