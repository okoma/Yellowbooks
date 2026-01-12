<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/ViewBusiness.php
// View business details with relation managers as tabs
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Resources\Pages\ViewRecord;

class ViewBusiness extends ViewRecord
{
    protected static string $resource = BusinessResource::class;

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
                Components\Section::make('Business Overview')
                    ->schema([
                        Components\ImageEntry::make('logo')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->business_name)),
                        
                        Components\TextEntry::make('business_name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('businessType.name')
                            ->label('Business Type')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('categories.name')
                            ->badge()
                            ->separator(',')
                            ->color('success'),
                        
                        Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Location & Contact')
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
                        
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->copyable(),
                        
                        Components\TextEntry::make('website')
                            ->icon('heroicon-o-globe-alt')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                        
                        Components\TextEntry::make('nearby_landmarks')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Business Hours')
                    ->schema([
                        Components\ViewEntry::make('business_hours')
                            ->view('filament.infolists.business-hours')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn ($record) => !empty($record->business_hours))
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Business Status')
                    ->schema([
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'active' => 'success',
                                'pending_review' => 'warning',
                                'draft' => 'secondary',
                                'suspended' => 'danger',
                                default => 'gray',
                            }),
                        
                        Components\IconEntry::make('is_verified')
                            ->boolean()
                            ->label('Verified'),
                        
                        Components\TextEntry::make('verification_level')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'premium' => 'success',
                                'standard' => 'info',
                                'basic' => 'warning',
                                default => 'gray',
                            }),
                        
                        Components\IconEntry::make('is_premium')
                            ->boolean()
                            ->label('Premium'),
                        
                        Components\IconEntry::make('is_claimed')
                            ->boolean()
                            ->label('Claimed'),
                        
                        Components\TextEntry::make('claimed_at')
                            ->dateTime()
                            ->label('Claimed On'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Performance Statistics')
                    ->schema([
                        Components\TextEntry::make('avg_rating')
                            ->label('Average Rating')
                            ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('total_reviews')
                            ->label('Total Reviews')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('total_views')
                            ->label('Total Views')
                            ->badge()
                            ->color('success'),
                        
                        Components\TextEntry::make('total_leads')
                            ->label('Total Leads')
                            ->badge()
                            ->color('warning'),
                        
                        Components\TextEntry::make('total_saves')
                            ->label('Total Saves')
                            ->badge()
                            ->color('primary'),
                        
                        Components\TextEntry::make('branches_count')
                            ->label('Total Branches')
                            ->badge()
                            ->color('info')
                            ->state(fn ($record) => $record->branches()->count()),
                    ])
                    ->columns(3),
                
                Components\Section::make('Features & Amenities')
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
                        
                        Components\IconEntry::make('has_unique_content')
                            ->boolean()
                            ->label('Has Unique Content'),
                        
                        Components\TextEntry::make('content_similarity_score')
                            ->suffix('%')
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Legal Information')
                    ->schema([
                        Components\TextEntry::make('registration_number')
                            ->label('CAC/RC Number'),
                        
                        Components\TextEntry::make('entity_type'),
                        
                        Components\TextEntry::make('years_in_business')
                            ->suffix(' years'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('Media')
                    ->schema([
                        Components\ImageEntry::make('cover_photo')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\ImageEntry::make('gallery')
                            ->columnSpanFull()
                            ->limit(10)
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->visible(fn ($record) => !empty($record->cover_photo) || !empty($record->gallery))
                    ->collapsible()
                    ->collapsed(),
                
                Components\Section::make('System Information')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Created'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                        
                        Components\TextEntry::make('slug')
                            ->copyable(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
