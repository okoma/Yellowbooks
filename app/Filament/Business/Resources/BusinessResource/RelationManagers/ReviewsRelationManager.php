<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/RelationManagers/ReviewsRelationManager.php
// View and respond to customer reviews
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';
    
    protected static ?string $title = 'Customer Reviews';
    
    protected static ?string $icon = 'heroicon-o-star';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Details')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Customer Name')
                            ->disabled(),
                        
                        Forms\Components\TextInput::make('rating')
                            ->disabled()
                            ->suffix('⭐'),
                        
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
                
                Forms\Components\Section::make('Status')
                    ->schema([
                        Forms\Components\Toggle::make('is_approved')
                            ->label('Approved')
                            ->disabled()
                            ->helperText('Only admins can approve/reject reviews'),
                        
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published')
                            ->disabled(),
                        
                        Forms\Components\DateTimePicker::make('replied_at')
                            ->label('Replied')
                            ->disabled(),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('user.name')
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('comment')
                    ->limit(50)
                    ->wrap()
                    ->searchable(),
                
                Tables\Columns\ImageColumn::make('photos')
                    ->circular()
                    ->stacked()
                    ->limit(3),
                
                Tables\Columns\IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                
                Tables\Columns\IconColumn::make('reply')
                    ->boolean()
                    ->label('Replied')
                    ->getStateUsing(fn ($record) => !empty($record->reply)),
                
                Tables\Columns\IconColumn::make('is_verified_purchase')
                    ->boolean()
                    ->label('Verified'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Posted'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ]),
                
                Tables\Filters\TernaryFilter::make('reply')
                    ->label('Has Reply')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('reply'),
                        false: fn ($query) => $query->whereNull('reply'),
                    ),
                
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved'),
                
                Tables\Filters\TernaryFilter::make('is_verified_purchase')
                    ->label('Verified Purchase'),
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
                // No bulk actions for reviews - each should be handled individually
            ]);
    }
    
    public function isReadOnly(): bool
    {
        return false; // Allow replies
    }
}