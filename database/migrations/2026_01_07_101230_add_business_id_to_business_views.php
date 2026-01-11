<?php
// database/migrations/2024_01_XX_000001_add_business_id_to_business_views.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_views', function (Blueprint $table) {
            // Add business_id column (nullable because existing records are branch-only)
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            
            // Add foreign key
            $table->foreign('business_id')
                  ->references('id')
                  ->on('businesses')
                  ->onDelete('cascade');
            
            // Add index for faster queries
            $table->index('business_id');
            
            // Make business_branch_id nullable (was required before)
            $table->unsignedBigInteger('business_branch_id')->nullable()->change();
        });
        
        // Add composite index for queries that filter by both
        Schema::table('business_views', function (Blueprint $table) {
            $table->index(['business_id', 'view_date']);
            $table->index(['business_branch_id', 'view_date']);
        });
    }

    public function down(): void
    {
        Schema::table('business_views', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->dropIndex(['business_id', 'view_date']);
            $table->dropIndex(['business_branch_id', 'view_date']);
            $table->dropColumn('business_id');
            
            // Restore business_branch_id as required
            $table->unsignedBigInteger('business_branch_id')->nullable(false)->change();
        });
    }
};