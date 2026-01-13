<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            // Add branch support
            $table->foreignId('business_branch_id')
                ->nullable()
                ->after('business_id')
                ->constrained('business_branches')
                ->onDelete('cascade');
            
            // Make business_id nullable
            $table->foreignId('business_id')->nullable()->change();
            
            // Add indexes
            $table->index(['business_id', 'is_active'], 'social_accounts_business_active_idx');
            $table->index(['business_branch_id', 'is_active'], 'social_accounts_branch_active_idx');
            $table->index(['business_id', 'platform'], 'social_accounts_business_platform_idx');
            $table->index(['business_branch_id', 'platform'], 'social_accounts_branch_platform_idx');
        });
    }

    public function down(): void
    {
        Schema::table('social_accounts', function (Blueprint $table) {
            $table->dropIndex('social_accounts_business_active_idx');
            $table->dropIndex('social_accounts_branch_active_idx');
            $table->dropIndex('social_accounts_business_platform_idx');
            $table->dropIndex('social_accounts_branch_platform_idx');
            
            $table->dropForeign(['business_branch_id']);
            $table->dropColumn('business_branch_id');
            
            $table->foreignId('business_id')->nullable(false)->change();
        });
    }
};
