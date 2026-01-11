<?php

// ============================================
// database/migrations/2024_12_28_000005_create_business_types_table.php
// Restaurant, Hotel, Hospital, etc.
// ============================================

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // "Restaurant", "Hotel"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            
            // Custom lead form fields for this type (JSON)
            $table->json('lead_form_fields')->nullable();
            // Example: [
            //   {"name": "preferred_date", "type": "date", "label": "Preferred Date", "required": true},
            //   {"name": "number_of_guests", "type": "number", "label": "Number of Guests", "required": false}
            // ]
            
            // Custom lead button options for this type
            $table->json('lead_button_options')->nullable();
            // Example: ["Book Now", "Get Quote", "Make Reservation", "Order Online"]
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_types');
    }
};