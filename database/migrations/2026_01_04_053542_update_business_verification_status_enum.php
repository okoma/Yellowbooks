<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Simplify status ENUM to match Filament form
        DB::statement("
            ALTER TABLE business_verifications 
            MODIFY COLUMN status ENUM(
                'pending',
                'approved',
                'rejected',
                'requires_resubmission'
            ) DEFAULT 'pending'
        ");
        
        // Optional: Update existing records with old statuses to new statuses
        DB::table('business_verifications')
            ->whereIn('status', ['documents_review', 'email_pending', 'website_pending', 'phone_verification'])
            ->update(['status' => 'pending']);
    }

    public function down(): void
    {
        // Revert to original ENUM with all statuses
        DB::statement("
            ALTER TABLE business_verifications 
            MODIFY COLUMN status ENUM(
                'pending',
                'documents_review',
                'email_pending',
                'website_pending',
                'phone_verification',
                'approved',
                'rejected',
                'requires_resubmission'
            ) DEFAULT 'pending'
        ");
    }
};