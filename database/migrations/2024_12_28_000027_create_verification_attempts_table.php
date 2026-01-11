<?php

// ============================================
// database/migrations/2024_12_28_000027_create_verification_attempts_table.php
// Track all verification attempts for audit trail
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_verification_id')->constrained()->onDelete('cascade');
            
            $table->enum('verification_type', [
                'cac',
                'location',
                'email',
                'website',
                'phone',
                'document'
            ]);
            
            $table->enum('status', ['success', 'failed', 'pending']);
            $table->text('details')->nullable(); // What happened
            $table->json('metadata')->nullable(); // Extra data
            
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('attempted_at')->useCurrent();
            
            $table->timestamps();
            
            $table->index(['business_verification_id', 'verification_type'], 'verif_attempts_idx');

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_attempts');
    }
};