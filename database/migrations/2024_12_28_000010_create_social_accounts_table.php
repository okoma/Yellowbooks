<?php

// ============================================
// database/migrations/2024_12_28_000010_create_social_accounts_table.php
// Business social media (per business, not per branch)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            $table->enum('platform', ['facebook', 'instagram', 'twitter', 'linkedin', 'youtube', 'tiktok']);
            $table->string('url');
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            $table->unique(['business_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};