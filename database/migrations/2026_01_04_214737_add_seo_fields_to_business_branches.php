<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('business_branches', function (Blueprint $table) {
            // SEO & Canonical URL management
            $table->string('canonical_strategy')->default('self')->after('slug');
            // Options: 'self' (index separately) or 'parent' (point to parent business)
            
            $table->string('canonical_url')->nullable()->after('canonical_strategy');
            // Custom canonical URL if needed (auto-generated if null)
            
            $table->text('meta_title')->nullable()->after('branch_description');
            // Custom meta title for SEO (auto-generated if null)
            
            $table->text('meta_description')->nullable()->after('meta_title');
            // Custom meta description for SEO (auto-generated if null)
            
            $table->json('unique_features')->nullable()->after('meta_description');
            // JSON array of unique features about this branch
            // Example: ["Free WiFi", "Parking Available", "Kids Play Area"]
            
            $table->text('nearby_landmarks')->nullable()->after('unique_features');
            // What's near this location? Helps make content unique
            // Example: "Located next to Computer Village, 5 mins from Ikeja Mall"
            
            $table->boolean('has_unique_content')->default(false)->after('nearby_landmarks');
            // Flag indicating if branch has passed uniqueness check
            
            $table->decimal('content_similarity_score', 5, 2)->nullable()->after('has_unique_content');
            // Percentage similarity to parent business (0-100)
            // Lower is better (< 30% is ideal)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_branches', function (Blueprint $table) {
            $table->dropColumn([
                'canonical_strategy',
                'canonical_url',
                'meta_title',
                'meta_description',
                'unique_features',
                'nearby_landmarks',
                'has_unique_content',
                'content_similarity_score',
            ]);
        });
    }
};