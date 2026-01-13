<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            // Try to drop old index if it exists
            try {
                $table->dropIndex(['order']);
            } catch (\Exception $e) {
                // Might not exist or different name - that's okay
            }
            
            // Add properly named index
            if (!$this->indexExists('officials', 'officials_display_order_idx')) {
                $table->index(['order'], 'officials_display_order_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('officials', function (Blueprint $table) {
            $table->dropIndex('officials_display_order_idx');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.statistics 
             WHERE table_schema = ? 
             AND table_name = ? 
             AND index_name = ?",
            [$database, $table, $index]
        );
        
        return $result[0]->count > 0;
    }
};
