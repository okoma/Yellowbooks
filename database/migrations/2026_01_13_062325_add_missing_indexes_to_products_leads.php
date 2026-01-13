<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes to PRODUCTS table
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'business_id')) {
                $table->index('business_id', 'products_business_idx');
                $table->index(['business_id', 'is_available'], 'products_business_available_idx');
                $table->index(['business_id', 'header_title'], 'products_business_category_idx');
            }
        });

        // Add indexes to LEADS table
        Schema::table('leads', function (Blueprint $table) {
            if (Schema::hasColumn('leads', 'business_id')) {
                $table->index('business_id', 'leads_business_idx');
                $table->index(['business_id', 'status'], 'leads_business_status_idx');
                $table->index(['business_id', 'is_replied'], 'leads_business_replied_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_business_idx');
            $table->dropIndex('products_business_available_idx');
            $table->dropIndex('products_business_category_idx');
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex('leads_business_idx');
            $table->dropIndex('leads_business_status_idx');
            $table->dropIndex('leads_business_replied_idx');
        });
    }
};
