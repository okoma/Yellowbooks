<?php
// ============================================
// app/Filament/Business/Resources/ProductResource/Pages/ViewProduct.php
// View product details with infolist
// ============================================

namespace App\Filament\Business\Resources\ProductResource\Pages;

use App\Filament\Business\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

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
                Components\Section::make('Product Location')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->badge()
                            ->color('info')
                            ->url(fn ($record) => $record->business ? 
                                route('filament.business.resources.businesses.view', $record->business) : null),
                        
                        Components\TextEntry::make('branch.branch_title')
                            ->label('Branch')
                            ->badge()
                            ->color('success')
                            ->url(fn ($record) => $record->branch ? 
                                route('filament.business.resources.branches.view', $record->branch) : null)
                            ->visible(fn ($record) => $record->branch),
                        
                        Components\TextEntry::make('location_type')
                            ->label('Available At')
                            ->getStateUsing(fn ($record) => $record->isStandalone() ? 'All Locations' : 'Specific Branch')
                            ->badge()
                            ->color(fn ($record) => $record->isStandalone() ? 'warning' : 'primary'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Product Information')
                    ->schema([
                        Components\ImageEntry::make('image')
                            ->size(150)
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('header_title')
                            ->label('Category/Section')
                            ->badge()
                            ->color('primary'),
                        
                        Components\TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('slug')
                            ->copyable()
                            ->icon('heroicon-o-link'),
                        
                        Components\TextEntry::make('description')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Pricing Details')
                    ->schema([
                        Components\TextEntry::make('currency')
                            ->badge(),
                        
                        Components\TextEntry::make('price')
                            ->label('Original Price')
                            ->money('NGN')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('discount_type')
                            ->label('Discount Type')
                            ->badge()
                            ->color(fn ($state) => $state !== 'none' ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'none' => 'No Discount',
                                'percentage' => 'Percentage Off',
                                'fixed' => 'Fixed Amount Off',
                            }),
                        
                        Components\TextEntry::make('discount_value')
                            ->label('Discount Value')
                            ->formatStateUsing(fn ($state, $record) => 
                                $record->discount_type === 'percentage' 
                                    ? $state . '%' 
                                    : '₦' . number_format($state, 2)
                            )
                            ->visible(fn ($record) => $record->discount_type !== 'none'),
                        
                        Components\TextEntry::make('final_price')
                            ->label('Final Price')
                            ->money('NGN')
                            ->size('xl')
                            ->weight('bold')
                            ->color('success'),
                        
                        Components\TextEntry::make('savings')
                            ->label('Customer Saves')
                            ->money('NGN')
                            ->color('danger')
                            ->visible(fn ($record) => $record->hasDiscount()),
                    ])
                    ->columns(3),
                
                Components\Section::make('Availability & Status')
                    ->schema([
                        Components\IconEntry::make('is_available')
                            ->boolean()
                            ->label('Currently Available')
                            ->size(Components\IconEntry\IconEntrySize::Large),
                        
                        Components\TextEntry::make('order')
                            ->label('Display Order')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->since()
                            ->label('Created'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->since()
                            ->label('Last Updated'),
                    ])
                    ->columns(4),
                
                Components\Section::make('Quick Actions')
                    ->schema([
                        Components\TextEntry::make('quick_actions')
                            ->label('')
                            ->columnSpanFull()
                            ->getStateUsing(fn () => '')
                            ->placeholder('Use the actions in the header to edit or delete this product'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}