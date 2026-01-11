<?php
// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource/Pages/ViewBusinessBranch.php
// Location: app/Filament/Admin/Resources/BusinessBranchResource/Pages/ViewBusinessBranch.php
// Panel: Admin Panel
// Access: Admins, Moderators
// ============================================

namespace App\Filament\Admin\Resources\BusinessBranchResource\Pages;

use App\Filament\Admin\Resources\BusinessBranchResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewBusinessBranch extends ViewRecord
{
    protected static string $resource = BusinessBranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Branch Overview')
                    ->schema([
                        Components\TextEntry::make('branch_title')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('business.business_name')
                            ->label('Parent Business')
                            ->url(fn ($record) => route('filament.admin.resources.businesses.view', $record->business))
                            ->color('primary'),
                        
                        Components\TextEntry::make('slug')
                            ->icon('heroicon-m-link')
                            ->copyable(),
                        
                        Components\IconEntry::make('is_main_branch')
                            ->boolean()
                            ->label('Main Branch'),
                        
                        Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        
                        Components\TextEntry::make('branch_description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Location Details')
                    ->schema([
                        Components\TextEntry::make('address')
                            ->icon('heroicon-m-map-pin')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('city')
                            ->icon('heroicon-m-building-office'),
                        
                        Components\TextEntry::make('area')
                            ->icon('heroicon-m-map'),
                        
                        Components\TextEntry::make('state')
                            ->icon('heroicon-m-globe-alt'),
                        
                        Components\TextEntry::make('latitude')
                            ->visible(fn ($record) => $record->latitude),
                        
                        Components\TextEntry::make('longitude')
                            ->visible(fn ($record) => $record->longitude),
                    ])
                    ->columns(3),
                
                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                
                Components\Section::make('Business Hours')
                    ->schema([
                        Components\TextEntry::make('business_hours')
                            ->formatStateUsing(function ($state) {
                                if (!$state) return 'Not set';
                                
                                $output = '';
                                foreach ($state as $day => $hours) {
                                    $dayName = ucfirst($day);
                                    if ($hours['closed'] ?? false) {
                                        $output .= "{$dayName}: Closed\n";
                                    } else {
                                        $output .= "{$dayName}: {$hours['open']} - {$hours['close']}\n";
                                    }
                                }
                                return $output;
                            })
                            ->html()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                
                Components\Section::make('Amenities')
                    ->schema([
                        Components\TextEntry::make('amenities.name')
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->amenities->count() > 0)
                    ->collapsible(),
                
                Components\Section::make('Locations')
                    ->schema([
                        Components\TextEntry::make('locations.name')
                            ->badge()
                            ->separator(',')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => $record->locations->count() > 0)
                    ->collapsible(),
                
                Components\Section::make('Statistics')
                    ->schema([
                        Components\TextEntry::make('rating')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'No ratings'),
                        
                        Components\TextEntry::make('reviews_count')
                            ->label('Reviews'),
                        
                        Components\TextEntry::make('views_count')
                            ->label('Views')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        
                        Components\TextEntry::make('leads_count')
                            ->label('Leads')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        
                        Components\TextEntry::make('saves_count')
                            ->label('Saves')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        
                        Components\TextEntry::make('products_count')
                            ->label('Products')
                            ->state(fn ($record) => $record->products()->count()),
                        
                        Components\TextEntry::make('managers_count')
                            ->label('Managers')
                            ->state(fn ($record) => $record->activeManagers()->count()),
                    ])
                    ->columns(4),
                
                Components\Section::make('Gallery')
                    ->schema([
                        Components\ImageEntry::make('gallery')
                            ->label('Branch Photos')
                            ->visible(fn ($record) => $record->gallery && count($record->gallery) > 0),
                    ])
                    ->visible(fn ($record) => $record->gallery && count($record->gallery) > 0)
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime(),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}