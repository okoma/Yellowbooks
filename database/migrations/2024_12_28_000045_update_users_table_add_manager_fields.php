<?php

// ============================================
// FILE: 2024_12_28_000045_update_users_table_add_manager_fields.php
// Use Case:
// When showing user dashboard, quickly check:
// "if (user->is_branch_manager) show manager dashboard"
// "You are managing {managing_branches_count} branches"
// ============================================

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
        Schema::table('users', function (Blueprint $table) {

            // Updated when manager is assigned/removed
            $table->boolean('is_branch_manager')->default(false)->after('role');
            
            // Updated when manager assignments change
            $table->integer('managing_branches_count')->default(0)->after('is_branch_manager');
            
    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'is_branch_manager',
                'managing_branches_count'
            ]);
        });
    }
};