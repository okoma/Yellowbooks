<?php

// ============================================
// app/Filament/Admin/Resources/BusinessResource/RelationManagers/ReviewsRelationManager.php
// Location: app/Filament/Admin/Resources/BusinessResource/RelationManagers/ReviewsRelationManager.php
// Panel: Admin Panel
// Access: Admins, Moderators
// Purpose: View and moderate customer reviews for standalone businesses
// ============================================

namespace App\Filament\Admin\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewsRelationManager extends RelationManager
{
    protected static string $relationship = 'reviews';
    protected static ?string $title = 'Customer Reviews';
    protected static ?string $icon = 'heroicon-o-star';
    
    // 💡 OPTIONAL: Add this to show context
    protected static ?string $recordTitleAttribute = 'comment';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Review Details')
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Customer')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\Select::make('rating')
                        ->options([
                            5 => '⭐⭐⭐⭐⭐ (5 stars)',
                            4 => '⭐⭐⭐⭐ (4 stars)',
                            3 => '⭐⭐⭐ (3 stars)',
                            2 => '⭐⭐ (2 stars)',
                            1 => '⭐ (1 star)',
                        ])
                        ->required()
                        ->native(false),
                    
                    Forms\Components\Textarea::make('comment')
                        ->rows(4)
                        ->maxLength(2000)
                        ->columnSpanFull(),
                    
                    Forms\Components\FileUpload::make('photos')
                        ->image()
                        ->multiple()
                        ->directory('review-photos')
                        ->maxSize(5120)
                        ->maxFiles(5)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Moderation')
                ->schema([
                    Forms\Components\Toggle::make('is_approved')
                        ->label('Approved')
                        ->helperText('Show this review publicly'),
                    
                    Forms\Components\Toggle::make('is_verified_purchase')
                        ->label('Verified Purchase')
                        ->helperText('Customer actually used this business'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Business Reply')
                ->schema([
                    Forms\Components\Textarea::make('reply')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Business owner response to this review')
                        ->columnSpanFull(),
                    
                    Forms\Components\DateTimePicker::make('replied_at')
                        ->disabled()
                        ->visible(fn ($get) => filled($get('reply'))),
                ])
                ->collapsible(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
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
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\IconColumn::make('is_verified_purchase')
                    ->boolean()
                    ->label('Verified')
                    ->toggleable(),
                
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
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('rating')
                    ->options([
                        5 => '5 Stars',
                        4 => '4 Stars',
                        3 => '3 Stars',
                        2 => '2 Stars',
                        1 => '1 Star',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_approved')
                    ->label('Approved Status'),
                
                Tables\Filters\TernaryFilter::make('is_verified_purchase')
                    ->label('Verified Purchase'),
                
                Tables\Filters\Filter::make('has_reply')
                    ->label('Has Reply')
                    ->query(fn ($query) => $query->whereNotNull('reply')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(fn ($record) => $record->update(['is_approved' => true, 'published_at' => now()]))
                    ->visible(fn ($record) => !$record->is_approved),
                
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn ($record) => $record->update(['is_approved' => false]))
                    ->visible(fn ($record) => $record->is_approved),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_approved' => true, 'published_at' => now()])),
                    
                    Tables\Actions\BulkAction::make('reject_selected')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_approved' => false])),
                ]),
            ]);
    }
}