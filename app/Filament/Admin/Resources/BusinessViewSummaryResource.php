<?php
// ============================================
// 9. BusinessViewSummaryResource (READ-ONLY)
// Purpose: Aggregated view statistics
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessViewSummaryResource\Pages;
use App\Models\BusinessViewSummary;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class BusinessViewSummaryResource extends Resource
{
    protected static ?string $model = BusinessViewSummary::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'View Summaries';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('branch.business.business_name')->label('Business')->searchable()->limit(30),
            Tables\Columns\TextColumn::make('branch.branch_title')->label('Branch')->searchable(),
            Tables\Columns\TextColumn::make('period_type')->badge()->formatStateUsing(fn ($s) => ucfirst($s)),
            Tables\Columns\TextColumn::make('period_key')->label('Period')->sortable(),
            Tables\Columns\TextColumn::make('total_views')->formatStateUsing(fn ($s) => number_format($s))->sortable(),
            Tables\Columns\TextColumn::make('total_calls')->formatStateUsing(fn ($s) => number_format($s))->sortable(),
            Tables\Columns\TextColumn::make('total_whatsapp')->formatStateUsing(fn ($s) => number_format($s))->sortable(),
            Tables\Columns\TextColumn::make('total_emails')->formatStateUsing(fn ($s) => number_format($s))->sortable(),
        ])->defaultSort('period_key', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('period_type')->options(['daily' => 'Daily', 'monthly' => 'Monthly', 'yearly' => 'Yearly']),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListBusinessViewSummaries::route('/')];
    }

    public static function canCreate(): bool { return false; }
}