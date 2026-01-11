<?php

// ============================================
// database/migrations/2024_12_28_000029_create_activity_logs_table.php
// Track all important actions for audit trail
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
public function up(): void
{
    Schema::create('activity_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
        
        // Action Details
        $table->string('action');
        $table->text('description');
        
        // Related Model (polymorphic)
        $table->morphs('subject'); // auto index included
        
        // Context
        $table->json('properties')->nullable();
        $table->string('ip_address', 45)->nullable();
        $table->text('user_agent')->nullable();
        
        $table->timestamps();
        
        $table->index(['user_id', 'created_at']); // keep this one
    });
}


    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};