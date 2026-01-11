<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Add polymorphic columns only if they don't exist
            if (!Schema::hasColumn('reviews', 'reviewable_type')) {
                $table->string('reviewable_type')->nullable()->after('id');
            }
            
            if (!Schema::hasColumn('reviews', 'reviewable_id')) {
                $table->unsignedBigInteger('reviewable_id')->nullable()->after('reviewable_type');
            }
            
            // Add replied_by if it doesn't exist
            if (!Schema::hasColumn('reviews', 'replied_by')) {
                $table->foreignId('replied_by')->nullable()->after('replied_at')->constrained('users')->nullOnDelete();
            }
        });

        // Migrate existing data
        // Priority: Reviews with business_branch_id go to BusinessBranch
        DB::table('reviews')
            ->whereNotNull('business_branch_id')
            ->whereNull('reviewable_type') // Only update if not already migrated
            ->update([
                'reviewable_type' => 'App\\Models\\BusinessBranch',
                'reviewable_id' => DB::raw('business_branch_id')
            ]);

        // Reviews with only business_id (no branch) go to Business
        DB::table('reviews')
            ->whereNotNull('business_id')
            ->whereNull('business_branch_id')
            ->whereNull('reviewable_type') // Only update if not already migrated
            ->update([
                'reviewable_type' => 'App\\Models\\Business',
                'reviewable_id' => DB::raw('business_id')
            ]);

        // Now make polymorphic columns required and indexed
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('reviewable_type')->nullable(false)->change();
            $table->unsignedBigInteger('reviewable_id')->nullable(false)->change();
            
            // Add composite index for polymorphic relationship (check if exists first)
            if (!$this->indexExists('reviews', 'reviews_reviewable_index')) {
                $table->index(['reviewable_type', 'reviewable_id'], 'reviews_reviewable_index');
            }
            
            // Add unique constraint (check if exists first)
            if (!$this->indexExists('reviews', 'reviews_user_reviewable_unique')) {
                $table->unique(['reviewable_type', 'reviewable_id', 'user_id'], 'reviews_user_reviewable_unique');
            }
            
            // Drop old foreign keys if they exist
            if ($this->foreignKeyExists('reviews', 'reviews_business_id_foreign')) {
                $table->dropForeign(['business_id']);
            }
            
            if ($this->foreignKeyExists('reviews', 'reviews_business_branch_id_foreign')) {
                $table->dropForeign(['business_branch_id']);
            }
            
            // Drop old columns if they still exist
            if (Schema::hasColumn('reviews', 'business_id')) {
                $table->dropColumn('business_id');
            }
            
            if (Schema::hasColumn('reviews', 'business_branch_id')) {
                $table->dropColumn('business_branch_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // Re-add old columns
            if (!Schema::hasColumn('reviews', 'business_id')) {
                $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->cascadeOnDelete();
            }
            
            if (!Schema::hasColumn('reviews', 'business_branch_id')) {
                $table->foreignId('business_branch_id')->nullable()->after('business_id')->constrained('business_branches')->cascadeOnDelete();
            }
        });

        // Migrate data back
        DB::table('reviews')
            ->where('reviewable_type', 'App\\Models\\Business')
            ->update(['business_id' => DB::raw('reviewable_id')]);

        DB::table('reviews')
            ->where('reviewable_type', 'App\\Models\\BusinessBranch')
            ->update(['business_branch_id' => DB::raw('reviewable_id')]);

        Schema::table('reviews', function (Blueprint $table) {
            // Drop polymorphic constraints and indexes
            if ($this->indexExists('reviews', 'reviews_user_reviewable_unique')) {
                $table->dropUnique('reviews_user_reviewable_unique');
            }
            
            if ($this->indexExists('reviews', 'reviews_reviewable_index')) {
                $table->dropIndex('reviews_reviewable_index');
            }
            
            // Drop polymorphic columns
            if (Schema::hasColumn('reviews', 'reviewable_type')) {
                $table->dropColumn('reviewable_type');
            }
            
            if (Schema::hasColumn('reviews', 'reviewable_id')) {
                $table->dropColumn('reviewable_id');
            }
            
            // Drop replied_by if it exists
            if (Schema::hasColumn('reviews', 'replied_by')) {
                if ($this->foreignKeyExists('reviews', 'reviews_replied_by_foreign')) {
                    $table->dropForeign(['replied_by']);
                }
                $table->dropColumn('replied_by');
            }
            
            // Restore old unique constraint if business_branch_id exists
            if (Schema::hasColumn('reviews', 'business_branch_id')) {
                if (!$this->indexExists('reviews', 'reviews_business_branch_id_user_id_unique')) {
                    $table->unique(['business_branch_id', 'user_id']);
                }
            }
        });
    }

    /**
     * Check if an index exists on a table
     */
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

    /**
     * Check if a foreign key exists on a table
     */
    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        $connection = Schema::getConnection();
        $database = $connection->getDatabaseName();
        
        $result = DB::select(
            "SELECT COUNT(*) as count 
             FROM information_schema.table_constraints 
             WHERE constraint_schema = ? 
             AND table_name = ? 
             AND constraint_name = ?
             AND constraint_type = 'FOREIGN KEY'",
            [$database, $table, $foreignKey]
        );
        
        return $result[0]->count > 0;
    }
};