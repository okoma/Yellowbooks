<?php
// ============================================
// database/migrations/2025_01_04_000001_add_complete_business_data_to_businesses_table.php
// Add business_type, location, hours, gallery, and amenities support
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Business Type (for category filtering)
            $table->foreignId('business_type_id')
                ->nullable()
                ->after('user_id')
                ->constrained('business_types')
                ->nullOnDelete();
            
            // Location Data (so standalone businesses have complete location info)
            $table->text('address')->nullable()->after('whatsapp_message');
            $table->string('city')->nullable()->after('address');
            $table->string('area')->nullable()->after('city');
            $table->string('state')->nullable()->after('area');
            $table->decimal('latitude', 10, 7)->nullable()->after('state');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            
            // Business Hours (JSON format for each day)
            $table->json('business_hours')->nullable()->after('longitude');
            
            // Gallery (multiple images for the business)
            $table->json('gallery')->nullable()->after('business_hours');
            
            // Add indexes for location-based searches
            $table->index('city');
            $table->index('state');
            $table->index(['latitude', 'longitude']);
            $table->index('business_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['city']);
            $table->dropIndex(['state']);
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropIndex(['business_type_id']);
            
            // Drop foreign key
            $table->dropForeign(['business_type_id']);
            
            // Drop columns
            $table->dropColumn([
                'business_type_id',
                'address',
                'city',
                'area',
                'state',
                'latitude',
                'longitude',
                'business_hours',
                'gallery',
            ]);
        });
    }
};