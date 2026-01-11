<?php
// ============================================
// 1. ListBusinessViews.php
// Location: app/Filament/Admin/Resources/BusinessViewResource/Pages/ListBusinessViews.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessViewResource\Pages;

use App\Filament\Admin\Resources\BusinessViewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessViews extends ListRecords
{
    protected static string $resource = BusinessViewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_all')
                ->label('Export All Views')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () {
                    // TODO: Implement CSV export
                    \Filament\Notifications\Notification::make()
                        ->info()
                        ->title('Export Started')
                        ->body('Full CSV export will be ready shortly.')
                        ->send();
                }),
        ];
    }
}
