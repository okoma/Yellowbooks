<?php

namespace App\Filament\Admin\Resources\ManagerInvitationResource\Pages;

use App\Filament\Admin\Resources\ManagerInvitationResource;
use App\Models\ManagerInvitation;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListManagerInvitations extends ListRecords
{
    protected static string $resource = ManagerInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(fn () => ManagerInvitation::query()->count()),

            'pending' => Tab::make('Pending')
                ->badge(fn () => ManagerInvitation::query()->where('status', 'pending')->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            'accepted' => Tab::make('Accepted')
                ->badge(fn () => ManagerInvitation::query()->where('status', 'accepted')->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'accepted')),

            'declined' => Tab::make('Declined')
                ->badge(fn () => ManagerInvitation::query()->where('status', 'declined')->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'declined')),
        ];
    }
}
