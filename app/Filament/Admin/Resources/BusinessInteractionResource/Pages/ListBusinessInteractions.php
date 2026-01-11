<?php
// ============================================
// 3. ListBusinessInteractions.php
// Location: app/Filament/Admin/Resources/BusinessInteractionResource/Pages/ListBusinessInteractions.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessInteractionResource\Pages;

use App\Filament\Admin\Resources\BusinessInteractionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBusinessInteractions extends ListRecords
{
    protected static string $resource = BusinessInteractionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export All Interactions')
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
            'all' => Tab::make('All Interactions'),
            
            'calls' => Tab::make('Phone Calls')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('interaction_type', 'call'))
                ->badge(fn () => \App\Models\BusinessInteraction::where('interaction_type', 'call')->count()),
            
            'whatsapp' => Tab::make('WhatsApp')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('interaction_type', 'whatsapp'))
                ->badge(fn () => \App\Models\BusinessInteraction::where('interaction_type', 'whatsapp')->count()),
            
            'email' => Tab::make('Email')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('interaction_type', 'email'))
                ->badge(fn () => \App\Models\BusinessInteraction::where('interaction_type', 'email')->count()),
            
            'website' => Tab::make('Website')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('interaction_type', 'website'))
                ->badge(fn () => \App\Models\BusinessInteraction::where('interaction_type', 'website')->count()),
            
            'map' => Tab::make('Map/Directions')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('interaction_type', 'map'))
                ->badge(fn () => \App\Models\BusinessInteraction::where('interaction_type', 'map')->count()),
            
            'today' => Tab::make('Today')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereDate('interaction_date', today()))
                ->badge(fn () => \App\Models\BusinessInteraction::whereDate('interaction_date', today())->count())
                ->badgeColor('success'),
        ];
    }
}
