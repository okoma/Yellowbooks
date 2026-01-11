<?php

// ============================================
// VIEW LEAD PAGE
// app/Filament/Business/Resources/LeadResource/Pages/ViewLead.php
// ============================================

namespace App\Filament\Business\Resources\LeadResource\Pages;

use App\Filament\Business\Resources\LeadResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;

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
                Components\Section::make('Lead Details')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('branch.branch_title')
                            ->label('Branch')
                            ->visible(fn ($record) => $record->branch),
                        
                        Components\TextEntry::make('client_name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),
                        
                        Components\TextEntry::make('phone')
                            ->icon('heroicon-o-phone')
                            ->copyable(),
                        
                        Components\TextEntry::make('whatsapp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->copyable(),
                        
                        Components\TextEntry::make('lead_button_text')
                            ->label('Inquiry Type')
                            ->badge(),
                    ])
                    ->columns(3),
                
                Components\Section::make('Custom Information')
                    ->schema([
                        Components\KeyValueEntry::make('custom_fields')
                            ->label('Additional Details'),
                    ])
                    ->visible(fn ($record) => !empty($record->custom_fields))
                    ->collapsible(),
                
                Components\Section::make('Status & Response')
                    ->schema([
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'new' => 'warning',
                                'contacted' => 'info',
                                'qualified' => 'primary',
                                'converted' => 'success',
                                'lost' => 'danger',
                            }),
                        
                        Components\IconEntry::make('is_replied')
                            ->boolean()
                            ->label('Replied'),
                        
                        Components\TextEntry::make('replied_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->replied_at),
                        
                        Components\TextEntry::make('notes')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('reply_message')
                            ->label('Reply Sent')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(3),
                
                Components\Section::make('Timeline')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Received')
                            ->dateTime()
                            ->since(),
                        
                        Components\TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since(),
                    ])
                    ->columns(2),
            ]);
    }
}
