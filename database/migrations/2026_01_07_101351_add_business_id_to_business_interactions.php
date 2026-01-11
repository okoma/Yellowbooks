<?php
// database/migrations/2024_01_XX_000002_add_business_id_to_business_interactions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_interactions', function (Blueprint $table) {
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
        
        // Add composite indexes for analytics queries
        Schema::table('business_interactions', function (Blueprint $table) {
            $table->index(['business_id', 'interaction_date']);
            $table->index(['business_branch_id', 'interaction_date']);
            $table->index(['business_id', 'interaction_type']);
            $table->index(['business_branch_id', 'interaction_type']);
        });
    }

    public function down(): void
    {
        Schema::table('business_interactions', function (Blueprint $table) {
            $table->dropForeign(['business_id']);
            $table->dropIndex(['business_id']);
            $table->dropIndex(['business_id', 'interaction_date']);
            $table->dropIndex(['business_branch_id', 'interaction_date']);
            $table->dropIndex(['business_id', 'interaction_type']);
            $table->dropIndex(['business_branch_id', 'interaction_type']);
            $table->dropColumn('business_id');
            
            $table->unsignedBigInteger('business_branch_id')->nullable(false)->change();
        });
    }
};