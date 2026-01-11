<?php
//ListLeads.php
namespace App\Filament\Admin\Resources\LeadResource\Pages;

use App\Filament\Admin\Resources\LeadResource;
use App\Models\Lead;
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
            'all' => Tab::make('All')
                ->badge(Lead::count()),
            
            'new' => Tab::make('New')
                ->badge(Lead::where('status', 'new')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'new')),
            
            'contacted' => Tab::make('Contacted')
                ->badge(Lead::where('status', 'contacted')->count())
                ->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'contacted')),
            
            'qualified' => Tab::make('Qualified')
                ->badge(Lead::where('status', 'qualified')->count())
                ->badgeColor('primary')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'qualified')),
            
            'converted' => Tab::make('Converted')
                ->badge(Lead::where('status', 'converted')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'converted')),
            
            'lost' => Tab::make('Lost')
                ->badge(Lead::where('status', 'lost')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'lost')),
            
            'unreplied' => Tab::make('Not Replied')
                ->badge(Lead::where('is_replied', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_replied', false)),
            
            'today' => Tab::make('Today')
                ->badge(Lead::whereDate('created_at', today())->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today())),
        ];
    }
}