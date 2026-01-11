<?php
// ============================================
// app/Filament/Business/Resources/AdPackageResource/Pages/ViewAdPackage.php
// ============================================

namespace App\Filament\Business\Resources\AdPackageResource\Pages;

use App\Filament\Business\Resources\AdPackageResource;
use App\Models\Business;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewAdPackage extends ViewRecord
{
    protected static string $resource = AdPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('purchase')
                ->label('Purchase This Package')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->size('lg')
                ->form([
                    Forms\Components\Select::make('business_id')
                        ->label('Select Business')
                        ->options(function () {
                            return Business::where('user_id', auth()->id())
                                ->pluck('business_name', 'id');
                        })
                        ->required()
                        ->searchable()
                        ->preload()
                        ->helperText('Choose which business to advertise'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date')
                        ->default(now())
                        ->minDate(now())
                        ->required()
                        ->helperText('When should the campaign begin?'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Campaign Notes (Optional)')
                        ->rows(2)
                        ->maxLength(500),
                ])
                ->action(function (array $data) {
                    try {
                        $record = $this->getRecord();
                        
                        // Create the campaign
                        $campaign = $record->createCampaign(
                            $data['business_id'],
                            auth()->id(),
                            [
                                'starts_at' => $data['start_date'],
                                'ends_at' => now()->parse($data['start_date'])->addDays($record->duration_days),
                                'description' => $data['notes'] ?? null,
                            ]
                        );

                        // TODO: Integrate with payment gateway here

                        Notification::make()
                            ->success()
                            ->title('Campaign Created!')
                            ->body('Your campaign has been created. Complete payment to activate it.')
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->label('View Campaign')
                                    ->url(route('filament.business.resources.ad-campaigns.view', $campaign)),
                            ])
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('Failed to create campaign: ' . $e->getMessage())
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Purchase Ad Package')
                ->modalDescription(fn () => 'You are about to purchase the "' . $this->getRecord()->name . '" package for ₦' . number_format($this->getRecord()->price, 2))
                ->modalSubmitActionLabel('Create Campaign'),

            Actions\Action::make('back')
                ->label('Back to Packages')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.business.resources.ad-packages.index'))
                ->color('gray'),
        ];
    }
}