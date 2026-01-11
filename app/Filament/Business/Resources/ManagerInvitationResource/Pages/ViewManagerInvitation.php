<?php
// ============================================
// app/Filament/Business/Resources/ManagerInvitationResource/Pages/ViewManagerInvitation.php
// ============================================

namespace App\Filament\Business\Resources\ManagerInvitationResource\Pages;

use App\Filament\Business\Resources\ManagerInvitationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ViewManagerInvitation extends ViewRecord
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resend')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->resend();

                    Notification::make()
                        ->success()
                        ->title('Invitation Resent')
                        ->body("Invitation resent to {$this->record->email}")
                        ->send();
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\Action::make('cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => 'expired']);

                    Notification::make()
                        ->success()
                        ->title('Invitation Cancelled')
                        ->send();

                    return redirect($this->getResource()::getUrl('index'));
                })
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\EditAction::make()
                ->visible(fn () => $this->record->status === 'pending'),

            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Invitation Details')
                    ->schema([
                        Components\TextEntry::make('email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Components\TextEntry::make('branch.branch_title')
                            ->label('Branch')
                            ->icon('heroicon-o-building-storefront'),

                        Components\TextEntry::make('branch.business.business_name')
                            ->label('Business')
                            ->icon('heroicon-o-building-office'),

                        Components\TextEntry::make('position')
                            ->badge()
                            ->color('info'),

                        Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'accepted' => 'success',
                                'declined' => 'danger',
                                'expired' => 'gray',
                                default => 'gray',
                            }),
                    ])
                    ->columns(2),

                Components\Section::make('Permissions')
                    ->schema([
                        Components\ViewEntry::make('permissions')
                            ->label('')
                            ->view('filament.infolists.permissions-list'),
                    ]),

                Components\Section::make('Timeline')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->label('Sent At')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-paper-airplane'),

                        Components\TextEntry::make('expires_at')
                            ->label('Expires At')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-clock')
                            ->color(fn ($record) => $record->expires_at->isPast() ? 'danger' : 'success'),

                        Components\TextEntry::make('accepted_at')
                            ->label('Accepted At')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-check-circle')
                            ->placeholder('Not accepted yet')
                            ->visible(fn ($record) => $record->status === 'accepted'),

                        Components\TextEntry::make('inviter.name')
                            ->label('Invited By')
                            ->icon('heroicon-o-user'),
                    ])
                    ->columns(2),

                Components\Section::make('Invitation Link')
                    ->schema([
                        Components\TextEntry::make('invitation_url')
                            ->label('Invitation URL')
                            ->state(fn ($record) => route('manager.invitation.accept', ['token' => $record->invitation_token]))
                            ->copyable()
                            ->url(fn ($record) => route('manager.invitation.accept', ['token' => $record->invitation_token]))
                            ->icon('heroicon-o-link'),
                    ])
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->collapsible(),
            ]);
    }
}