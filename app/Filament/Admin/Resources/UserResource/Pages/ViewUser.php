<?php
// ============================================
// app/Filament/Admin/Resources/UserResource/Pages/ViewUser.php
// FILAMENT V3.3 COMPATIBLE
// ============================================

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('User Details')
                    ->schema([
                        Components\TextEntry::make('name'),
                        Components\TextEntry::make('email')
                            ->copyable(),
                        Components\TextEntry::make('phone'),
                        Components\TextEntry::make('role')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state->label()),
                        Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label('Active'),
                        Components\IconEntry::make('is_banned')
                            ->boolean()
                            ->label('Banned'),
                    ])
                    ->columns(3),
                
                Components\Section::make('Statistics')
                    ->schema([
                        Components\TextEntry::make('businesses_count')
                            ->label('Owned Businesses')
                            ->state(fn ($record) => $record->businesses()->count()),
                        Components\TextEntry::make('managing_branches_count')
                            ->label('Managing Branches'),
                        Components\TextEntry::make('reviews_count')
                            ->label('Reviews Written')
                            ->state(fn ($record) => $record->reviews()->count()),
                        Components\TextEntry::make('created_at')
                            ->dateTime()
                            ->label('Joined'),
                        Components\TextEntry::make('last_login_at')
                            ->dateTime()
                            ->label('Last Login'),
                    ])
                    ->columns(3),
            ]);
    }
}