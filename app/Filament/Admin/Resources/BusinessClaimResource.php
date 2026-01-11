<?php
// ============================================
// app/Filament/Admin/Resources/BusinessClaimResource.php
// Location: app/Filament/Admin/Resources/BusinessClaimResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage business claim requests - approve/reject workflow
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessClaimResource\Pages;
use App\Models\BusinessClaim;
use App\Models\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification as FilamentNotification;

class BusinessClaimResource extends Resource
{
    protected static ?string $model = BusinessClaim::class;
    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';
    protected static ?string $navigationLabel = 'Business Claims';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Claim Information')
                ->schema([
                    Forms\Components\Select::make('business_id')
                        ->relationship('business', 'business_name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\Select::make('user_id')
                        ->label('Claimant')
                        ->relationship('user', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\TextInput::make('claimant_position')
                        ->label('Position/Title')
                        ->maxLength(255)
                        ->helperText('Claimant\'s position in the business'),
                    
                    Forms\Components\Textarea::make('claim_message')
                        ->label('Claim Message')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Why are they claiming this business?')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Verification Contact')
                ->description('Contact details for verification')
                ->schema([
                    Forms\Components\TextInput::make('verification_phone')
                        ->tel()
                        ->maxLength(20)
                        ->prefix('+234'),
                    
                    Forms\Components\TextInput::make('verification_email')
                        ->email()
                        ->maxLength(255),
                    
                    Forms\Components\Toggle::make('phone_verified')
                        ->label('Phone Verified')
                        ->helperText('Has the phone been verified?'),
                    
                    Forms\Components\Toggle::make('email_verified')
                        ->label('Email Verified')
                        ->helperText('Has the email been verified?'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Status & Review')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'under_review' => 'Under Review',
                            'approved' => 'Approved',
                            'rejected' => 'Rejected',
                        ])
                        ->required()
                        ->default('pending')
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\Select::make('reviewed_by')
                        ->label('Reviewed By')
                        ->relationship('reviewer', 'name')
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => in_array($get('status'), ['approved', 'rejected'])),
                    
                    Forms\Components\Textarea::make('rejection_reason')
                        ->label('Rejection Reason')
                        ->rows(3)
                        ->maxLength(1000)
                        ->visible(fn (Forms\Get $get) => $get('status') === 'rejected')
                        ->required(fn (Forms\Get $get) => $get('status') === 'rejected')
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('admin_notes')
                        ->label('Admin Notes')
                        ->rows(3)
                        ->maxLength(1000)
                        ->helperText('Internal notes (not visible to claimant)')
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->business->owner->name ?? 'No owner'),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Claimant')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('claimant_position')
                    ->label('Position')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('verification_phone')
                    ->label('Phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('verification_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\IconColumn::make('phone_verified')
                    ->boolean()
                    ->label('Phone ✓')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('email_verified')
                    ->boolean()
                    ->label('Email ✓')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'under_review',
                        'success' => 'approved',
                        'danger' => 'rejected',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_')))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviewer.name')
                    ->label('Reviewed By')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
                
                Tables\Columns\TextColumn::make('reviewed_at')
                    ->label('Reviewed')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Deleted At'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'under_review' => 'Under Review',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('phone_verified')
                    ->label('Phone Verified'),
                
                Tables\Filters\TernaryFilter::make('email_verified')
                    ->label('Email Verified'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('submitted_from')
                            ->label('Submitted from'),
                        Forms\Components\DatePicker::make('submitted_until')
                            ->label('Submitted until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['submitted_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['submitted_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                
                TrashedFilter::make()->label('Deleted Claims'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Business Claim')
                        ->modalDescription(fn ($record) => "Approve claim for {$record->business->business_name} by {$record->user->name}?")
                        ->action(function (BusinessClaim $record) {
                            $record->approve(auth()->id());
                            
                            // Send notification to claimant
                            Notification::claimApproved(
                                $record->user_id,
                                $record->business->business_name,
                                $record->business_id
                            );
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Claim Approved')
                                ->body("Business claim has been approved successfully.")
                                ->send();
                        })
                        ->visible(fn (BusinessClaim $record) => $record->status === 'pending' || $record->status === 'under_review'),
                    
                    Tables\Actions\Action::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->required()
                                ->label('Reason for Rejection')
                                ->helperText('Explain why this claim is being rejected')
                                ->rows(3),
                        ])
                        ->action(function (BusinessClaim $record, array $data) {
                            $record->reject(auth()->id(), $data['rejection_reason']);
                            
                            FilamentNotification::make()
                                ->danger()
                                ->title('Claim Rejected')
                                ->body("Business claim has been rejected.")
                                ->send();
                        })
                        ->visible(fn (BusinessClaim $record) => $record->status === 'pending' || $record->status === 'under_review'),
                    
                    Tables\Actions\Action::make('mark_under_review')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->action(function (BusinessClaim $record) {
                            $record->update([
                                'status' => 'under_review',
                                'reviewed_by' => auth()->id(),
                            ]);
                            
                            FilamentNotification::make()
                                ->info()
                                ->title('Status Updated')
                                ->body("Claim marked as under review.")
                                ->send();
                        })
                        ->visible(fn (BusinessClaim $record) => $record->status === 'pending'),
                    
                    Tables\Actions\Action::make('verify_phone')
                        ->icon('heroicon-o-phone')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (BusinessClaim $record) {
                            $record->update(['phone_verified' => true]);
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Phone Verified')
                                ->send();
                        })
                        ->visible(fn (BusinessClaim $record) => !$record->phone_verified),
                    
                    Tables\Actions\Action::make('verify_email')
                        ->icon('heroicon-o-envelope')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (BusinessClaim $record) {
                            $record->update(['email_verified' => true]);
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Email Verified')
                                ->send();
                        })
                        ->visible(fn (BusinessClaim $record) => !$record->email_verified),
                    
                    Tables\Actions\DeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Business Claim')
                        ->modalDescription('This will soft delete the claim. It will be hidden but can be restored later.')
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Claim deleted')
                                ->body('The business claim has been soft deleted.')
                        )
                        ->visible(fn (BusinessClaim $record) => auth()->user()->isAdmin()),
                    
                    Tables\Actions\RestoreAction::make()
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Claim restored')
                                ->body('The business claim has been restored.')
                        )
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\ForceDeleteAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Claim')
                        ->modalDescription('Are you sure? This will permanently delete the claim and cannot be undone.')
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Claim permanently deleted')
                                ->body('The business claim has been permanently removed from the database.')
                        )
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Claims deleted')
                                ->body('The selected claims have been soft deleted.')
                        )
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\RestoreBulkAction::make()
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Claims restored')
                                ->body('The selected claims have been restored.')
                        )
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Permanently Delete Claims')
                        ->modalDescription('This will permanently delete the selected claims. This action cannot be undone.')
                        ->successNotification(
                            FilamentNotification::make()
                                ->success()
                                ->title('Claims permanently deleted')
                                ->body('The selected claims have been permanently removed.')
                        )
                        ->visible(fn () => auth()->user()->isAdmin()),
                    
                    Tables\Actions\BulkAction::make('mark_under_review')
                        ->label('Mark Under Review')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'status' => 'under_review',
                                'reviewed_by' => auth()->id(),
                            ]);
                            
                            FilamentNotification::make()
                                ->success()
                                ->title('Status Updated')
                                ->body(count($records) . ' claims marked as under review.')
                                ->send();
                        }),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Claim Details')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->url(fn ($record) => route('filament.admin.resources.businesses.view', $record->business))
                            ->color('primary'),
                        
                        Components\TextEntry::make('user.name')
                            ->label('Claimant')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user))
                            ->color('primary'),
                        
                        Components\TextEntry::make('claimant_position')
                            ->label('Position/Title'),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'pending' => 'warning',
                                'under_review' => 'info',
                                'approved' => 'success',
                                'rejected' => 'danger',
                            }),
                        
                        Components\TextEntry::make('claim_message')
                            ->label('Claim Message')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Components\Section::make('Verification Contact')
                    ->schema([
                        Components\TextEntry::make('verification_phone')
                            ->label('Phone')
                            ->icon('heroicon-m-phone'),
                        
                        Components\IconEntry::make('phone_verified')
                            ->boolean()
                            ->label('Phone Verified'),
                        
                        Components\TextEntry::make('verification_email')
                            ->label('Email')
                            ->icon('heroicon-m-envelope'),
                        
                        Components\IconEntry::make('email_verified')
                            ->boolean()
                            ->label('Email Verified'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Review Information')
                    ->schema([
                        Components\TextEntry::make('reviewer.name')
                            ->label('Reviewed By')
                            ->visible(fn ($record) => $record->reviewer),
                        
                        Components\TextEntry::make('reviewed_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->reviewed_at),
                        
                        Components\TextEntry::make('approved_at')
                            ->dateTime()
                            ->visible(fn ($record) => $record->approved_at),
                        
                        Components\TextEntry::make('rejection_reason')
                            ->visible(fn ($record) => $record->status === 'rejected')
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('admin_notes')
                            ->visible(fn ($record) => $record->admin_notes)
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->reviewer || $record->status !== 'pending'),
                
                Components\Section::make('Current Business Owner')
                    ->schema([
                        Components\TextEntry::make('business.owner.name')
                            ->label('Current Owner')
                            ->url(fn ($record) => $record->business->owner ? route('filament.admin.resources.users.view', $record->business->owner) : null),
                        
                        Components\IconEntry::make('business.is_claimed')
                            ->boolean()
                            ->label('Already Claimed'),
                        
                        Components\TextEntry::make('business.claimedBy.name')
                            ->label('Claimed By')
                            ->visible(fn ($record) => $record->business->is_claimed),
                    ])
                    ->columns(3),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Submitted At'),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime()
                            ->label('Last Updated'),
                    ])
                    ->columns(2)
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
            'index' => Pages\ListBusinessClaims::route('/'),
            'create' => Pages\CreateBusinessClaim::route('/create'),
            'edit' => Pages\EditBusinessClaim::route('/{record}/edit'),
            'view' => Pages\ViewBusinessClaim::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::whereIn('status', ['pending', 'under_review'])->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingCount = static::getModel()::whereIn('status', ['pending', 'under_review'])->count();
        return $pendingCount > 0 ? 'warning' : null;
    }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}