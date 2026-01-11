<?php
// app/Filament/Admin/Resources/LocationResource/Pages/ListLocations.php
namespace App\Filament\Admin\Resources\LocationResource\Pages;

use App\Filament\Admin\Resources\LocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListLocations extends ListRecords
{
    protected static string $resource = LocationResource::class;

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
                ->badge(fn () => $this->getModel()::count()),
            
            'states' => Tab::make('States')
                ->badge(fn () => $this->getModel()::where('type', 'state')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'state')),
            
            'cities' => Tab::make('Cities')
                ->badge(fn () => $this->getModel()::where('type', 'city')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'city')),
            
            'areas' => Tab::make('Areas')
                ->badge(fn () => $this->getModel()::where('type', 'area')->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->where('type', 'area')),
        ];
    }
}