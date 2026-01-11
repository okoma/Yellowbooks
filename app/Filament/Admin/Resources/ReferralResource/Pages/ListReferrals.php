<?php
// ============================================
// 5. ListReferrals.php
// Location: app/Filament/Admin/Resources/ReferralResource/Pages/ListReferrals.php
// ============================================

namespace App\Filament\Admin\Resources\ReferralResource\Pages;

use App\Filament\Admin\Resources\ReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Referrals'),
            
            'pending' => Tab::make('Pending')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => \App\Models\Referral::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'completed' => Tab::make('Completed')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed'))
                ->badge(fn () => \App\Models\Referral::where('status', 'completed')->count())
                ->badgeColor('success'),
            
            'unpaid' => Tab::make('Unpaid Rewards')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'completed')->where('rewards_paid', false))
                ->badge(fn () => \App\Models\Referral::where('status', 'completed')->where('rewards_paid', false)->count())
                ->badgeColor('danger'),
            
            'this_month' => Tab::make('This Month')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year))
                ->badge(fn () => \App\Models\Referral::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count())
                ->badgeColor('info'),
        ];
    }
}
