<?php
// Location: app/Filament/Admin/Resources/BusinessVerificationResource/Pages/ListBusinessVerifications.php

namespace App\Filament\Admin\Resources\BusinessVerificationResource\Pages;

use App\Filament\Admin\Resources\BusinessVerificationResource;
use App\Models\BusinessVerification;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessVerifications extends ListRecords
{
    protected static string $resource = BusinessVerificationResource::class;
    
    protected function getHeaderActions(): array 
    { 
        return [
            Actions\CreateAction::make()
        ]; 
    }
    
    public function getTabs(): array 
    {
        return [
            'all' => Tab::make('All')
                ->badge(BusinessVerification::count()),
            
            'pending' => Tab::make('Pending')
                ->badge(BusinessVerification::where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),
            
            'approved' => Tab::make('Approved')
                ->badge(BusinessVerification::where('status', 'approved')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'approved')),
            
            'rejected' => Tab::make('Rejected')
                ->badge(BusinessVerification::where('status', 'rejected')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'rejected')),
            
            'resubmission' => Tab::make('Needs Resubmission')
                ->badge(BusinessVerification::where('status', 'requires_resubmission')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'requires_resubmission')),
            
            'premium' => Tab::make('Premium (90+)')
                ->badge(BusinessVerification::where('verification_score', '>=', 90)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('verification_score', '>=', 90)),
        ];
    }
}