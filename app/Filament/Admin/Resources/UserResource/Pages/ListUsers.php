<?php
// ============================================
// app/Filament/Admin/Resources/UserResource/Pages/ListUsers.php
// ============================================

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\UserRole;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->badge(fn () => $this->getModel()::count()),
            
            'admins' => Tab::make()
                ->badge(fn () => $this->getModel()::where('role', UserRole::ADMIN)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::ADMIN)),
            
            'business_owners' => Tab::make('Business Owners')
                ->badge(fn () => $this->getModel()::where('role', UserRole::BUSINESS_OWNER)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::BUSINESS_OWNER)),
            
            'managers' => Tab::make('Branch Managers')
                ->badge(fn () => $this->getModel()::where('is_branch_manager', true)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_branch_manager', true)),
            
            'customers' => Tab::make()
                ->badge(fn () => $this->getModel()::where('role', UserRole::CUSTOMER)->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('role', UserRole::CUSTOMER)),
            
            'banned' => Tab::make()
                ->badge(fn () => $this->getModel()::where('is_banned', true)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_banned', true)),
        ];
    }
}