<?php

// ============================================
// database/migrations/2024_12_28_000022_create_business_branch_location_table.php
// PIVOT TABLE
// Branch to Locations (branch level)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_branch_location', function (Blueprint $table) {
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');
            $table->primary(['business_branch_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_branch_location');
    }
};