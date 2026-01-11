<?php
// ============================================
// 5. TopPerformersWidget.php
// Location: app/Filament/Admin/Widgets/TopPerformersWidget.php
// Purpose: Show top businesses by metrics
// ============================================

namespace App\Filament\Admin\Widgets;

use App\Models\BusinessBranch;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopPerformersWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Top Performing Businesses (This Month)')
            ->query(
                BusinessBranch::query()
                    ->with(['business', 'reviews'])
                    ->withCount([
                        'views as views_count' => function ($query) {
                            $query->whereDate('created_at', '>=', now()->startOfMonth());
                        },
                        'leads as leads_count' => function ($query) {
                            $query->whereDate('created_at', '>=', now()->startOfMonth());
                        },
                        'reviews as reviews_count',
                    ])
                    ->having('views_count', '>', 0)
                    ->orderBy('views_count', 'desc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->limit(30)
                    ->description(fn ($record) => $record->branch_title),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Leads')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state))
                    ->badge()
                    ->color('warning'),
                
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'No ratings')
                    ->sortable(),
            ])
            ->paginated(false);
    }
}