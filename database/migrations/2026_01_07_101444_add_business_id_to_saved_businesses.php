<?php
// database/migrations/2024_01_XX_000003_add_business_id_to_saved_businesses.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_businesses', function (Blueprint $table) {
            // Add business_id column
            $table->unsignedBigInteger('business_id')->nullable()->after('user_id');
            
            // Add foreign key
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');
            
            // Add index
            $table->index('business_id');
            
            // Make business_branch_id nullable
            $table->unsignedBigInteger('business_branch_id')->nullable()->change();
        });
        
        // Add composite indexes
        Schema::table('saved_businesses', function (Blueprint $table) {
            $table->index(['user_id', 'business_id']);
            $table->index(['user_id', 'business_branch_id']);
        });
        
        // Add unique constraint: user can only save a business/branch once
        Schema::table('saved_businesses', function (Blueprint $table) {
            $table->unique(['user_id', 'business_id'], 'unique_user_business');
            // Note: unique constraint on user_id + business_branch_id should already exist
        });
    }

    public function down(): void
    {
        Schema::table('saved_businesses', function (Blueprint $table) {
            $table->dropUnique('unique_user_business');
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->dropIndex(['user_id', 'business_id']);
            $table->dropIndex(['user_id', 'business_branch_id']);
            $table->dropColumn('business_id');
            
            $table->unsignedBigInteger('business_branch_id')->nullable(false)->change();
        });
    }
};