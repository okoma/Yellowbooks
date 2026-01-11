<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/RelationManagers/SocialAccountsRelationManager.php
// Manage business social media accounts
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SocialAccountsRelationManager extends RelationManager
{
    protected static string $relationship = 'socialAccounts';
    
    protected static ?string $title = 'Social Media Accounts';
    
    protected static ?string $icon = 'heroicon-o-share';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('platform')
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
                    ->required()
                    ->searchable()
                    ->unique(ignoreRecord: true)
                    ->helperText('Each platform can only be added once'),
                
                Forms\Components\TextInput::make('url')
                    ->label('Profile URL')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->placeholder('https://facebook.com/yourbusiness')
                    ->helperText('Enter the full URL to your social media profile'),
                
                Forms\Components\Toggle::make('is_active')
                    ->label('Show on Profile')
                    ->default(true)
                    ->helperText('Display this social link on your business profile'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('platform')
            ->columns([
                Tables\Columns\TextColumn::make('platform')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'facebook' => 'info',
                        'instagram' => 'danger',
                        'twitter' => 'primary',
                        'linkedin' => 'info',
                        'youtube' => 'danger',
                        'tiktok' => 'gray',
                        default => 'secondary',
                    })
                    ->icon(fn ($state) => match($state) {
                        'facebook' => 'heroicon-o-globe-alt',
                        'instagram' => 'heroicon-o-camera',
                        'twitter' => 'heroicon-o-megaphone',
                        'linkedin' => 'heroicon-o-briefcase',
                        'youtube' => 'heroicon-o-video-camera',
                        'tiktok' => 'heroicon-o-musical-note',
                        default => 'heroicon-o-link',
                    }),
                
                Tables\Columns\TextColumn::make('url')
                    ->label('Profile URL')
                    ->limit(50)
                    ->copyable()
                    ->url(fn ($record) => $record->url)
                    ->openUrlInNewTab(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter',
                        'linkedin' => 'LinkedIn',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Social Account'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('visit')
                        ->label('Visit Profile')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->url(fn ($record) => $record->url)
                        ->openUrlInNewTab()
                        ->color('info'),
                    
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Your First Social Account'),
            ])
            ->emptyStateHeading('No Social Media Accounts')
            ->emptyStateDescription('Connect your social media profiles to increase your online presence.')
            ->emptyStateIcon('heroicon-o-share');
    }
}