<?php

// ============================================
// database/migrations/2024_12_28_000014_create_leads_table.php
// Leads are PER BRANCH
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Contact Info
            $table->string('client_name');
            $table->string('email');
            $table->string('phone', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            
            // Which button was clicked
            $table->string('lead_button_text')->nullable(); // "Book Now", "Get Quote"
            
            // Dynamic fields based on business type
            $table->json('custom_fields')->nullable();
            
            // Status
            $table->enum('status', ['new', 'contacted', 'converted', 'rejected'])->default('new');
            $table->boolean('is_replied')->default(false);
            $table->timestamp('replied_at')->nullable();
            $table->text('reply_message')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['business_branch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};