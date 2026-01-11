<?php

// ============================================
// database/migrations/2024_12_28_000034_create_invoices_table.php
// Invoice generation for transactions
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            
            // Invoice Details
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            
            // Amount
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency')->default('NGN');
            
            // Status
            $table->enum('status', ['draft', 'sent', 'paid', 'overdue', 'cancelled'])->default('draft');
            
            // Items (JSON)
            $table->json('items');
            // Example: [
            //   {"description": "Pro Plan - Monthly", "quantity": 1, "price": 5000, "total": 5000},
            //   {"description": "Ad Campaign Credits", "quantity": 10, "price": 500, "total": 5000}
            // ]
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            
            // Payment
            $table->timestamp('paid_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('invoice_number');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};