<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/Pages/ViewBranch.php
// View branch details with relation managers
// ============================================

namespace App\Filament\Business\Resources\BranchResource\Pages;

use App\Filament\Business\Resources\BranchResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewBranch extends ViewRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Branch Overview')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Parent Business')
                            ->badge()
                            ->color('info')
                            ->url(fn ($record) => route('filament.business.resources.businesses.view', $record->business)),
                        
                        Components\TextEntry::make('branch_title')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\IconEntry::make('is_main_branch')
                            ->boolean()
                            ->label('Main Branch'),
                        
                        Components\TextEntry::make('branch_description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Location')
                    ->schema([
                        Components\TextEntry::make('address')
                            ->icon('heroicon-o-map-pin'),
                        
                        Components\TextEntry::make('city')
                            ->icon('heroicon-o-building-office'),
                        
                        Components\TextEntry::make('area'),
                        
                        Components\TextEntry::make('state')
                            ->icon('heroicon-o-globe-alt'),
                        
                        Components\TextEntry::make('latitude')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('longitude')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('nearby_landmarks')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->copyable(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Performance Statistics')
                    ->schema([
                        Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('reviews_count')
                            ->label('Reviews')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('views_count')
                            ->label('Total Views')
                            ->badge()
                            ->color('success'),
                        
                        Components\TextEntry::make('leads_count')
                            ->label('Leads')
                            ->badge()
                            ->color('warning'),
                        
                        Components\TextEntry::make('saves_count')
                            ->label('Saves')
                            ->badge()
                            ->color('primary'),
                    ])
                    ->columns(5),
                
                Components\Section::make('Features')
                    ->schema([
                        Components\TextEntry::make('unique_features')
                            ->badge()
                            ->separator(','),
                        
                        Components\TextEntry::make('amenities.name')
                            ->label('Amenities')
                            ->badge()
                            ->separator(',')
                            ->color('success'),
                        
                        Components\TextEntry::make('paymentMethods.name')
                            ->label('Payment Methods')
                            ->badge()
                            ->separator(',')
                            ->color('info'),
                    ])
                    ->columns(1)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('SEO Settings')
                    ->schema([
                        Components\TextEntry::make('canonical_strategy')
                            ->badge(),
                        
                        Components\TextEntry::make('meta_title'),
                        
                        Components\TextEntry::make('meta_description'),
                        
                        Components\TextEntry::make('content_similarity_score')
                            ->suffix('%')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\IconEntry::make('has_unique_content')
                            ->boolean()
                            ->label('Has Unique Content'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Media Gallery')
                    ->schema([
                        Components\ImageEntry::make('gallery')
                            ->columnSpanFull()
                            ->limit(10),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Status')
                    ->schema([
                        Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        
                        Components\TextEntry::make('created_at')
                            ->dateTime(),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}