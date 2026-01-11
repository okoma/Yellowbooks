<?php

// ============================================
// FILE: 2024_12_28_000042_create_branch_managers_table.php
// Business owner assigns "John" as manager of "Ikeja Branch"
// John can update products, respond to reviews, view leads
// Owner can revoke access anytime
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Manager Details
            $table->string('position')->default('Branch Manager');
            $table->string('employee_id')->nullable();
            
            // Contact
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('whatsapp', 20)->nullable();
            
            // Permissions
            $table->json('permissions')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false);
            
            // Assignment
            $table->foreignId('assigned_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('removed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['business_branch_id', 'is_active']);
            $table->index(['user_id', 'is_active']);
            $table->unique(['business_branch_id', 'user_id', 'deleted_at'], 'branch_user_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_managers');
    }
};