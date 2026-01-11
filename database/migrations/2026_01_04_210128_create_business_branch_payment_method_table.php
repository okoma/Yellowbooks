<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_branch_payment_method', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_branch_id')
                ->constrained('business_branches')
                ->onDelete('cascade');
            $table->foreignId('payment_method_id')
                ->constrained('payment_methods')
                ->onDelete('cascade');
            $table->timestamps();
            
            // Ensure unique combinations
            $table->unique(['business_branch_id', 'payment_method_id'], 'branch_payment_unique');
            
            // Add indexes for better query performance
            $table->index('business_branch_id');
            $table->index('payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_branch_payment_method');
    }
};