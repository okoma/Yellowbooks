<?php
// ============================================
// UNIVERSAL SocialAccountsRelationManager
// Use EXACT SAME FILE for both:
// - app/Filament/Admin/Resources/BusinessResource/RelationManagers/SocialAccountsRelationManager.php
// - app/Filament/Admin/Resources/BusinessBranchResource/RelationManagers/SocialAccountsRelationManager.php
// ============================================

namespace App\Filament\Admin\Resources\BusinessBranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;

class SocialAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'socialAccounts';
    protected static ?string $title = 'Social Accounts';
    protected static ?string $icon = 'heroicon-o-globe-alt';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\Select::make('platform')
                        ->required()
                        ->live() // Enable reactive behavior
                        ->options([
                            'facebook' => 'Facebook',
                            'instagram' => 'Instagram',
                            'twitter' => 'Twitter (X)',
                            'linkedin' => 'LinkedIn',
                            'youtube' => 'YouTube',
                            'tiktok' => 'TikTok',
                            'pinterest' => 'Pinterest',
                            'whatsapp' => 'WhatsApp Business',
                        ])
                        ->native(false)
                        ->searchable(),
                    
                    Forms\Components\TextInput::make('url')
                        ->required()
                        ->url()
                        ->maxLength(500)
                        ->prefix(fn (Get $get) => $get('platform') === 'whatsapp' ? '' : 'https://')
                        ->placeholder(function (Get $get) {
                            return match($get('platform')) {
                                'facebook' => 'facebook.com/yourbusiness',
                                'instagram' => 'instagram.com/yourbusiness',
                                'twitter' => 'twitter.com/yourbusiness or x.com/yourbusiness',
                                'linkedin' => 'linkedin.com/company/yourbusiness',
                                'youtube' => 'youtube.com/@yourbusiness or youtube.com/c/yourbusiness',
                                'tiktok' => 'tiktok.com/@yourbusiness',
                                'pinterest' => 'pinterest.com/yourbusiness',
                                'whatsapp' => '+2348012345678 or https://wa.me/2348012345678',
                                default => 'Enter social media URL'
                            };
                        })
                        ->helperText(function (Get $get) {
                            return match($get('platform')) {
                                'facebook' => 'Enter your Facebook page URL (e.g., facebook.com/yourpage)',
                                'instagram' => 'Enter your Instagram profile URL (e.g., instagram.com/yourprofile)',
                                'twitter' => 'Enter your Twitter/X profile URL (e.g., twitter.com/yourhandle or x.com/yourhandle)',
                                'linkedin' => 'Enter your LinkedIn company page URL (e.g., linkedin.com/company/yourcompany)',
                                'youtube' => 'Enter your YouTube channel URL (e.g., youtube.com/@yourchannel)',
                                'tiktok' => 'Enter your TikTok profile URL (e.g., tiktok.com/@yourprofile)',
                                'pinterest' => 'Enter your Pinterest profile URL (e.g., pinterest.com/yourprofile)',
                                'whatsapp' => 'Enter WhatsApp number (e.g., +2348012345678) or wa.me link (e.g., https://wa.me/2348012345678)',
                                default => 'Enter the full URL to your social media page'
                            };
                        })
                        ->rules(function (Get $get) {
                            $rules = ['required', 'string', 'max:500'];
                            
                            // Different validation for WhatsApp
                            if ($get('platform') === 'whatsapp') {
                                // Allow phone numbers or wa.me links
                                return array_merge($rules, [
                                    function ($attribute, $value, $fail) {
                                        // Check if it's a valid phone number format or wa.me link
                                        if (!preg_match('/^\+?[0-9]{10,15}$/', $value) && 
                                            !str_starts_with($value, 'https://wa.me/') &&
                                            !str_starts_with($value, 'http://wa.me/')) {
                                            $fail('Please enter a valid phone number (e.g., +2348012345678) or wa.me link');
                                        }
                                    }
                                ]);
                            }
                            
                            // URL validation for other platforms
                            return array_merge($rules, ['url']);
                        }),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Display this social link on business page'),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('platform')
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter (X)',
                        'linkedin' => 'LinkedIn',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'pinterest' => 'Pinterest',
                        'whatsapp' => 'WhatsApp',
                        default => ucfirst($state),
                    })
                    ->color(fn ($state) => match($state) {
                        'facebook' => 'info',
                        'instagram' => 'danger',
                        'twitter' => 'info',
                        'linkedin' => 'primary',
                        'youtube' => 'danger',
                        'tiktok' => 'gray',
                        'pinterest' => 'danger',
                        'whatsapp' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'facebook' => 'heroicon-o-globe-alt',
                        'instagram' => 'heroicon-o-camera',
                        'twitter' => 'heroicon-o-chat-bubble-left',
                        'linkedin' => 'heroicon-o-briefcase',
                        'youtube' => 'heroicon-o-video-camera',
                        'whatsapp' => 'heroicon-o-phone',
                        'pinterest' => 'heroicon-o-photo',
                        'tiktok' => 'heroicon-o-musical-note',
                        default => 'heroicon-o-link',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('url')
                    ->searchable()
                    ->limit(50)
                    ->copyable()
                    ->formatStateUsing(function ($state, $record) {
                        // Format WhatsApp numbers nicely
                        if ($record->platform === 'whatsapp' && !str_contains($state, 'http')) {
                            return $state; // Show phone number as-is
                        }
                        return $state;
                    })
                    ->url(function ($record) {
                        // Convert WhatsApp phone numbers to wa.me links
                        if ($record->platform === 'whatsapp' && !str_contains($record->url, 'http')) {
                            $phone = preg_replace('/[^0-9+]/', '', $record->url);
                            return 'https://wa.me/' . ltrim($phone, '+');
                        }
                        return $record->url;
                    })
                    ->openUrlInNewTab(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter (X)',
                        'linkedin' => 'LinkedIn',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'pinterest' => 'Pinterest',
                        'whatsapp' => 'WhatsApp',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Social Account')
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-format WhatsApp numbers
                        if ($data['platform'] === 'whatsapp' && !str_contains($data['url'], 'http')) {
                            $phone = preg_replace('/[^0-9+]/', '', $data['url']);
                            // Store as wa.me link for consistency
                            $data['url'] = 'https://wa.me/' . ltrim($phone, '+');
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-format WhatsApp numbers on edit
                        if ($data['platform'] === 'whatsapp' && !str_contains($data['url'], 'http')) {
                            $phone = preg_replace('/[^0-9+]/', '', $data['url']);
                            $data['url'] = 'https://wa.me/' . ltrim($phone, '+');
                        }
                        return $data;
                    }),
                    
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('visit')
                    ->label('Visit')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(function ($record) {
                        // Handle WhatsApp links
                        if ($record->platform === 'whatsapp' && !str_contains($record->url, 'http')) {
                            $phone = preg_replace('/[^0-9+]/', '', $record->url);
                            return 'https://wa.me/' . ltrim($phone, '+');
                        }
                        return $record->url;
                    })
                    ->openUrlInNewTab()
                    ->color('info'),
                
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn ($record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn ($record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_active' => !$record->is_active])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->emptyStateHeading('No Social Media Accounts')
            ->emptyStateDescription('Add social media accounts to help customers connect.')
            ->emptyStateIcon('heroicon-o-globe-alt');
    }
}