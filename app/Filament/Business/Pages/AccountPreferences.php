<?php
// ============================================
// app/Filament/Business/Pages/AccountPreferences.php
// Account Preferences - DATABASE VERSION (Recommended)
// Stores preferences in user_preferences table
// ============================================

namespace App\Filament\Business\Pages;

use App\Models\UserPreference;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class AccountPreferences extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Preferences';

    protected static ?string $navigationGroup = 'Account';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.business.pages.account-preferences';

    public ?array $data = [];

    public function mount(): void
    {
        $this->fillForm();
    }

    protected function fillForm(): void
    {
        $user = auth()->user();
        
        // Get or create user preferences
        $preferences = UserPreference::getForUser($user->id);

        $this->data = [
            // Notification Preferences
            'email_notifications' => $preferences->email_notifications,
            'notify_new_leads' => $preferences->notify_new_leads,
            'notify_new_reviews' => $preferences->notify_new_reviews,
            'notify_review_replies' => $preferences->notify_review_replies,
            'notify_verifications' => $preferences->notify_verifications,
            'notify_premium_expiring' => $preferences->notify_premium_expiring,
            'notify_campaign_updates' => $preferences->notify_campaign_updates,
            
            // Display Preferences
            'theme' => $preferences->theme,
            'language' => $preferences->language,
            'timezone' => $preferences->timezone,
            'date_format' => $preferences->date_format,
            'time_format' => $preferences->time_format,
            
            // Privacy Preferences
            'profile_visibility' => $preferences->profile_visibility,
            'show_email' => $preferences->show_email,
            'show_phone' => $preferences->show_phone,
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Preferences')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Notifications')
                            ->icon('heroicon-o-bell')
                            ->schema([
                                Forms\Components\Section::make('Email Notifications')
                                    ->description('Manage your email notification preferences.')
                                    ->schema([
                                        Forms\Components\Toggle::make('email_notifications')
                                            ->label('Enable Email Notifications')
                                            ->helperText('Receive notifications via email')
                                            ->live(),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('notify_new_leads')
                                                    ->label('New Leads')
                                                    ->helperText('When someone submits a lead'),

                                                Forms\Components\Toggle::make('notify_new_reviews')
                                                    ->label('New Reviews')
                                                    ->helperText('When you receive a new review'),

                                                Forms\Components\Toggle::make('notify_review_replies')
                                                    ->label('Review Replies')
                                                    ->helperText('When customers reply to your responses'),

                                                Forms\Components\Toggle::make('notify_verifications')
                                                    ->label('Verification Updates')
                                                    ->helperText('Status updates on verification requests'),

                                                Forms\Components\Toggle::make('notify_premium_expiring')
                                                    ->label('Premium Expiring')
                                                    ->helperText('When your premium subscription is expiring'),

                                                Forms\Components\Toggle::make('notify_campaign_updates')
                                                    ->label('Campaign Updates')
                                                    ->helperText('Updates about your ad campaigns'),
                                            ])
                                            ->visible(fn (Forms\Get $get) => $get('email_notifications')),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Display')
                            ->icon('heroicon-o-computer-desktop')
                            ->schema([
                                Forms\Components\Section::make('Display Settings')
                                    ->description('Customize how the dashboard looks and feels.')
                                    ->schema([
                                        Forms\Components\Select::make('theme')
                                            ->options([
                                                'light' => 'Light',
                                                'dark' => 'Dark',
                                                'system' => 'System Default',
                                            ])
                                            ->default('system')
                                            ->helperText('Choose your preferred theme'),

                                        Forms\Components\Select::make('language')
                                            ->options([
                                                'en' => 'English',
                                                'fr' => 'French',
                                                'es' => 'Spanish',
                                            ])
                                            ->default('en')
                                            ->helperText('Select your language'),

                                        Forms\Components\Select::make('timezone')
                                            ->options([
                                                'Africa/Lagos' => 'Lagos (GMT+1)',
                                                'UTC' => 'UTC',
                                                'America/New_York' => 'New York (EST)',
                                                'Europe/London' => 'London (GMT)',
                                            ])
                                            ->searchable()
                                            ->default('Africa/Lagos')
                                            ->helperText('Your local timezone'),

                                        Forms\Components\Select::make('date_format')
                                            ->options([
                                                'M j, Y' => now()->format('M j, Y'),
                                                'd/m/Y' => now()->format('d/m/Y'),
                                                'Y-m-d' => now()->format('Y-m-d'),
                                                'F j, Y' => now()->format('F j, Y'),
                                            ])
                                            ->default('M j, Y')
                                            ->helperText('How dates are displayed'),

                                        Forms\Components\Select::make('time_format')
                                            ->options([
                                                '12h' => '12-hour (e.g., 2:30 PM)',
                                                '24h' => '24-hour (e.g., 14:30)',
                                            ])
                                            ->default('12h')
                                            ->helperText('Time display format'),
                                    ])
                                    ->columns(2),
                            ]),

                        Forms\Components\Tabs\Tab::make('Privacy')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Forms\Components\Section::make('Privacy Settings')
                                    ->description('Control who can see your information.')
                                    ->schema([
                                        Forms\Components\Select::make('profile_visibility')
                                            ->options([
                                                'public' => 'Public - Anyone can see',
                                                'registered' => 'Registered Users Only',
                                                'private' => 'Private - Only You',
                                            ])
                                            ->default('public')
                                            ->helperText('Who can view your profile'),

                                        Forms\Components\Toggle::make('show_email')
                                            ->label('Show Email on Profile')
                                            ->helperText('Display your email publicly'),

                                        Forms\Components\Toggle::make('show_phone')
                                            ->label('Show Phone on Profile')
                                            ->helperText('Display your phone number publicly'),
                                    ])
                                    ->columns(1),
                            ]),
                    ])
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $user = auth()->user();
        
        // Update or create preferences
        UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        Notification::make()
            ->success()
            ->title('Preferences Saved')
            ->body('Your preferences have been updated successfully.')
            ->send();

        // Refresh the form
        $this->fillForm();
    }
}