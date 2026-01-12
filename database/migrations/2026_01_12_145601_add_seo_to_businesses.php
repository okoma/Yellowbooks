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
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('canonical_strategy')->default('self')->after('slug');
            $table->string('canonical_url')->nullable()->after('canonical_strategy');
            $table->text('meta_title')->nullable()->after('description');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->json('unique_features')->nullable()->after('gallery');
            $table->text('nearby_landmarks')->nullable()->after('unique_features');
            $table->boolean('has_unique_content')->default(true)->after('nearby_landmarks');
            $table->decimal('content_similarity_score', 5, 2)->nullable()->after('has_unique_content');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn([
                'canonical_strategy',
                'canonical_url',
                'meta_title',
                'meta_description',
                'unique_features',
                'nearby_landmarks',
                'has_unique_content',
                'content_similarity_score',
            ]);
        });
    }
};
