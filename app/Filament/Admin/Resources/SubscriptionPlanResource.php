<?php
// ============================================
// 1. SubscriptionPlanResource.php
// Location: app/Filament/Admin/Resources/SubscriptionPlanResource.php
// Panel: Admin Panel (/admin)
// Access: Admins
// Purpose: Manage subscription plans (Free, Basic, Pro, Enterprise)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionPlanResource\Pages;
use App\Models\SubscriptionPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class SubscriptionPlanResource extends Resource
{
    protected static ?string $model = SubscriptionPlan::class;
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Subscription Plans';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plan Details')->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(255)->live(onBlur: true)->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true)->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(3)->maxLength(1000)->columnSpanFull(),
            ])->columns(2),
            
            Forms\Components\Section::make('Pricing')->schema([
                Forms\Components\TextInput::make('price')->numeric()->prefix('₦')->step(0.01)->required()->helperText('Monthly price'),
                Forms\Components\TextInput::make('yearly_price')->numeric()->prefix('₦')->step(0.01)->helperText('Yearly price (leave empty if not applicable)'),
                Forms\Components\Select::make('currency')->options(['NGN' => '₦ NGN', 'USD' => '$ USD', 'EUR' => '€ EUR', 'GBP' => '£ GBP'])->default('NGN')->required()->native(false),
                Forms\Components\Select::make('billing_interval')->options(['monthly' => 'Monthly', 'yearly' => 'Yearly'])->default('monthly')->required()->native(false),
                Forms\Components\TextInput::make('trial_days')->numeric()->default(0)->suffix('days')->helperText('Free trial period'),
            ])->columns(3),
            
            Forms\Components\Section::make('Limits')->schema([
                Forms\Components\TextInput::make('max_branches')->numeric()->helperText('Leave empty for unlimited'),
                Forms\Components\TextInput::make('max_products')->numeric()->helperText('Leave empty for unlimited'),
                Forms\Components\TextInput::make('max_team_members')->numeric()->helperText('Leave empty for unlimited'),
                Forms\Components\TextInput::make('max_photos')->numeric()->helperText('Gallery photos limit'),
                Forms\Components\TextInput::make('monthly_ad_credits')->numeric()->default(0)->helperText('Free ad credits per month'),
            ])->columns(3),
            
            Forms\Components\Section::make('Features')->schema([
                Forms\Components\KeyValue::make('features')->label('Plan Features')->helperText('e.g., {"analytics": true, "priority_support": true}')->columnSpanFull(),
            ])->collapsible(),
            
            Forms\Components\Section::make('Display Settings')->schema([
                Forms\Components\Toggle::make('is_popular')->label('Popular Plan')->helperText('Show "Most Popular" badge'),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true)->helperText('Available for purchase'),
                Forms\Components\TextInput::make('order')->numeric()->default(0)->helperText('Display order'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->searchable()->sortable()->description(fn ($record) => Str::limit($record->description, 50)),
            Tables\Columns\TextColumn::make('price')->money('NGN')->sortable()->description(fn ($record) => $record->billing_interval === 'yearly' ? "Yearly: ₦" . number_format($record->yearly_price, 2) : null),
            Tables\Columns\TextColumn::make('billing_interval')->badge()->formatStateUsing(fn ($state) => ucfirst($state)),
            Tables\Columns\TextColumn::make('max_branches')->label('Branches')->default('∞')->formatStateUsing(fn ($state) => $state ?? '∞'),
            Tables\Columns\TextColumn::make('max_products')->label('Products')->default('∞')->formatStateUsing(fn ($state) => $state ?? '∞'),
            Tables\Columns\TextColumn::make('monthly_ad_credits')->label('Ad Credits')->suffix('/mo')->toggleable(),
            Tables\Columns\IconColumn::make('is_popular')->boolean()->label('Popular'),
            Tables\Columns\IconColumn::make('is_active')->boolean()->label('Active'),
            Tables\Columns\TextColumn::make('activeSubscriptions_count')->counts('activeSubscriptions')->label('Active Subs')->sortable(),
            Tables\Columns\TextColumn::make('order')->sortable(),
            Tables\Columns\TextColumn::make('deleted_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Deleted At'),
        ])->defaultSort('order')->filters([
            Tables\Filters\TernaryFilter::make('is_active')->label('Active Status'),
            Tables\Filters\TernaryFilter::make('is_popular')->label('Popular'),
            TrashedFilter::make()->label('Deleted Plans'),
        ])->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Subscription Plan')
                ->modalDescription('This will soft delete the plan. It will be hidden but can be restored later.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Plan deleted')
                        ->body('The subscription plan has been soft deleted.')
                ),
            Tables\Actions\RestoreAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Plan restored')
                        ->body('The subscription plan has been restored.')
                ),
            Tables\Actions\ForceDeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Plan')
                ->modalDescription('Are you sure? This will permanently delete the plan and cannot be undone. Only do this if there are no active subscriptions.')
                ->before(function ($record, $action) {
                    if ($record->activeSubscriptions()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot delete')
                            ->body('This plan has active subscriptions. Please cancel them first.')
                            ->persistent()
                            ->send();
                        
                        $action->cancel();
                    }
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Plan permanently deleted')
                        ->body('The subscription plan has been permanently removed from the database.')
                ),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make()
                ->requiresConfirmation()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Plans deleted')
                        ->body('The selected plans have been soft deleted.')
                ),
            Tables\Actions\RestoreBulkAction::make()
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Plans restored')
                        ->body('The selected plans have been restored.')
                ),
            Tables\Actions\ForceDeleteBulkAction::make()
                ->requiresConfirmation()
                ->modalHeading('Permanently Delete Plans')
                ->modalDescription('This will permanently delete the selected plans. This action cannot be undone.')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Plans permanently deleted')
                        ->body('The selected plans have been permanently removed.')
                ),
        ])->reorderable('order');
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubscriptionPlans::route('/'), 'create' => Pages\CreateSubscriptionPlan::route('/create'), 'edit' => Pages\EditSubscriptionPlan::route('/{record}/edit')];
    }

    public static function getNavigationBadge(): ?string { return static::getModel()::count(); }
    
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}