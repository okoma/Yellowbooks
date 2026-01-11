<?php
// ============================================
// SUBSCRIPTION WIDGET
// app/Filament/Business/Widgets/SubscriptionOverviewWidget.php
// Show current subscription status on dashboard
// ============================================

namespace App\Filament\Business\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class SubscriptionOverviewWidget extends Widget
{
    protected static string $view = 'filament.business.widgets.subscription-overview';
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?int $sort = 1;
    
    public function getData(): array
    {
        $user = Auth::user();
        $subscription = $user->subscription()->with('plan')->first();
        
        if (!$subscription) {
            return [
                'hasSubscription' => false,
                'plan' => null,
            ];
        }
        
        $plan = $subscription->plan;
        
        return [
            'hasSubscription' => true,
            'plan' => [
                'name' => $plan->name,
                'status' => $subscription->status,
                'starts_at' => $subscription->starts_at,
                'ends_at' => $subscription->ends_at,
                'days_remaining' => $subscription->daysRemaining(),
                'auto_renew' => $subscription->auto_renew,
            ],
            'usage' => [
                'branches' => [
                    'used' => $subscription->branches_used,
                    'limit' => $plan->max_branches,
                    'percentage' => $plan->max_branches ? ($subscription->branches_used / $plan->max_branches * 100) : 0,
                ],
                'products' => [
                    'used' => $subscription->products_used,
                    'limit' => $plan->max_products,
                    'percentage' => $plan->max_products ? ($subscription->products_used / $plan->max_products * 100) : 0,
                ],
                'photos' => [
                    'used' => $subscription->photos_used,
                    'limit' => $plan->max_photos,
                    'percentage' => $plan->max_photos ? ($subscription->photos_used / $plan->max_photos * 100) : 0,
                ],
            ],
            'features' => $plan->features ?? [],
        ];
    }
}
