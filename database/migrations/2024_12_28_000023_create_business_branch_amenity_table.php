<?php

// ============================================
// database/migrations/2024_12_28_000023_create_business_branch_amenity_table.php
// PIVOT TABLE
// Branch to Amenities (branch level - each branch may have different amenities)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_branch_amenity', function (Blueprint $table) {
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
            $table->primary(['business_branch_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_branch_amenity');
    }
};