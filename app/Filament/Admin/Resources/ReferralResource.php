<?php
// ============================================
// app/Filament/Admin/Resources/ReferralResource.php
// Location: app/Filament/Admin/Resources/ReferralResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Track referral program (who referred who, rewards paid)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReferralResource\Pages;
use App\Models\Referral;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ReferralResource extends Resource
{
    protected static ?string $model = Referral::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Referrals';
    protected static ?string $navigationGroup = 'User Management';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Referral Information')
                ->schema([
                    Forms\Components\Select::make('referrer_id')
                        ->label('Referrer (Person who invited)')
                        ->relationship('referrer', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\Select::make('referred_id')
                        ->label('Referred User (Person invited)')
                        ->relationship('referred', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->disabled(fn ($context) => $context !== 'create'),
                    
                    Forms\Components\TextInput::make('referral_code')
                        ->label('Referral Code Used')
                        ->maxLength(255)
                        ->disabled()
                        ->helperText('Code used to track referral'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Rewards')
                ->schema([
                    Forms\Components\TextInput::make('referrer_reward')
                        ->label('Referrer Cash Reward')
                        ->numeric()
                        ->prefix('₦')
                        ->step(0.01)
                        ->default(0)
                        ->helperText('Cash reward for person who referred'),
                    
                    Forms\Components\TextInput::make('referrer_credits')
                        ->label('Referrer Ad Credits')
                        ->numeric()
                        ->default(0)
                        ->helperText('Ad credits for person who referred'),
                    
                    Forms\Components\TextInput::make('referred_reward')
                        ->label('Referred User Cash Reward')
                        ->numeric()
                        ->prefix('₦')
                        ->step(0.01)
                        ->default(0)
                        ->helperText('Welcome bonus for new user'),
                    
                    Forms\Components\TextInput::make('referred_credits')
                        ->label('Referred User Ad Credits')
                        ->numeric()
                        ->default(0)
                        ->helperText('Welcome ad credits for new user'),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'pending' => 'Pending',
                            'completed' => 'Completed',
                            'expired' => 'Expired',
                            'cancelled' => 'Cancelled',
                        ])
                        ->required()
                        ->default('pending')
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\Toggle::make('rewards_paid')
                        ->label('Rewards Paid')
                        ->disabled()
                        ->helperText('Automatically set when rewards are processed'),
                    
                    Forms\Components\DateTimePicker::make('completed_at')
                        ->label('Completed At')
                        ->disabled()
                        ->visible(fn (Forms\Get $get) => $get('status') === 'completed'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('referrer.name')
                    ->label('Referrer')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->referrer->email)
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->referrer)),
                
                Tables\Columns\TextColumn::make('referred.name')
                    ->label('Referred User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->referred->email)
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->referred)),
                
                Tables\Columns\TextColumn::make('referral_code')
                    ->label('Code')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('referrer_reward')
                    ->label('Referrer Reward')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn ($record) => $record->referrer_credits > 0 ? "{$record->referrer_credits} credits" : null),
                
                Tables\Columns\TextColumn::make('referred_reward')
                    ->label('Referred Reward')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn ($record) => $record->referred_credits > 0 ? "{$record->referred_credits} credits" : null),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'expired',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('rewards_paid')
                    ->boolean()
                    ->label('Paid')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Referred On')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('rewards_paid')
                    ->label('Rewards Status')
                    ->placeholder('All')
                    ->trueLabel('Paid')
                    ->falseLabel('Not Paid'),
                
                Tables\Filters\SelectFilter::make('referrer_id')
                    ->label('Referrer')
                    ->relationship('referrer', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('referred_from')
                            ->label('Referred from'),
                        Forms\Components\DatePicker::make('referred_until')
                            ->label('Referred until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['referred_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['referred_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('complete')
                        ->label('Mark as Completed')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Referral $record) {
                            $record->complete();
                            
                            Notification::make()
                                ->success()
                                ->title('Referral Completed')
                                ->body('Referral marked as completed and rewards processed.')
                                ->send();
                        })
                        ->visible(fn (Referral $record) => $record->status === 'pending'),
                    
                    Tables\Actions\Action::make('pay_rewards')
                        ->label('Pay Rewards')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Pay Referral Rewards')
                        ->modalDescription('This will credit both referrer and referred user with their rewards.')
                        ->action(function (Referral $record) {
                            if (!$record->rewards_paid) {
                                $record->payRewards();
                                
                                Notification::make()
                                    ->success()
                                    ->title('Rewards Paid')
                                    ->body('Both referrer and referred user have received their rewards.')
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('Already Paid')
                                    ->body('Rewards have already been paid for this referral.')
                                    ->send();
                            }
                        })
                        ->visible(fn (Referral $record) => $record->status === 'completed' && !$record->rewards_paid),
                    
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('complete_selected')
                        ->label('Complete Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->complete();
                            
                            Notification::make()
                                ->success()
                                ->title('Referrals Completed')
                                ->body(count($records) . ' referrals marked as completed.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('pay_rewards_selected')
                        ->label('Pay Rewards')
                        ->icon('heroicon-o-banknotes')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $paid = 0;
                            $records->each(function ($record) use (&$paid) {
                                if (!$record->rewards_paid && $record->status === 'completed') {
                                    $record->payRewards();
                                    $paid++;
                                }
                            });
                            
                            Notification::make()
                                ->success()
                                ->title('Rewards Paid')
                                ->body("{$paid} referral rewards have been processed.")
                                ->send();
                        }),
                ]),
            ])
            ->emptyStateHeading('No Referrals Yet')
            ->emptyStateDescription('Referrals will appear here when users invite others.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Referral Information')
                    ->schema([
                        Components\TextEntry::make('referrer.name')
                            ->label('Referrer (Who Invited)')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->referrer))
                            ->color('primary'),
                        
                        Components\TextEntry::make('referred.name')
                            ->label('Referred User (Who Was Invited)')
                            ->url(fn ($record) => route('filament.admin.resources.users.view', $record->referred))
                            ->color('primary'),
                        
                        Components\TextEntry::make('referral_code')
                            ->label('Referral Code')
                            ->copyable()
                            ->badge(),
                        
                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'pending' => 'warning',
                                'completed' => 'success',
                                'expired' => 'danger',
                                'cancelled' => 'gray',
                            }),
                    ])
                    ->columns(2),
                
                Components\Section::make('Referrer Rewards')
                    ->schema([
                        Components\TextEntry::make('referrer_reward')
                            ->label('Cash Reward')
                            ->money('NGN')
                            ->size('lg')
                            ->color('success'),
                        
                        Components\TextEntry::make('referrer_credits')
                            ->label('Ad Credits')
                            ->suffix(' credits')
                            ->size('lg')
                            ->color('info'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Referred User Rewards')
                    ->schema([
                        Components\TextEntry::make('referred_reward')
                            ->label('Cash Reward')
                            ->money('NGN')
                            ->size('lg')
                            ->color('success'),
                        
                        Components\TextEntry::make('referred_credits')
                            ->label('Ad Credits')
                            ->suffix(' credits')
                            ->size('lg')
                            ->color('info'),
                    ])
                    ->columns(2),
                
                Components\Section::make('Status')
                    ->schema([
                        Components\IconEntry::make('rewards_paid')
                            ->boolean()
                            ->label('Rewards Paid'),
                        
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Referred On'),
                        
                        Components\TextEntry::make('completed_at')
                            ->dateTime()
                            ->label('Completed At')
                            ->visible(fn ($record) => $record->completed_at),
                    ])
                    ->columns(3),
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
            'index' => Pages\ListReferrals::route('/'),
            'create' => Pages\CreateReferral::route('/create'),
            'edit' => Pages\EditReferral::route('/{record}/edit'),
            'view' => Pages\ViewReferral::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pendingCount = static::getModel()::where('status', 'pending')->count();
        return $pendingCount > 0 ? (string) $pendingCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}