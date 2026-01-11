<?php

// ============================================
// database/migrations/2024_12_28_000013_create_reviews_table.php
// Reviews are PER BRANCH (each branch has own reviews)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->integer('rating')->unsigned(); // 1-5
            $table->text('comment')->nullable();
            $table->json('photos')->nullable();
            
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->timestamp('published_at')->nullable();
            
            // Business owner reply
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['business_branch_id', 'user_id']);
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};