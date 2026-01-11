<?php

// ============================================
// database/migrations/2024_12_28_000024_create_business_payment_method_table.php
// PIVOT TABLE
// Business to Payment Methods (business level, shared across branches)
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_payment_method', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->primary(['business_id', 'payment_method_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_payment_method');
    }
};