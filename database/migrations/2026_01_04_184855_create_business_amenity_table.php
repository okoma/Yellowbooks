<?php
// ============================================
// database/migrations/2025_01_04_000002_create_business_amenity_table.php
// Pivot table for Business <-> Amenity many-to-many relationship
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained('amenities')->cascadeOnDelete();
            $table->timestamps();
            
            // Prevent duplicate relationships
            $table->unique(['business_id', 'amenity_id']);
            
            // Indexes for faster queries
            $table->index('business_id');
            $table->index('amenity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_amenity');
    }
};