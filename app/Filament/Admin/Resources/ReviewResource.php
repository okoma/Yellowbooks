<?php

// ============================================
// app/Filament/Admin/Resources/ReviewResource.php
// Location: app/Filament/Admin/Resources/ReviewResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: System-wide review management with polymorphic support
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReviewResource\Pages;
use App\Models\Review;
use App\Models\Business;
use App\Models\BusinessBranch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $navigationLabel = 'Reviews';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Review Details')
                ->schema([
                    Forms\Components\Select::make('reviewable_type')
                        ->label('Review For')
                        ->options([
                            BusinessBranch::class => 'Business Branch',
                            Business::class => 'Business (Main)',
                        ])
                        ->required()
                        ->default(BusinessBranch::class)
                        ->live()
                        ->disabled(fn ($context) => $context !== 'create')
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('reviewable_id', null)),
                    
                    Forms\Components\Select::make('reviewable_id')
                        ->label(fn (Forms\Get $get) => 
                            $get('reviewable_type') === Business::class 
                                ? 'Select Business' 
                                : 'Select Business Branch'
                        )
                        ->options(function (Forms\Get $get) {
                            $type = $get('reviewable_type');
                            
                            if ($type === Business::class) {
                                return Business::query()
                                    ->pluck('business_name', 'id');
                            }
                            
                            if ($type === BusinessBranch::class) {
                                return BusinessBranch::query()
                                    ->with('business')
                                    ->get()
                                    ->mapWithKeys(fn ($branch) => [
                                        $branch->id => "{$branch->branch_title}"
                                    ]);
                            }
                            
                            return [];
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create')
                        ->live()
                        ->helperText(fn (Forms\Get $get) => 
                            $get('reviewable_type') === Business::class
                                ? 'Select the business this review is for'
                                : 'Select the specific branch this review is for'
                        ),
                    
                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\Select::make('rating')
                        ->options([
                            5 => '⭐⭐⭐⭐⭐ (5 stars - Excellent)',
                            4 => '⭐⭐⭐⭐ (4 stars - Very Good)',
                            3 => '⭐⭐⭐ (3 stars - Good)',
                            2 => '⭐⭐ (2 stars - Fair)',
                            1 => '⭐ (1 star - Poor)',
                        ])
                        ->required()
                        ->native(false),
                    
                    Forms\Components\Textarea::make('comment')
                        ->label('Review Comment')
                        ->rows(4)
                        ->maxLength(2000)
                        ->helperText('Customer review text')
                        ->columnSpanFull(),
                    
                    Forms\Components\FileUpload::make('photos')
                        ->label('Review Photos')
                        ->image()
                        ->multiple()
                        ->directory('review-photos')
                        ->maxSize(5120)
                        ->maxFiles(5)
                        ->imageEditor()
                        ->helperText('Customer uploaded photos (Max: 5)')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Moderation')
                ->schema([
                    Forms\Components\Toggle::make('is_approved')
                        ->label('Approved')
                        ->helperText('Show this review publicly')
                        ->default(false),
                    
                    Forms\Components\Toggle::make('is_verified_purchase')
                        ->label('Verified Purchase')
                        ->helperText('Customer actually used this business')
                        ->default(false),
                    
                    Forms\Components\DateTimePicker::make('published_at')
                        ->label('Published At')
                        ->native(false)
                        ->visible(fn (Forms\Get $get) => $get('is_approved')),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Business Reply')
                ->schema([
                    Forms\Components\Textarea::make('reply')
                        ->label('Business Owner Reply')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Business response to this review')
                        ->columnSpanFull(),
                    
                    Forms\Components\DateTimePicker::make('replied_at')
                        ->label('Replied At')
                        ->native(false)
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => filled($get('reply'))),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reviewable_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => 
                        $state === BusinessBranch::class ? 'Branch' : 'Business'
                    )
                    ->badge()
                    ->color(fn ($state) => 
                        $state === BusinessBranch::class ? 'info' : 'success'
                    )
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviewable.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        if ($record->isForBusiness()) {
                            return $record->reviewable->business_name;
                        }
                        return $record->reviewable->business->business_name ?? 'N/A';
                    })
                    ->description(fn ($record) => 
                        $record->isForBranch() ? $record->reviewable->branch_title : 'General Review'
                    ),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                    ->sortable()
                    ->description(fn ($record) => "{$record->rating}/5 stars"),
                
                Tables\Columns\TextColumn::make('comment')
                    ->limit(50)
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                
                Tables\Columns\ImageColumn::make('photos')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_verified_purchase')
                    ->boolean()
                    ->label('Verified')
                    ->tooltip('Verified Purchase')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('reply')
                    ->label('Replied')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->reply))
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Posted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('reviewable_type')
                    ->label('Review Type')
                    ->options([
                        BusinessBranch::class => 'Branch Reviews',
                        Business::class => 'Business Reviews',
                    ]),
                
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approval Status')
                    ->placeholder('All reviews')
                    ->trueLabel('Approved only')
                    ->falseLabel('Not approved'),
                
                Tables\Filters\TernaryFilter::make('is_verified_purchase')
                    ->label('Verified Purchase')
                    ->placeholder('All reviews')
                    ->trueLabel('Verified only')
                    ->falseLabel('Not verified'),
                
                Tables\Filters\Filter::make('has_reply')
                    ->label('Has Reply')
                    ->query(fn ($query) => $query->whereNotNull('reply')),
                
                Tables\Filters\Filter::make('has_photos')
                    ->label('Has Photos')
                    ->query(fn ($query) => $query->whereNotNull('photos')),
                
                Tables\Filters\Filter::make('low_ratings')
                    ->label('Low Ratings (1-2 stars)')
                    ->query(fn ($query) => $query->whereIn('rating', [1, 2])),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('posted_from')
                            ->label('Posted from'),
                        Forms\Components\DatePicker::make('posted_until')
                            ->label('Posted until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['posted_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['posted_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Review $record) {
                            $record->update([
                                'is_approved' => true,
                                'published_at' => now(),
                            ]);
                            
                            // Update stats for the reviewable entity
                            $record->reviewable?->updateAggregateStats();
                            
                            Notification::make()
                                ->success()
                                ->title('Review Approved')
                                ->send();
                        })
                        ->visible(fn (Review $record) => !$record->is_approved),
                    
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Review $record) {
                            $record->update(['is_approved' => false]);
                            
                            // Update stats for the reviewable entity
                            $record->reviewable?->updateAggregateStats();
                            
                            Notification::make()
                                ->warning()
                                ->title('Review Rejected')
                                ->send();
                        })
                        ->visible(fn (Review $record) => $record->is_approved),
                    
                    Tables\Actions\Action::make('mark_verified')
                        ->label('Mark Verified')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Review $record) {
                            $record->update(['is_verified_purchase' => true]);
                            
                            Notification::make()
                                ->success()
                                ->title('Marked as Verified')
                                ->send();
                        })
                        ->visible(fn (Review $record) => !$record->is_verified_purchase),
                    
                    Tables\Actions\Action::make('remove_verified')
                        ->label('Remove Verified')
                        ->icon('heroicon-o-x-mark')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (Review $record) {
                            $record->update(['is_verified_purchase' => false]);
                            
                            Notification::make()
                                ->warning()
                                ->title('Verification Removed')
                                ->send();
                        })
                        ->visible(fn (Review $record) => $record->is_verified_purchase),
                    
                    Tables\Actions\DeleteAction::make()
                        ->after(function (Review $record) {
                            $record->reviewable?->updateAggregateStats();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function ($records) {
                            // Update stats for all affected entities
                            $records->each(function ($record) {
                                $record->reviewable?->updateAggregateStats();
                            });
                        }),
                    
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'is_approved' => true,
                                'published_at' => now(),
                            ]);
                            
                            // Update stats for all affected entities
                            $records->each(function ($record) {
                                $record->reviewable?->updateAggregateStats();
                            });
                            
                            Notification::make()
                                ->success()
                                ->title('Reviews Approved')
                                ->body(count($records) . ' reviews approved successfully.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_approved' => false]);
                            
                            // Update stats for all affected entities
                            $records->each(function ($record) {
                                $record->reviewable?->updateAggregateStats();
                            });
                            
                            Notification::make()
                                ->warning()
                                ->title('Reviews Rejected')
                                ->body(count($records) . ' reviews rejected.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_verified')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_verified_purchase' => true]);
                            
                            Notification::make()
                                ->success()
                                ->title('Marked as Verified')
                                ->body(count($records) . ' reviews marked as verified.')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Review Details')
                    ->schema([
                        Components\TextEntry::make('reviewable_type')
                            ->label('Review Type')
                            ->formatStateUsing(fn ($state) => 
                                $state === BusinessBranch::class ? 'Business Branch' : 'Business (General)'
                            )
                            ->badge()
                            ->color(fn ($state) => 
                                $state === BusinessBranch::class ? 'info' : 'success'
                            ),
                        
                        Components\TextEntry::make('business_name')
                            ->label('Business')
                            ->getStateUsing(function ($record) {
                                if ($record->isForBusiness()) {
                                    return $record->reviewable->business_name;
                                }
                                return $record->reviewable->business->business_name ?? 'N/A';
                            })
                            ->url(function ($record) {
                                $business = $record->getParentBusiness();
                                return $business 
                                    ? route('filament.admin.resources.businesses.view', $business)
                                    : null;
                            })
                            ->color('primary'),
                        
                        Components\TextEntry::make('branch_title')
                            ->label('Branch')
                            ->getStateUsing(fn ($record) => 
                                $record->isForBranch() ? $record->reviewable->branch_title : 'N/A'
                            )
                            ->url(function ($record) {
                                return $record->isForBranch()
                                    ? route('filament.admin.resources.business-branches.view', $record->reviewable)
                                    : null;
                            })
                            ->color('primary')
                            ->visible(fn ($record) => $record->isForBranch()),
                        
                        Components\TextEntry::make('user.name')
                            ->label('Customer')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                            ->color('primary'),
                        
                        Components\TextEntry::make('rating')
                            ->formatStateUsing(fn ($state) => str_repeat('⭐', $state) . " ({$state}/5)"),
                        
                        Components\IconEntry::make('is_verified_purchase')
                            ->boolean()
                            ->label('Verified Purchase'),
                        
                        Components\IconEntry::make('is_approved')
                            ->boolean()
                            ->label('Approved'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Review Content')
                    ->schema([
                        Components\TextEntry::make('comment')
                            ->label('Customer Comment')
                            ->columnSpanFull(),
                        
                        Components\ImageEntry::make('photos')
                            ->label('Photos')
                            ->visible(fn ($record) => $record->photos && count($record->photos) > 0)
                            ->columnSpanFull(),
                    ]),
                
                Components\Section::make('Business Reply')
                    ->schema([
                        Components\TextEntry::make('reply')
                            ->label('Owner Reply')
                            ->visible(fn ($record) => filled($record->reply))
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('replied_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->replied_at),
                    ])
                    ->visible(fn ($record) => filled($record->reply))
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Posted At'),
                        
                        Components\TextEntry::make('published_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->published_at),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $unapprovedCount = static::getModel()::where('is_approved', false)->count();
        return $unapprovedCount > 0 ? (string) $unapprovedCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $unapprovedCount = static::getModel()::where('is_approved', false)->count();
        return $unapprovedCount > 0 ? 'warning' : null;
    }
}