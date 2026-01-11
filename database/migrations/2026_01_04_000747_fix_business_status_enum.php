<?php
// ============================================
// database/migrations/2025_01_04_fix_business_status_enum.php
// Fix status column to match BusinessResource options
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update the ENUM to match your Filament form options
        DB::statement("
            ALTER TABLE businesses 
            MODIFY COLUMN status ENUM(
                'draft', 
                'pending_review', 
                'active', 
                'suspended', 
                'closed'
            ) DEFAULT 'pending_review'
        ");
        
        // Optional: Update existing records to new values
        DB::table('businesses')->where('status', 'pending')->update(['status' => 'pending_review']);
        DB::table('businesses')->where('status', 'approved')->update(['status' => 'active']);
        DB::table('businesses')->where('status', 'rejected')->update(['status' => 'closed']);
    }

    public function down(): void
    {
        // Revert to original ENUM
        DB::statement("
            ALTER TABLE businesses 
            MODIFY COLUMN status ENUM(
                'pending', 
                'approved', 
                'rejected', 
                'suspended'
            ) DEFAULT 'pending'
        ");
    }
};