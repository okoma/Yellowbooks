<?php
// ============================================
// database/migrations/2025_01_05_000001_add_branch_support_to_officials_table.php
// Add branch support to officials
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            // Add branch support
            $table->foreignId('business_branch_id')->nullable()->after('business_id')->constrained()->onDelete('cascade');
            
            // Add active status
            $table->boolean('is_active')->default(true)->after('social_accounts');
            
            // Modify business_id to be nullable (since officials can belong to branch instead)
            $table->foreignId('business_id')->nullable()->change();
            
            // Add indexes
            $table->index(['business_id', 'is_active']);
            $table->index(['business_branch_id', 'is_active']);
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            // Remove indexes
            $table->dropIndex(['business_id', 'is_active']);
            $table->dropIndex(['business_branch_id', 'is_active']);
            $table->dropIndex(['order']);
            
            // Remove columns
            $table->dropForeign(['business_branch_id']);
            $table->dropColumn([
                'business_branch_id',
                'is_active',
            ]);
            
            // Make business_id required again
            $table->foreignId('business_id')->nullable(false)->change();
        });
    }
};