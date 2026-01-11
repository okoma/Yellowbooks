<?php
// ============================================
// VIEW REVIEW PAGE
// app/Filament/Business/Resources/ReviewResource/Pages/ViewReview.php
// ============================================

namespace App\Filament\Business\Resources\ReviewResource\Pages;

use App\Filament\Business\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ViewReview extends ViewRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('reply')
                ->label('Reply to Review')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('primary')
                ->form([
                    Forms\Components\Textarea::make('reply')
                        ->label('Your Reply')
                        ->required()
                        ->rows(5)
                        ->maxLength(1000)
                        ->helperText('This will be visible to all customers'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'reply' => $data['reply'],
                        'replied_at' => now(),
                        'replied_by' => Auth::id(),
                    ]);
                    
                    Notification::make()
                        ->title('Reply posted successfully')
                        ->success()
                        ->send();
                })
                ->visible(fn () => empty($this->record->reply)),
            
            Actions\Action::make('edit_reply')
                ->label('Edit Reply')
                ->icon('heroicon-o-pencil')
                ->color('warning')
                ->fillForm(fn () => ['reply' => $this->record->reply])
                ->form([
                    Forms\Components\Textarea::make('reply')
                        ->label('Edit Your Reply')
                        ->required()
                        ->rows(5)
                        ->maxLength(1000),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'reply' => $data['reply'],
                        'replied_at' => now(),
                    ]);
                    
                    Notification::make()
                        ->title('Reply updated successfully')
                        ->success()
                        ->send();
                })
                ->visible(fn () => !empty($this->record->reply)),
        ];
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Review Location')
                    ->schema([
                        Components\TextEntry::make('business_info')
                            ->label('Business')
                            ->getStateUsing(function ($record) {
                                if ($record->reviewable_type === 'App\Models\Business') {
                                    return $record->reviewable->business_name;
                                }
                                return $record->reviewable->business->business_name;
                            })
                            ->badge()
                            ->color('info'),
                        
                        Components\TextEntry::make('branch_info')
                            ->label('Branch')
                            ->getStateUsing(function ($record) {
                                if ($record->reviewable_type === 'App\Models\BusinessBranch') {
                                    return $record->reviewable->branch_title;
                                }
                                return 'All Locations';
                            })
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Customer Review')
                    ->schema([
                        Components\TextEntry::make('user.name')
                            ->label('Customer Name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('user.email')
                            ->label('Customer Email')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),
                        
                        Components\TextEntry::make('rating')
                            ->label('Rating')
                            ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                            ->size('xl'),
                        
                        Components\IconEntry::make('is_verified_purchase')
                            ->boolean()
                            ->label('Verified Purchase')
                            ->size(Components\IconEntry\IconEntrySize::Large),
                        
                        Components\TextEntry::make('comment')
                            ->label('Review Comment')
                            ->columnSpanFull(),
                        
                        Components\ImageEntry::make('photos')
                            ->label('Customer Photos')
                            ->columnSpanFull()
                            ->limit(10)
                            ->visible(fn ($state) => !empty($state)),
                    ])
                    ->columns(2),
                
                Components\Section::make('Your Response')
                    ->schema([
                        Components\TextEntry::make('reply')
                            ->label('Your Reply')
                            ->columnSpanFull()
                            ->visible(fn ($state) => !empty($state))
                            ->placeholder('No reply yet'),
                        
                        Components\TextEntry::make('replied_at')
                            ->dateTime()
                            ->since()
                            ->label('Replied')
                            ->visible(fn ($state) => !empty($state)),
                        
                        Components\TextEntry::make('repliedByUser.name')
                            ->label('Replied By')
                            ->visible(fn ($record) => !empty($record->reply)),
                    ])
                    ->columns(2),
                
                Components\Section::make('Review Status')
                    ->schema([
                        Components\IconEntry::make('is_approved')
                            ->boolean()
                            ->label('Approved')
                            ->size(Components\IconEntry\IconEntrySize::Large),
                        
                        Components\TextEntry::make('published_at')
                            ->dateTime()
                            ->since()
                            ->label('Published'),
                        
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->since()
                            ->label('Submitted'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->since()
                            ->label('Last Updated'),
                    ])
                    ->columns(4),
            ]);
    }
}