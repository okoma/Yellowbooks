<?php

// ============================================
// database/migrations/2024_12_28_000004_create_business_branches_table.php
// Each branch is an INDEXED, SEO-friendly page
// chicken-republic-lagos, chicken-republic-ikeja, etc.
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            
            // Branch-Specific Info
            $table->string('branch_title')->nullable(); // "Ikeja Branch" or custom title
            $table->string('slug')->unique(); // "chicken-republic-ikeja"
            $table->text('branch_description')->nullable(); // Optional unique description
            $table->boolean('is_main_branch')->default(false);
            
            // Location (REQUIRED for each branch)
            $table->string('address'); // Full address
            $table->string('city');
            $table->string('area')->nullable(); // Ikeja, VI, Lekki
            $table->string('state');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            
            // Branch Contact (can override parent)
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();
            
            // Business Hours (per branch)
            $table->json('business_hours')->nullable();
            // Example: {
            //   "monday": {"open": "09:00", "close": "17:00", "closed": false},
            //   "tuesday": {"open": "09:00", "close": "17:00", "closed": false},
            //   ...
            // }
            
            // Branch-specific media
            $table->json('gallery')->nullable(); // Branch-specific photos
            
            // Branch Stats
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('reviews_count')->default(0);
            $table->integer('views_count')->default(0);
            $table->integer('leads_count')->default(0);
            $table->integer('saves_count')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // Display order
            
            $table->timestamps();
            
            $table->index('business_id');
            $table->index('slug');
            $table->index(['city', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_branches');
    }
};