<?php
// ============================================
// app/Filament/Business/Resources/ReviewResource.php
// Centralized review management across all businesses
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';
    
    protected static ?string $navigationLabel = 'Customer Reviews';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Details')
                    ->schema([
                        Forms\Components\Placeholder::make('reviewable_info')
                            ->label('Business/Branch')
                            ->content(function ($record) {
                                if (!$record) return 'N/A';
                                
                                if ($record->reviewable_type === 'App\Models\Business') {
                                    return $record->reviewable->business_name;
                                } else {
                                    return $record->reviewable->branch_title . ' (' . $record->reviewable->business->business_name . ')';
                                }
                            }),
                        
                        Forms\Components\Placeholder::make('user.name')
                            ->label('Customer Name'),
                        
                        Forms\Components\Placeholder::make('user.email')
                            ->label('Customer Email'),
                        
                        Forms\Components\Placeholder::make('rating')
                            ->label('Rating')
                            ->content(fn ($record) => str_repeat('⭐', $record->rating ?? 0)),
                        
                        Forms\Components\Textarea::make('comment')
                            ->label('Review Comment')
                            ->disabled()
                            ->rows(4)
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('photos')
                            ->label('Review Photos')
                            ->multiple()
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Your Response')
                    ->schema([
                        Forms\Components\Textarea::make('reply')
                            ->label('Reply to Customer')
                            ->rows(4)
                            ->maxLength(1000)
                            ->helperText('Your response will be visible to all customers')
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Status Information')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approved')
                            ->disabled()
                            ->helperText('Only admins can approve/reject reviews'),
                        
                        Forms\Components\Toggle::make('is_verified_purchase')
                            ->label('Verified Purchase')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('replied_at')
                            ->label('Replied At')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reviewable_name')
                    ->label('Business/Branch')
                    ->getStateUsing(function ($record) {
                        if ($record->reviewable_type === 'App\Models\Business') {
                            return $record->reviewable->business_name;
                        }
                        return $record->reviewable->branch_title;
                    })
                    ->searchable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('comment')
                    ->limit(50)
                    ->wrap()
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\ImageColumn::make('photos')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                
                Tables\Columns\IconColumn::make('reply')
                    ->boolean()
                    ->label('Replied')
                    ->getStateUsing(fn ($record) => !empty($record->reply)),
                
                Tables\Columns\IconColumn::make('is_verified_purchase')
                    ->boolean()
                    ->label('Verified')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Posted'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars ⭐⭐⭐⭐⭐',
                        4 => '4 Stars ⭐⭐⭐⭐',
                        3 => '3 Stars ⭐⭐⭐',
                        2 => '2 Stars ⭐⭐',
                        1 => '1 Star ⭐',
                    ]),
                
                Tables\Filters\TernaryFilter::make('reply')
                    ->label('Has Reply')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('reply'),
                        false: fn ($query) => $query->whereNull('reply'),
                    ),
                
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved Only'),
                
                Tables\Filters\TernaryFilter::make('is_verified_purchase')
                    ->label('Verified Purchase'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['created_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\Action::make('reply')
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
                        ->action(function ($record, array $data) {
                            $record->update([
                                'reply' => $data['reply'],
                                'replied_at' => now(),
                                'replied_by' => Auth::id(),
                            ]);
                            
                            Notification::make()
                                ->title('Reply posted successfully')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => empty($record->reply)),
                    
                    Tables\Actions\Action::make('edit_reply')
                        ->label('Edit Reply')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->fillForm(fn ($record) => ['reply' => $record->reply])
                        ->form([
                            Forms\Components\Textarea::make('reply')
                                ->label('Edit Your Reply')
                                ->required()
                                ->rows(5)
                                ->maxLength(1000),
                        ])
                        ->action(function ($record, array $data) {
                            $record->update([
                                'reply' => $data['reply'],
                                'replied_at' => now(),
                            ]);
                            
                            Notification::make()
                                ->title('Reply updated successfully')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !empty($record->reply)),
                    
                    Tables\Actions\Action::make('delete_reply')
                        ->label('Delete Reply')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $record->update([
                                'reply' => null,
                                'replied_at' => null,
                            ]);
                            
                            Notification::make()
                                ->title('Reply deleted')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !empty($record->reply)),
                ]),
            ])
            ->bulkActions([
                // No bulk actions - reviews should be handled individually
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'view' => Pages\ViewReview::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where(function($q) {
            $businesses = Auth::user()->businesses()->pluck('id');
            $branches = Auth::user()->businesses()->with('branches')->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
            
            $q->where(function($query) use ($businesses) {
                $query->where('reviewable_type', 'App\Models\Business')
                      ->whereIn('reviewable_id', $businesses);
            })->orWhere(function($query) use ($branches) {
                $query->where('reviewable_type', 'App\Models\BusinessBranch')
                      ->whereIn('reviewable_id', $branches);
            });
        })->whereNull('reply')->count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
    
    public static function getEloquentQuery(): Builder
    {
        $businesses = Auth::user()->businesses()->pluck('id');
        $branches = Auth::user()->businesses()->with('branches')->get()
            ->flatMap(fn($b) => $b->branches->pluck('id'));
        
        return parent::getEloquentQuery()
            ->where(function($q) use ($businesses, $branches) {
                $q->where(function($query) use ($businesses) {
                    $query->where('reviewable_type', 'App\Models\Business')
                          ->whereIn('reviewable_id', $businesses);
                })->orWhere(function($query) use ($branches) {
                    $query->where('reviewable_type', 'App\Models\BusinessBranch')
                          ->whereIn('reviewable_id', $branches);
                });
            });
    }
    
    public static function canCreate(): bool
    {
        return false; // Reviews are created by customers, not business owners
    }
}