<?php
// ============================================
// app/Filament/Business/Resources/BranchManagerResource.php
// Manage existing branch managers - view, edit permissions, remove
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BranchManagerResource\Pages;
use App\Models\BranchManager;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class BranchManagerResource extends Resource
{
    protected static ?string $model = BranchManager::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Branch Managers';

    protected static ?string $navigationGroup = 'Team Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manager Information')
                    ->schema([
                        Forms\Components\Select::make('business_branch_id')
                            ->label('Branch')
                            ->relationship('branch', 'branch_title', function ($query) {
                                $query->whereHas('business', function ($q) {
                                    $q->where('user_id', auth()->id());
                                });
                            })
                            ->required()
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('user.name')
                            ->label('Manager Name')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('user.email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),

                        Forms\Components\TextInput::make('position')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('employee_id')
                            ->label('Employee ID')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(20),

                        Forms\Components\TextInput::make('whatsapp')
                            ->tel()
                            ->maxLength(20),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Status & Role')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Inactive managers cannot access the branch'),

                        Forms\Components\Toggle::make('is_primary')
                            ->label('Primary Manager')
                            ->helperText('Only one primary manager per branch')
                            ->reactive(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Permissions')
                    ->schema([
                        Forms\Components\CheckboxList::make('permissions')
                            ->label('Manager Permissions')
                            ->options([
                                'can_edit_branch' => 'Edit Branch Information',
                                'can_manage_products' => 'Manage Products/Services',
                                'can_respond_to_reviews' => 'Respond to Reviews',
                                'can_view_leads' => 'View Customer Leads',
                                'can_respond_to_leads' => 'Respond to Leads',
                                'can_view_analytics' => 'View Analytics & Reports',
                                'can_access_financials' => 'Access Financial Data',
                                'can_manage_staff' => 'Manage Staff Members',
                            ])
                            ->columns(2)
                            ->gridDirection('row'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Only show managers for current user's branches
                $query->whereHas('branch.business', function ($q) {
                    $q->where('user_id', auth()->id());
                });
            })
            ->columns([
                Tables\Columns\ImageColumn::make('user.avatar')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->user->name ?? 'Manager')),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Manager')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->position),

                Tables\Columns\TextColumn::make('branch.branch_title')
                    ->label('Branch')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->branch->business->business_name ?? ''),

                Tables\Columns\IconColumn::make('is_primary')
                    ->label('Primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('')
                    ->trueColor('warning'),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('assigned_at')
                    ->label('Since')
                    ->date('M j, Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All managers')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                Tables\Filters\TernaryFilter::make('is_primary')
                    ->label('Primary Manager')
                    ->placeholder('All managers')
                    ->trueLabel('Primary only')
                    ->falseLabel('Non-primary only'),

                Tables\Filters\SelectFilter::make('business_branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'branch_title', function ($query) {
                        $query->whereHas('business', function ($q) {
                            $q->where('user_id', auth()->id());
                        });
                    })
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),

                    Tables\Actions\Action::make('deactivate')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function (BranchManager $record) {
                            $record->deactivate();

                            Notification::make()
                                ->success()
                                ->title('Manager Deactivated')
                                ->body("{$record->user->name} can no longer access this branch.")
                                ->send();
                        })
                        ->visible(fn (BranchManager $record) => $record->is_active),

                    Tables\Actions\Action::make('activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (BranchManager $record) {
                            $record->activate();

                            Notification::make()
                                ->success()
                                ->title('Manager Activated')
                                ->body("{$record->user->name} can now access this branch.")
                                ->send();
                        })
                        ->visible(fn (BranchManager $record) => !$record->is_active),

                    Tables\Actions\Action::make('remove')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Remove Manager?')
                        ->modalDescription('This will permanently remove this manager from the branch. They will lose all access.')
                        ->action(function (BranchManager $record) {
                            $name = $record->user->name;
                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Manager Removed')
                                ->body("{$name} has been removed from this branch.")
                                ->send();
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->deactivate();

                            Notification::make()
                                ->success()
                                ->title('Managers Deactivated')
                                ->body(count($records) . ' managers deactivated.')
                                ->send();
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Remove Selected'),
                ]),
            ])
            ->defaultSort('assigned_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranchManagers::route('/'),
            'view' => Pages\ViewBranchManager::route('/{record}'),
            'edit' => Pages\EditBranchManager::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('branch.business', function ($q) {
            $q->where('user_id', auth()->id());
        })
        ->where('is_active', true)
        ->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}