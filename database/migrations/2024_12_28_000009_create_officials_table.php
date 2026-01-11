<?php

// ============================================
// database/migrations/2024_12_28_000009_create_officials_table.php
// Team members (per business, not per branch)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('officials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            $table->string('name');
            $table->string('position');
            $table->string('photo')->nullable();
            
            // Social accounts (JSON)
            $table->json('social_accounts')->nullable();
            // Example: {"linkedin": "url", "twitter": "url", "instagram": "url"}
            
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('officials');
    }
};