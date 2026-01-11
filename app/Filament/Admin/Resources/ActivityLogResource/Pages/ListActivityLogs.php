<?php

// 1. ListActivityLogs.php
// Location: app/Filament/Admin/Resources/ActivityLogResource/Pages/ListActivityLogs.php

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export All Logs')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->info()
                        ->title('Export Started')
                        ->body('Full CSV export will be ready shortly.')
                        ->send();
                }),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Activity'),
            
            'created' => Tab::make('Created')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('action', 'created'))
                ->badge(fn () => \App\Models\ActivityLog::where('action', 'created')->whereDate('created_at', today())->count()),
            
            'updated' => Tab::make('Updated')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('action', 'updated'))
                ->badge(fn () => \App\Models\ActivityLog::where('action', 'updated')->whereDate('created_at', today())->count()),
            
            'deleted' => Tab::make('Deleted')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('action', 'deleted'))
                ->badge(fn () => \App\Models\ActivityLog::where('action', 'deleted')->whereDate('created_at', today())->count())
                ->badgeColor('danger'),
            
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('created_at', today()))
                ->badge(fn () => \App\Models\ActivityLog::whereDate('created_at', today())->count())
                ->badgeColor('success'),
        ];
    }
}