<?php
// ============================================
// app/Filament/Business/Resources/AdPackageResource.php
// Browse and purchase advertising packages
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\AdPackageResource\Pages;
use App\Models\AdPackage;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class AdPackageResource extends Resource
{
    protected static ?string $model = AdPackage::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationLabel = 'Ad Packages';

    protected static ?string $navigationGroup = 'Billing & Marketing';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        // Only show active packages to business users
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->orderBy('order')
            ->orderBy('price');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Package Details')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->disabled()
                            ->label('Package Name'),

                        Forms\Components\Textarea::make('description')
                            ->disabled()
                            ->rows(3),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->disabled()
                                    ->prefix('₦')
                                    ->numeric(),

                                Forms\Components\TextInput::make('duration_days')
                                    ->disabled()
                                    ->suffix('days')
                                    ->label('Duration'),

                                Forms\Components\TextInput::make('campaign_type')
                                    ->disabled()
                                    ->label('Campaign Type')
                                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
                            ]),
                    ]),

                Forms\Components\Section::make('Features')
                    ->schema([
                        Forms\Components\Placeholder::make('features_list')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->features) {
                                    return 'No features listed';
                                }

                                return view('filament.forms.components.features-list', [
                                    'features' => $record->features
                                ]);
                            }),
                    ]),

                Forms\Components\Section::make('Limits')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('impressions_limit')
                                    ->disabled()
                                    ->label('Max Impressions')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited'),

                                Forms\Components\TextInput::make('clicks_limit')
                                    ->disabled()
                                    ->label('Max Clicks')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->size('lg'),

                Tables\Columns\TextColumn::make('campaign_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'bump_up' => 'info',
                        'sponsored' => 'warning',
                        'featured' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('NGN')
                    ->sortable()
                    ->size('lg')
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('duration_days')
                    ->label('Duration')
                    ->suffix(' days')
                    ->sortable(),

                Tables\Columns\TextColumn::make('impressions_limit')
                    ->label('Max Impressions')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('clicks_limit')
                    ->label('Max Clicks')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Unlimited')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campaign_type')
                    ->label('Campaign Type')
                    ->options([
                        'bump_up' => 'Bump Up',
                        'sponsored' => 'Sponsored',
                        'featured' => 'Featured',
                    ]),

                Tables\Filters\TernaryFilter::make('is_popular')
                    ->label('Show Popular Only')
                    ->placeholder('All packages')
                    ->trueLabel('Popular only')
                    ->falseLabel('Non-popular only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\Action::make('purchase')
                    ->label('Purchase')
                    ->icon('heroicon-o-shopping-cart')
                    ->color('success')
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
                    ->action(function (AdPackage $record, array $data) {
                        try {
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
                            // For now, we'll mark it as unpaid

                            Notification::make()
                                ->success()
                                ->title('Campaign Created!')
                                ->body('Your campaign has been created. Complete payment to activate it.')
                                ->actions([
                                    \Filament\Notifications\Actions\Action::make('view')
                                        ->label('View Campaign')
                                        ->url(fn () => static::getUrl('../ad-campaigns/view', ['record' => $campaign])),
                                ])
                                ->send();

                            // Redirect to payment or campaign details
                            // redirect()->route('payment.process', $campaign);

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
                    ->modalDescription(fn ($record) => 'You are about to purchase the "' . $record->name . '" package for ₦' . number_format($record->price, 2))
                    ->modalSubmitActionLabel('Create Campaign'),
            ])
            ->bulkActions([
                // No bulk actions needed for business users
            ])
            ->emptyStateHeading('No Ad Packages Available')
            ->emptyStateDescription('There are currently no advertising packages available. Please check back later.')
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->defaultSort('order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdPackages::route('/'),
            'view' => Pages\ViewAdPackage::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false; // Business users cannot create packages
    }

    public static function canEdit($record): bool
    {
        return false; // Business users cannot edit packages
    }

    public static function canDelete($record): bool
    {
        return false; // Business users cannot delete packages
    }
}