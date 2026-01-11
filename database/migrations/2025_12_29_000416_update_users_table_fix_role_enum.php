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
        Schema::table('users', function (Blueprint $table) {
            // Change enum to include all roles
            $table->enum('role', [
                'admin',           // YellowBooks admin - full control
                'moderator',       // Content moderator - limited admin
                'business_owner',  // Owns businesses
                'branch_manager',  // Manages specific branches
                'customer'         // Regular user
            ])->default('customer')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'business_owner', 'customer', 'moderator'])
                ->default('customer')
                ->change();
        });
    }
};