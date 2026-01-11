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
                        
                        Components\TextEntry::make('state')
                            ->icon('heroicon-o-globe-alt'),
                        
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
                    ])
                    ->columns(3),
                
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
                            ->color('info'),
                    ])
                    ->columns(3),
                
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
                            ->columnSpanFull(),
                        
                        Components\ImageEntry::make('gallery')
                            ->columnSpanFull()
                            ->limit(10),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}