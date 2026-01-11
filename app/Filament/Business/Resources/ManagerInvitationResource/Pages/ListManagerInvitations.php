<?php
// ============================================
// app/Filament/Business/Resources/ManagerInvitationResource/Pages/ListManagerInvitations.php
// ============================================

namespace App\Filament\Business\Resources\ManagerInvitationResource\Pages;

use App\Filament\Business\Resources\ManagerInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListManagerInvitations extends ListRecords
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Invite Manager')
                ->icon('heroicon-o-user-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->count()),

            'pending' => Tab::make('Pending')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'accepted' => Tab::make('Accepted')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('status', 'accepted')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'accepted')),

            'declined' => Tab::make('Declined')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('status', 'declined')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'declined')),

            'expired' => Tab::make('Expired')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('status', 'expired')->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'expired')),
        ];
    }
}