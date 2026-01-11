<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource/Pages/ViewBusiness.php
// ============================================
namespace App\Filament\Admin\Resources\BusinessResource\Pages;

use App\Filament\Admin\Resources\BusinessResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewBusiness extends ViewRecord
{
    protected static string $resource = BusinessResource::class;

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
                Components\Section::make('Business Overview')
                    ->schema([
                        Components\ImageEntry::make('logo')
                            ->size(100)
                            ->circular(),
                        
                        Components\TextEntry::make('business_name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('slug')
                            ->icon('heroicon-m-link')
                            ->copyable(),
                        
                        Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Owner & Status')
                    ->schema([
                        Components\TextEntry::make('owner.name')
                            ->label('Business Owner')
                            ->url(fn ($record) => $record->owner ? route('filament.admin.resources.users.view', $record->owner) : null),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'active' => 'success',
                                'pending_review' => 'warning',
                                'suspended' => 'danger',
                                'closed' => 'gray',
                                default => 'gray',
                            }),
                        
                        Components\IconEntry::make('is_claimed')
                            ->boolean()
                            ->label('Claimed'),
                        
                        Components\TextEntry::make('claimedBy.name')
                            ->label('Claimed By')
                            ->visible(fn ($record) => $record->is_claimed),
                        
                        Components\IconEntry::make('is_verified')
                            ->boolean()
                            ->label('Verified'),
                        
                        Components\TextEntry::make('verification_level')
                            ->badge()
                            ->visible(fn ($record) => $record->is_verified),
                        
                        Components\IconEntry::make('is_premium')
                            ->boolean()
                            ->label('Premium'),
                        
                        Components\TextEntry::make('premium_until')
                            ->dateTime()
                            ->visible(fn ($record) => $record->is_premium),
                    ])
                    ->columns(4),
                
                Components\Section::make('Legal Information')
                    ->schema([
                        Components\TextEntry::make('registration_number')
                            ->label('CAC Number'),
                        
                        Components\TextEntry::make('entity_type')
                            ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                        
                        Components\TextEntry::make('years_in_business')
                            ->suffix(' years'),
                    ])
                    ->columns(3)
                    ->collapsible(),
                
                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('email')
                            ->icon('heroicon-m-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-m-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('website')
                            ->icon('heroicon-m-globe-alt')
                            ->url(fn ($state) => $state)
                            ->openUrlInNewTab(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                
                Components\Section::make('Statistics')
                    ->schema([
                        Components\TextEntry::make('branches_count')
                            ->label('Total Branches')
                            ->state(fn ($record) => $record->branches()->count()),
                        
                        Components\TextEntry::make('avg_rating')
                            ->label('Average Rating')
                            ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'No ratings'),
                        
                        Components\TextEntry::make('total_reviews')
                            ->label('Total Reviews'),
                        
                        Components\TextEntry::make('total_views')
                            ->label('Total Views')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        
                        Components\TextEntry::make('total_leads')
                            ->label('Total Leads')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                        
                        Components\TextEntry::make('total_saves')
                            ->label('Total Saves')
                            ->formatStateUsing(fn ($state) => number_format($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Categories')
                    ->schema([
                        Components\TextEntry::make('categories.name')
                            ->badge()
                            ->separator(','),
                    ]),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime(),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime(),
                        
                        Components\TextEntry::make('claimed_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->is_claimed),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}