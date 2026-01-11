<?php

// ============================================
// database/migrations/2024_12_28_000011_create_products_table.php
// Products/Services/Menu items (PER BRANCH)
// Each branch can have different menu/services
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            
            // Product Info
            $table->string('header_title'); // "Main Dishes", "Drinks", "Services"
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            
            // Pricing
            $table->string('currency')->default('NGN');
            $table->decimal('price', 10, 2)->nullable();
            $table->enum('discount_type', ['none', 'percentage', 'fixed'])->default('none');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->decimal('final_price', 10, 2)->nullable(); // Calculated
            
            // Availability
            $table->boolean('is_available')->default(true);
            $table->integer('order')->default(0);
            
            $table->timestamps();
            
            $table->index('business_branch_id');
            $table->index('header_title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};