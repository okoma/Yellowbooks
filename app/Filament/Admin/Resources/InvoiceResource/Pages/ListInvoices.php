<?php
// ============================================
// app/Filament/Admin/Resources/InvoiceResource/Pages/ListInvoices.php
// ============================================

namespace App\Filament\Admin\Resources\InvoiceResource\Pages;

use App\Filament\Admin\Resources\InvoiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListInvoices extends ListRecords
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Invoices')
                ->icon('heroicon-o-document-text')
                ->badge(fn () => $this->getModel()::count()),
            
            'draft' => Tab::make('Draft')
                ->icon('heroicon-o-pencil')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(fn () => $this->getModel()::where('status', 'draft')->count())
                ->badgeColor('secondary'),
            
            'sent' => Tab::make('Sent')
                ->icon('heroicon-o-paper-airplane')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sent'))
                ->badge(fn () => $this->getModel()::where('status', 'sent')->count())
                ->badgeColor('info'),
            
            'paid' => Tab::make('Paid')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'paid'))
                ->badge(fn () => $this->getModel()::where('status', 'paid')->count())
                ->badgeColor('success'),
            
            'overdue' => Tab::make('Overdue')
                ->icon('heroicon-o-exclamation-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('status', '!=', 'paid')
                        ->where('due_date', '<', now())
                )
                ->badge(fn () => $this->getModel()::where('status', '!=', 'paid')
                    ->where('due_date', '<', now())
                    ->count()
                )
                ->badgeColor('danger'),
            
            'cancelled' => Tab::make('Cancelled')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'cancelled'))
                ->badge(fn () => $this->getModel()::where('status', 'cancelled')->count())
                ->badgeColor('warning'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // You can add invoice statistics widgets here
        ];
    }
}