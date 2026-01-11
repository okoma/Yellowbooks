<?php
// ============================================
// RecentActivityWidget.php
// Location: app/Filament/Admin/Widgets/RecentActivityWidget.php
// Purpose: Show latest platform activities
// ============================================

namespace App\Filament\Admin\Widgets;

use App\Models\BusinessClaim;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class RecentActivityWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent Platform Activity')
            ->query(function () {

                // --------------------------------------------------
                // 1. Build UNION query (NO ordering, NO outer scopes)
                // --------------------------------------------------
                $unionQuery = BusinessClaim::query()
                    ->withoutGlobalScopes() // IMPORTANT
                    ->select([
                        'business_claims.id as id',
                        'business_claims.created_at as created_at',
                        DB::raw("'Business Claim' as type"),
                        DB::raw("CONCAT(
                            'New claim for ',
                            (SELECT business_name FROM businesses WHERE id = business_claims.business_id)
                        ) as description"),
                        DB::raw('business_claims.user_id as actor_id'),
                    ])
                    ->whereDate('business_claims.created_at', '>=', now()->subDays(7))
                    ->whereNull('business_claims.deleted_at')

                    ->unionAll(
                        DB::table('business_verifications')
                            ->select([
                                'id',
                                'created_at',
                                DB::raw("'Verification' as type"),
                                DB::raw("CONCAT(
                                    'Verification submitted for ',
                                    (SELECT business_name FROM businesses WHERE id = business_verifications.business_id)
                                ) as description"),
                                DB::raw('submitted_by as actor_id'),
                            ])
                            ->whereDate('created_at', '>=', now()->subDays(7))
                            ->whereNull('deleted_at')
                    )

                    ->unionAll(
                        DB::table('users')
                            ->select([
                                'id',
                                'created_at',
                                DB::raw("'New User' as type"),
                                DB::raw("CONCAT('New user registered: ', name) as description"),
                                DB::raw('id as actor_id'),
                            ])
                            ->whereDate('created_at', '>=', now()->subDays(7))
                            ->whereNull('deleted_at')
                    )

                    ->unionAll(
                        DB::table('reviews')
                            ->select([
                                'id',
                                'created_at',
                                DB::raw("'Review' as type"),
                                DB::raw("CONCAT('New review (', rating, ' stars)') as description"),
                                DB::raw('user_id as actor_id'),
                            ])
                            ->whereDate('created_at', '>=', now()->subDays(7))
                            ->whereNull('deleted_at')
                    );

                // --------------------------------------------------
                // 2. Wrap UNION in Eloquent WITHOUT global scopes
                // --------------------------------------------------
                return BusinessClaim::query()
                    ->withoutGlobalScopes() // THIS is the real fix
                    ->fromSub($unionQuery, 'activities')
                    ->select([
                        'id',
                        'created_at',
                        'type',
                        'description',
                        'actor_id',
                    ]);
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'warning' => 'Business Claim',
                        'info'    => 'Verification',
                        'success' => 'New User',
                        'primary' => 'Review',
                    ]),

                Tables\Columns\TextColumn::make('description')
                    ->wrap()
                    ->limit(100),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->since()
                    ->sortable(),
            ])
            ->defaultPaginationPageOption(15)
            ->paginated([15]);
    }
}
