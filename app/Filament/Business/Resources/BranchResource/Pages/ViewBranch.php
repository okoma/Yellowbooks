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
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Location')
                    ->schema([
                        Components\TextEntry::make('address')
                            ->icon('heroicon-o-map-pin'),
                        
                        Components\TextEntry::make('city')
                            ->icon('heroicon-o-building-office'),
                        
                        Components\TextEntry::make('area')
                            ->visible(fn ($state) => !empty($state)),
                        
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
                            ->copyable()
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->copyable()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => !empty($record->phone) || !empty($record->email) || !empty($record->whatsapp)),
                
                Components\Section::make('Business Hours')
                    ->schema([
                        Components\ViewEntry::make('business_hours')
                            ->view('filament.infolists.business-hours')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->business_hours))
                    ->collapsible()
                    ->collapsed(),
                
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
                            ->separator(',')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('amenities.name')
                            ->label('Amenities')
                            ->badge()
                            ->separator(',')
                            ->color('success')
                            ->visible(fn ($record) => $record->amenities()->exists()),
                        
                        Components\TextEntry::make('paymentMethods.name')
                            ->label('Payment Methods')
                            ->badge()
                            ->separator(',')
                            ->color('info')
                            ->visible(fn ($record) => $record->paymentMethods()->exists()),
                    ])
                    ->columns(1)
                    ->visible(fn ($record) => 
                        !empty($record->unique_features) || 
                        $record->amenities()->exists() || 
                        $record->paymentMethods()->exists()
                    )
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('SEO Settings')
                    ->schema([
                        Components\TextEntry::make('canonical_strategy')
                            ->badge()
                            ->color(fn ($state) => $state === 'self' ? 'success' : 'info'),
                        
                        Components\TextEntry::make('canonical_url')
                            ->visible(fn ($state) => !empty($state))
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                        
                        Components\TextEntry::make('meta_title')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('meta_description')
                            ->visible(fn ($state) => !empty($state))
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('content_similarity_score')
                            ->suffix('%')
                            ->visible(fn ($state) => !empty($state))
                            ->color(fn ($state) => $state > 70 ? 'danger' : ($state > 30 ? 'warning' : 'success')),
                        
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
                    ->visible(fn ($record) => !empty($record->gallery))
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
                        
                        Components\TextEntry::make('slug')
                            ->copyable(),
                    ])
                    ->columns(4)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
