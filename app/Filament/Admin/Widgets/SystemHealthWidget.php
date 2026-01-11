<?php
// ============================================
// 4. SystemHealthWidget.php
// Location: app/Filament/Admin/Widgets/SystemHealthWidget.php
// Purpose: Show pending items and alerts
// ============================================

namespace App\Filament\Admin\Widgets;

use App\Models\BusinessClaim;
use App\Models\BusinessVerification;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\BusinessReport;
use App\Models\Review;
use Filament\Widgets\Widget;

class SystemHealthWidget extends Widget
{
    protected static ?int $sort = 4;
    protected static string $view = 'filament.admin.widgets.system-health-widget';

    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        return [
            'pending_claims' => BusinessClaim::whereIn('status', ['pending', 'under_review'])->count(),
            'pending_verifications' => BusinessVerification::whereIn('status', ['pending', 'requires_resubmission'])->count(),
            'failed_transactions' => Transaction::where('status', 'failed')->whereDate('created_at', '>=', now()->subDays(7))->count(),
            'expiring_subscriptions' => Subscription::where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])->count(),
            'pending_reports' => BusinessReport::where('status', 'pending')->count(),
            'unapproved_reviews' => Review::where('is_approved', false)->count(),
        ];
    }
}
