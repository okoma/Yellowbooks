<?php
// database/migrations/2024_01_XX_000004_add_business_id_to_business_view_summaries.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            // Add business_id column
            $table->unsignedBigInteger('business_id')->nullable()->after('id');
            
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
        
        // Add composite indexes for aggregation queries
        Schema::table('business_view_summaries', function (Blueprint $table) {
            $table->index(['business_id', 'period_type', 'period_key']);
            $table->index(['business_branch_id', 'period_type', 'period_key']);
        });
    }

    public function down(): void
    {
        Schema::table('business_view_summaries', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->dropIndex(['business_id', 'period_type', 'period_key']);
            $table->dropIndex(['business_branch_id', 'period_type', 'period_key']);
            $table->dropColumn('business_id');
            
            $table->unsignedBigInteger('business_branch_id')->nullable(false)->change();
        });
    }
};