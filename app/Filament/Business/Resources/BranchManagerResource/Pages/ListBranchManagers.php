<?php
// ============================================
// app/Filament/Business/Resources/BranchManagerResource/Pages/ListBranchManagers.php
// ============================================

namespace App\Filament\Business\Resources\BranchManagerResource\Pages;

use App\Filament\Business\Resources\BranchManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBranchManagers extends ListRecords
{
    protected static string $resource = BranchManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('invite_manager')
                ->label('Invite New Manager')
                ->icon('heroicon-o-user-plus')
                ->url(route('filament.business.resources.manager-invitations.create'))
                ->color('primary'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->count()),

            'active' => Tab::make('Active')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('is_active', true)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),

            'inactive' => Tab::make('Inactive')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('is_active', false)->count())
                ->badgeColor('gray')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),

            'primary' => Tab::make('Primary Managers')
                ->badge(fn () => $this->getModel()::whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                })->where('is_primary', true)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_primary', true)),
        ];
    }
}