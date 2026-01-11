<?php
// ============================================
// app/Filament/Admin/Resources/BusinessClaimResource/Pages/ListBusinessClaims.php
// Location: app/Filament/Admin/Resources/BusinessClaimResource/Pages/ListBusinessClaims.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources\BusinessClaimResource\Pages;

use App\Filament\Admin\Resources\BusinessClaimResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessClaims extends ListRecords
{
    protected static string $resource = BusinessClaimResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::count()),
            
            'pending' => Tab::make('Pending')
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            
            'under_review' => Tab::make('Under Review')
                ->badge(fn () => $this->getModel()::where('status', 'under_review')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'under_review')),
            
            'approved' => Tab::make('Approved')
                ->badge(fn () => $this->getModel()::where('status', 'approved')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            
            'rejected' => Tab::make('Rejected')
                ->badge(fn () => $this->getModel()::where('status', 'rejected')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            
            'verified_contacts' => Tab::make('Verified Contacts')
                ->badge(fn () => $this->getModel()::where('phone_verified', true)
                    ->where('email_verified', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('phone_verified', true)
                    ->where('email_verified', true)),
        ];
    }
}
