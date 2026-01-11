<?php
// ============================================
// app/Filament/Business/Resources/BranchManagerResource/Pages/ViewBranchManager.php
// ============================================

namespace App\Filament\Business\Resources\BranchManagerResource\Pages;

use App\Filament\Business\Resources\BranchManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;

class ViewBranchManager extends ViewRecord
{
    protected static string $resource = BranchManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('deactivate')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->deactivate();

                    Notification::make()
                        ->success()
                        ->title('Manager Deactivated')
                        ->send();
                })
                ->visible(fn () => $this->record->is_active),

            Actions\Action::make('activate')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->activate();

                    Notification::make()
                        ->success()
                        ->title('Manager Activated')
                        ->send();
                })
                ->visible(fn () => !$this->record->is_active),

            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->label('Remove Manager'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Manager Information')
                    ->schema([
                        Components\ImageEntry::make('user.avatar')
                            ->label('Avatar')
                            ->circular()
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name ?? 'Manager')),

                        Components\TextEntry::make('user.name')
                            ->label('Name')
                            ->icon('heroicon-o-user'),

                        Components\TextEntry::make('user.email')
                            ->label('Email')
                            ->icon('heroicon-o-envelope')
                            ->copyable(),

                        Components\TextEntry::make('position')
                            ->badge()
                            ->color('info'),

                        Components\TextEntry::make('employee_id')
                            ->label('Employee ID')
                            ->placeholder('Not set'),

                        Components\TextEntry::make('phone')
                            ->icon('heroicon-o-phone')
                            ->placeholder('Not set'),

                        Components\TextEntry::make('whatsapp')
                            ->label('WhatsApp')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->placeholder('Not set'),
                    ])
                    ->columns(3),

                Components\Section::make('Branch Assignment')
                    ->schema([
                        Components\TextEntry::make('branch.branch_title')
                            ->label('Branch')
                            ->icon('heroicon-o-building-storefront'),

                        Components\TextEntry::make('branch.business.business_name')
                            ->label('Business')
                            ->icon('heroicon-o-building-office'),

                        Components\TextEntry::make('branch.address')
                            ->label('Branch Address')
                            ->icon('heroicon-o-map-pin'),

                        Components\IconEntry::make('is_primary')
                            ->label('Primary Manager')
                            ->boolean()
                            ->trueIcon('heroicon-o-star')
                            ->falseIcon('heroicon-o-minus')
                            ->trueColor('warning')
                            ->falseColor('gray'),

                        Components\IconEntry::make('is_active')
                            ->label('Active Status')
                            ->boolean(),

                        Components\TextEntry::make('assigned_at')
                            ->label('Assigned On')
                            ->dateTime('M j, Y g:i A')
                            ->icon('heroicon-o-calendar'),

                        Components\TextEntry::make('assignedBy.name')
                            ->label('Assigned By')
                            ->icon('heroicon-o-user-circle'),
                    ])
                    ->columns(2),

                Components\Section::make('Permissions')
                    ->schema([
                        Components\ViewEntry::make('permissions')
                            ->label('')
                            ->view('filament.infolists.permissions-list'),
                    ]),

                Components\Section::make('Recent Activity')
                    ->schema([
                        Components\ViewEntry::make('recent_activity')
                            ->label('')
                            ->view('filament.infolists.manager-activity', [
                                'managerId' => fn ($record) => $record->id,
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}