<?php
// ============================================
// app/Filament/Business/Resources/ManagerInvitationResource.php
// Manager Invitation System - Business Owner sends invitations
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\ManagerInvitationResource\Pages;
use App\Models\ManagerInvitation;
use App\Models\BusinessBranch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class ManagerInvitationResource extends Resource
{
    protected static ?string $model = ManagerInvitation::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationLabel = 'Manager Invitations';

    protected static ?string $navigationGroup = 'Team Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Invitation Details')
                    ->schema([
                        Forms\Components\Select::make('business_branch_id')
                            ->label('Branch')
                            ->options(function () {
                                // Only show branches owned by current user
                                return BusinessBranch::whereHas('business', function ($query) {
                                    $query->where('user_id', auth()->id());
                                })
                                ->pluck('branch_title', 'id');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->helperText('Select which branch this manager will oversee'),

                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ManagerInvitation::class, 'email', ignoreRecord: true)
                            ->helperText('Manager will receive invitation at this email')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('position')
                            ->default('Branch Manager')
                            ->required()
                            ->helperText('e.g., Branch Manager, Assistant Manager, Operations Manager')
                            ->maxLength(255),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Manager Permissions')
                            ->options([
                                'can_edit_branch' => 'Edit Branch Information',
                                'can_manage_products' => 'Manage Products/Services',
                                'can_respond_to_reviews' => 'Respond to Reviews',
                                'can_view_leads' => 'View Customer Leads',
                                'can_respond_to_leads' => 'Respond to Leads',
                                'can_view_analytics' => 'View Analytics & Reports',
                                'can_access_financials' => 'Access Financial Data',
                                'can_manage_staff' => 'Manage Staff Members',
                            ])
                            ->default([
                                'can_edit_branch' => true,
                                'can_manage_products' => true,
                                'can_respond_to_reviews' => true,
                                'can_view_leads' => true,
                                'can_respond_to_leads' => true,
                                'can_view_analytics' => true,
                            ])
                            ->columns(2)
                            ->gridDirection('row')
                            ->helperText('Select permissions this manager will have'),
                    ]),

                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Placeholder::make('invitation_info')
                            ->label('Invitation Process')
                            ->content('The manager will receive an email with a unique invitation link. They will have 7 days to accept the invitation. You can resend or cancel invitations at any time.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show invitations for current user's branches
                $query->whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            })
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('branch.branch_title')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->branch->business->business_name ?? ''),

                Tables\Columns\TextColumn::make('position')
                    ->searchable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'accepted' => 'success',
                        'declined' => 'danger',
                        'expired' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->description(fn ($record) => $record->expires_at->diffForHumans())
                    ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : null),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Sent')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('accepted_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Not accepted yet'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'declined' => 'Declined',
                        'expired' => 'Expired',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('expires_soon')
                    ->label('Expiring Soon (3 days)')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('status', 'pending')
                              ->whereBetween('expires_at', [now(), now()->addDays(3)])
                    ),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    
                    Tables\Actions\Action::make('resend')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->requiresConfirmation()
                        ->action(function (ManagerInvitation $record) {
                            if ($record->status !== 'pending') {
                                Notification::make()
                                    ->danger()
                                    ->title('Cannot Resend')
                                    ->body('Only pending invitations can be resent.')
                                    ->send();
                                return;
                            }

                            $record->resend();

                            // TODO: Send email notification
                            // Mail::to($record->email)->send(new ManagerInvitationMail($record));

                            Notification::make()
                                ->success()
                                ->title('Invitation Resent')
                                ->body("Invitation resent to {$record->email}")
                                ->send();
                        })
                        ->visible(fn (ManagerInvitation $record) => $record->status === 'pending'),

                    Tables\Actions\Action::make('cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Invitation?')
                        ->modalDescription('This will cancel the invitation. The recipient will no longer be able to accept it.')
                        ->action(function (ManagerInvitation $record) {
                            $record->update(['status' => 'expired']);

                            Notification::make()
                                ->success()
                                ->title('Invitation Cancelled')
                                ->body('The invitation has been cancelled.')
                                ->send();
                        })
                        ->visible(fn (ManagerInvitation $record) => $record->status === 'pending'),

                    Tables\Actions\EditAction::make()
                        ->visible(fn (ManagerInvitation $record) => $record->status === 'pending'),

                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('cancel_selected')
                        ->label('Cancel Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->update(['status' => 'expired']));
                            
                            Notification::make()
                                ->success()
                                ->title('Invitations Cancelled')
                                ->body(count($records) . ' invitations cancelled.')
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListManagerInvitations::route('/'),
            'create' => Pages\CreateManagerInvitation::route('/create'),
            'view' => Pages\ViewManagerInvitation::route('/{record}'),
            'edit' => Pages\EditManagerInvitation::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('branch.business', function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->where('status', 'pending')
        ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}