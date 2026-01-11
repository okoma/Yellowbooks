<?php
// database/migrations/2024_01_XX_000005_add_business_parent_constraints.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add CHECK constraints to ensure records belong to either business OR branch, not both
        
        if (DB::getDriverName() === 'mysql') {
            // MySQL 8.0.16+ supports CHECK constraints
            DB::statement('
                ALTER TABLE business_views 
                ADD CONSTRAINT check_business_views_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
            
            DB::statement('
                ALTER TABLE business_interactions 
                ADD CONSTRAINT check_business_interactions_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
            
            DB::statement('
                ALTER TABLE saved_businesses 
                ADD CONSTRAINT check_saved_businesses_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
            
            DB::statement('
                ALTER TABLE business_view_summaries 
                ADD CONSTRAINT check_business_view_summaries_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
        }
        
        // PostgreSQL also supports CHECK constraints
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('
                ALTER TABLE business_views 
                ADD CONSTRAINT check_business_views_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
            
            DB::statement('
                ALTER TABLE business_interactions 
                ADD CONSTRAINT check_business_interactions_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
            
            DB::statement('
                ALTER TABLE saved_businesses 
                ADD CONSTRAINT check_saved_businesses_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
            
            DB::statement('
                ALTER TABLE business_view_summaries 
                ADD CONSTRAINT check_business_view_summaries_parent 
                CHECK (
                    (business_id IS NOT NULL AND business_branch_id IS NULL) OR
                    (business_id IS NULL AND business_branch_id IS NOT NULL)
                )
            ');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE business_views DROP CHECK check_business_views_parent');
            DB::statement('ALTER TABLE business_interactions DROP CHECK check_business_interactions_parent');
            DB::statement('ALTER TABLE saved_businesses DROP CHECK check_saved_businesses_parent');
            DB::statement('ALTER TABLE business_view_summaries DROP CHECK check_business_view_summaries_parent');
        }
        
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE business_views DROP CONSTRAINT check_business_views_parent');
            DB::statement('ALTER TABLE business_interactions DROP CONSTRAINT check_business_interactions_parent');
            DB::statement('ALTER TABLE saved_businesses DROP CONSTRAINT check_saved_businesses_parent');
            DB::statement('ALTER TABLE business_view_summaries DROP CONSTRAINT check_business_view_summaries_parent');
        }
    }
};