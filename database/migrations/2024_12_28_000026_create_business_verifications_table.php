<?php

// ============================================
// database/migrations/2024_12_28_000026_create_business_verifications_table.php
// Verification workflow - MORE STRICT than claiming
// Claiming = "I own this" → Verification = "Prove it"
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_claim_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            
            // 1. CAC Verification (REQUIRED)
            $table->string('cac_number')->nullable(); // RC Number
            $table->string('cac_document')->nullable(); // PDF/Image of CAC certificate
            $table->boolean('cac_verified')->default(false);
            $table->text('cac_notes')->nullable(); // Admin notes
            
            // 2. Location Verification (REQUIRED)
            $table->text('office_address'); // Physical address
            $table->string('office_photo')->nullable(); // Photo of office/storefront
            $table->decimal('office_latitude', 10, 7)->nullable();
            $table->decimal('office_longitude', 10, 7)->nullable();
            $table->boolean('location_verified')->default(false);
            $table->text('location_notes')->nullable();
            
            // 3. Business Email Verification (REQUIRED)
            $table->string('business_email'); // Must be company domain (not gmail/yahoo)
            $table->string('email_verification_token')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->text('email_notes')->nullable();
            
            // 4. Website Meta Tag Verification (OPTIONAL but recommended)
            $table->string('website_url')->nullable();
            $table->string('meta_tag_code')->unique()->nullable(); // Generated verification code
            // Example: <meta name="yellowbooks-verification" content="YB-ABC123XYZ">
            $table->boolean('website_verified')->default(false);
            $table->timestamp('website_verified_at')->nullable();
            $table->text('website_notes')->nullable();
            
            // 5. Additional Documents (Optional)
            $table->json('additional_documents')->nullable();
            // Example: [
            //   {"type": "business_license", "path": "path/to/file.pdf"},
            //   {"type": "utility_bill", "path": "path/to/bill.pdf"}
            // ]
            
            // Overall Status
            $table->enum('status', [
                'pending',              // Just submitted
                'documents_review',     // Reviewing documents
                'email_pending',        // Waiting for email confirmation
                'website_pending',      // Waiting for meta tag verification
                'phone_verification',   // Calling to verify
                'approved',             // Fully verified
                'rejected',             // Verification failed
                'requires_resubmission' // Need more info
            ])->default('pending');
            
            // Verification Score (0-100)
            $table->integer('verification_score')->default(0);
            // CAC = 40 points, Location = 30 points, Email = 20 points, Website = 10 points
            
            // Admin Review
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('rejection_reason')->nullable();
            $table->text('admin_feedback')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Resubmission tracking
            $table->integer('resubmission_count')->default(0);
            $table->timestamp('last_resubmitted_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['business_id', 'status']);
            $table->index('cac_number');
            $table->index('business_email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_verifications');
    }
};