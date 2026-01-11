<?php
// ============================================
// 1. AdPackageResource.php
// Location: app/Filament/Admin/Resources/AdPackageResource.php
// Panel: Admin Panel - Access: Admins
// ============================================
namespace App\Filament\Admin\Resources;
use App\Filament\Admin\Resources\AdPackageResource\Pages;
use App\Models\AdPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AdPackageResource extends Resource
{
    protected static ?string $model = AdPackage::class;
    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationLabel = 'Ad Packages';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Package Details')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(3)->maxLength(1000)->columnSpanFull(),
            ])->columns(2),
            Forms\Components\Section::make('Pricing & Duration')->schema([
                Forms\Components\TextInput::make('price')->numeric()->prefix('₦')->step(0.01)->required(),
                Forms\Components\Select::make('currency')->options(['NGN' => '₦ NGN', 'USD' => '$ USD'])->default('NGN')->required()->native(false),
                Forms\Components\Select::make('campaign_type')->options(['bump_up' => 'Bump Up', 'sponsored' => 'Sponsored', 'featured' => 'Featured'])->required()->native(false)->helperText('Type of advertising'),
                Forms\Components\TextInput::make('duration_days')->numeric()->required()->suffix('days')->helperText('Campaign duration'),
                Forms\Components\TextInput::make('impressions_limit')->numeric()->helperText('Max impressions (leave empty for unlimited)'),
                Forms\Components\TextInput::make('clicks_limit')->numeric()->helperText('Max clicks (leave empty for unlimited)'),
            ])->columns(3),
            Forms\Components\Section::make('Features')->schema([
                Forms\Components\KeyValue::make('features')->label('Package Features')->helperText('e.g., {"top_placement": true, "social_boost": true}')->columnSpanFull(),
            ])->collapsible(),
            Forms\Components\Section::make('Settings')->schema([
                Forms\Components\Toggle::make('is_popular')->label('Popular Package'),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                Forms\Components\TextInput::make('order')->numeric()->default(0),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('campaign_type')->badge()->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state)))->colors(['info' => 'bump_up', 'warning' => 'sponsored', 'success' => 'featured']),
            Tables\Columns\TextColumn::make('price')->money('NGN')->sortable(),
            Tables\Columns\TextColumn::make('duration_days')->suffix(' days')->sortable(),
            Tables\Columns\TextColumn::make('impressions_limit')->default('∞')->formatStateUsing(fn ($state) => $state ? number_format((float)$state) : '∞')->toggleable(),
            Tables\Columns\TextColumn::make('clicks_limit')->default('∞')->formatStateUsing(fn ($state) => $state ? number_format((float)$state) : '∞')->toggleable(),
            Tables\Columns\IconColumn::make('is_popular')->boolean()->label('Popular'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            Tables\Columns\TextColumn::make('campaigns_count')->counts('campaigns')->label('Campaigns')->sortable(),
            Tables\Columns\TextColumn::make('deleted_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Deleted At'),
        ])->defaultSort('order')->filters([
            Tables\Filters\SelectFilter::make('campaign_type')->options(['bump_up' => 'Bump Up', 'sponsored' => 'Sponsored', 'featured' => 'Featured']),
            Tables\Filters\TernaryFilter::make('is_active'),
            TrashedFilter::make()->label('Deleted Packages'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Ad Package')
                ->modalDescription('This will soft delete the package. It will be hidden but can be restored later.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Package deleted')
                        ->body('The ad package has been soft deleted.')
                ),
            Tables\Actions\RestoreAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Package restored')
                        ->body('The ad package has been restored.')
                ),
            Tables\Actions\ForceDeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Package')
                ->modalDescription('Are you sure? This will permanently delete the package and cannot be undone. Only do this if there are no active campaigns.')
                ->before(function ($record, $action) {
                    if ($record->campaigns()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete')
                            ->body('This package has associated campaigns. Please remove them first.')
                            ->persistent()
                            ->send();
                        
                        $action->cancel();
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Package permanently deleted')
                        ->body('The ad package has been permanently removed from the database.')
                ),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()
                ->requiresConfirmation()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Packages deleted')
                        ->body('The selected packages have been soft deleted.')
                ),
            Tables\Actions\RestoreBulkAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Packages restored')
                        ->body('The selected packages have been restored.')
                ),
            Tables\Actions\ForceDeleteBulkAction::make()
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Packages')
                ->modalDescription('This will permanently delete the selected packages. This action cannot be undone.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Packages permanently deleted')
                        ->body('The selected packages have been permanently removed.')
                ),
        ])->reorderable('order');
    }
    
    public static function getPages(): array { return ['index' => Pages\ListAdPackages::route('/'), 'create' => Pages\CreateAdPackage::route('/create'), 'edit' => Pages\EditAdPackage::route('/{record}/edit')]; }
    
    public static function getNavigationBadge(): ?string { return static::getModel()::count(); }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}