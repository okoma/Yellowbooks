<?php
// ============================================
// app/Filament/Admin/Resources/UserResource.php
// Manage all users, assign roles, ban users
// FILAMENT V3.3 COMPATIBLE
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use App\Enums\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Role & Permissions')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->options(UserRole::toArray())
                            ->required()
                            ->default(UserRole::CUSTOMER->value)
                            ->native(false),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive users cannot login'),
                        
                        Forms\Components\Toggle::make('is_banned')
                            ->label('Banned')
                            ->live()
                            ->helperText('Banned users are blocked from the platform'),
                        
                        Forms\Components\Textarea::make('ban_reason')
                            ->label('Ban Reason')
                            ->visible(fn (Forms\Get $get) => $get('is_banned'))
                            ->maxLength(500)
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Profile')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->image()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->imageEditor(),
                        
                        Forms\Components\Textarea::make('bio')
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Forms\Components\Section::make('Referral')
                    ->schema([
                        Forms\Components\TextInput::make('referral_code')
                            ->maxLength(255)
                            ->disabled()
                            ->helperText('Auto-generated on creation'),
                        
                        Forms\Components\Select::make('referred_by')
                            ->relationship('referrer', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name)),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('role')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => match($state) {
                        UserRole::ADMIN => 'danger',
                        UserRole::MODERATOR => 'warning',
                        UserRole::BUSINESS_OWNER => 'success',
                        UserRole::BRANCH_MANAGER => 'info',
                        UserRole::CUSTOMER => 'gray',
                    }),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\IconColumn::make('is_banned')
                    ->boolean()
                    ->label('Banned'),
                
                Tables\Columns\TextColumn::make('businesses_count')
                    ->counts('businesses')
                    ->label('Businesses')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('managing_branches_count')
                    ->label('Managing Branches')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('last_login_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options(UserRole::toArray())
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Users')
                    ->placeholder('All users')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\TernaryFilter::make('is_banned')
                    ->label('Banned Users')
                    ->placeholder('All users')
                    ->trueLabel('Banned only')
                    ->falseLabel('Not banned'),
                
                Tables\Filters\TernaryFilter::make('is_branch_manager')
                    ->label('Branch Managers')
                    ->placeholder('All users')
                    ->trueLabel('Managers only')
                    ->falseLabel('Non-managers'),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Registered from'),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Registered until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('ban')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('ban_reason')
                                ->required()
                                ->label('Reason for ban'),
                        ])
                        ->action(function (User $record, array $data) {
                            $record->update([
                                'is_banned' => true,
                                'is_active' => false,
                                'ban_reason' => $data['ban_reason'],
                                'banned_at' => now(),
                            ]);
                        })
                        ->visible(fn (User $record) => !$record->is_banned),
                    
                    Tables\Actions\Action::make('unban')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (User $record) {
                            $record->update([
                                'is_banned' => false,
                                'is_active' => true,
                                'ban_reason' => null,
                                'banned_at' => null,
                            ]);
                        })
                        ->visible(fn (User $record) => $record->is_banned),
                    
                    Tables\Actions\Action::make('promote_to_business_owner')
                        ->label('Promote to Business Owner')
                        ->icon('heroicon-o-arrow-up-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (User $record) => $record->promoteToBusinessOwner())
                        ->visible(fn (User $record) => $record->isCustomer()),
                    
                    Tables\Actions\Action::make('promote_to_moderator')
                        ->label('Promote to Moderator')
                        ->icon('heroicon-o-shield-check')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (User $record) => $record->promoteToModerator())
                        ->visible(fn (User $record) => !$record->isAdmin() && !$record->isModerator()),
                    
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn (User $record) => !$record->isAdmin()),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Can add relation managers here later
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }
}