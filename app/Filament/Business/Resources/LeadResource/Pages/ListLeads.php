<?php
// ============================================
// LIST LEADS PAGE
// app/Filament/Business/Resources/LeadResource/Pages/ListLeads.php
// ============================================

namespace App\Filament\Business\Resources\LeadResource\Pages;

use App\Filament\Business\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Leads'),
            
            'new' => Tab::make('New')
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')),
            
            'contacted' => Tab::make('Contacted')
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'contacted')),
            
            'qualified' => Tab::make('Qualified')
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'qualified')),
            
            'converted' => Tab::make('Converted')
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'converted')),
            
            'unreplied' => Tab::make('Unreplied')
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_replied', false)),
        ];
    }
}