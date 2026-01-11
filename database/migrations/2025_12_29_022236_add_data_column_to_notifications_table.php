<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add the data column
        Schema::table('notifications', function (Blueprint $table) {
            $table->json('data')->nullable()->after('notifiable_id');
        });
        
        // Populate existing notifications
        DB::table('notifications')->orderBy('id')->chunk(100, function ($notifications) {
            foreach ($notifications as $notification) {
                DB::table('notifications')
                    ->where('id', $notification->id)
                    ->update([
                        'data' => json_encode([
                            'format' => 'filament',
                            'title' => $notification->title ?? '',
                            'message' => $notification->message ?? '',
                            'action_url' => $notification->action_url ?? null,
                            'type' => $notification->type ?? 'system',
                        ])
                    ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};