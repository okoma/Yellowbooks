<?php
// ============================================
// app/Filament/Admin/Resources/BusinessReportResource/Pages/ListBusinessReports.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessReportResource\Pages;

use App\Filament\Admin\Resources\BusinessReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessReports extends ListRecords
{
    protected static string $resource = BusinessReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus')
                ->label('Create Report'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Reports')
                ->icon('heroicon-o-flag')
                ->badge(fn () => $this->getModel()::count()),
            
            'pending' => Tab::make('Pending Review')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending'))
                ->badge(fn () => $this->getModel()::where('status', 'pending')->count())
                ->badgeColor('warning'),
            
            'reviewing' => Tab::make('Under Review')
                ->icon('heroicon-o-eye')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'reviewing'))
                ->badge(fn () => $this->getModel()::where('status', 'reviewing')->count())
                ->badgeColor('info'),
            
            'high_priority' => Tab::make('High Priority')
                ->icon('heroicon-o-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('reason', ['fake_business', 'scam', 'spam'])
                        ->whereIn('status', ['pending', 'reviewing'])
                )
                ->badge(fn () => $this->getModel()::whereIn('reason', ['fake_business', 'scam', 'spam'])
                    ->whereIn('status', ['pending', 'reviewing'])
                    ->count()
                )
                ->badgeColor('danger'),
            
            'resolved' => Tab::make('Resolved')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'resolved'))
                ->badge(fn () => $this->getModel()::where('status', 'resolved')->count())
                ->badgeColor('success'),
            
            'dismissed' => Tab::make('Dismissed')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'dismissed'))
                ->badge(fn () => $this->getModel()::where('status', 'dismissed')->count())
                ->badgeColor('secondary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add report statistics widgets here
        ];
    }
}