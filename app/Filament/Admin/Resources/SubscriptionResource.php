<?php

// ============================================
// 2. SubscriptionResource.php
// Location: app/Filament/Admin/Resources/SubscriptionResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage active user subscriptions
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Subscriptions';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Subscription Details')->schema([
                Forms\Components\Select::make('user_id')->relationship('user', 'name')->required()->searchable()->preload()->disabled(fn ($context) => $context !== 'create'),
                Forms\Components\Select::make('subscription_plan_id')->relationship('plan', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('business_id')->relationship('business', 'business_name')->searchable()->preload()->helperText('Optional: Link to specific business'),
                Forms\Components\TextInput::make('subscription_code')->maxLength(255)->disabled()->helperText('Auto-generated'),
            ])->columns(2),
            
            Forms\Components\Section::make('Status & Dates')->schema([
                Forms\Components\Select::make('status')->options(['active' => 'Active', 'cancelled' => 'Cancelled', 'expired' => 'Expired', 'paused' => 'Paused', 'trialing' => 'Trialing'])->required()->default('active')->native(false),
                Forms\Components\DateTimePicker::make('starts_at')->required()->default(now())->native(false),
                Forms\Components\DateTimePicker::make('ends_at')->required()->default(now()->addDays(30))->native(false),
                Forms\Components\DateTimePicker::make('trial_ends_at')->native(false)->helperText('Trial period end date'),
                Forms\Components\Toggle::make('auto_renew')->label('Auto Renew')->default(true),
            ])->columns(3),
            
            Forms\Components\Section::make('Usage Tracking')->schema([
                Forms\Components\TextInput::make('branches_used')->numeric()->default(0),
                Forms\Components\TextInput::make('products_used')->numeric()->default(0),
                Forms\Components\TextInput::make('team_members_used')->numeric()->default(0),
                Forms\Components\TextInput::make('photos_used')->numeric()->default(0),
                Forms\Components\TextInput::make('ad_credits_used')->numeric()->default(0),
            ])->columns(3)->collapsible(),
            
            Forms\Components\Section::make('Cancellation')->schema([
                Forms\Components\DateTimePicker::make('cancelled_at')->disabled()->native(false),
                Forms\Components\DateTimePicker::make('paused_at')->disabled()->native(false),
                Forms\Components\Textarea::make('cancellation_reason')->rows(2)->maxLength(500)->columnSpanFull(),
            ])->columns(2)->collapsible()->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('subscription_code')->searchable()->copyable()->label('Code'),
            Tables\Columns\TextColumn::make('user.name')->searchable()->sortable()->url(fn ($r) => route('filament.admin.resources.users.view', $r->user)),
            Tables\Columns\TextColumn::make('plan.name')->searchable()->sortable()->badge()->color('info'),
            Tables\Columns\TextColumn::make('business.business_name')->searchable()->toggleable()->limit(30),
            Tables\Columns\TextColumn::make('status')->badge()->colors(['success' => 'active', 'danger' => 'cancelled', 'warning' => 'expired', 'gray' => 'paused', 'info' => 'trialing'])->formatStateUsing(fn ($s) => ucfirst($s))->sortable(),
            Tables\Columns\TextColumn::make('starts_at')->dateTime()->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('ends_at')->dateTime()->sortable()->since()->description(fn ($r) => $r->ends_at->format('M d, Y')),
            Tables\Columns\IconColumn::make('auto_renew')->boolean()->label('Auto Renew')->toggleable(),
            Tables\Columns\TextColumn::make('branches_used')->label('Branches')->suffix(fn ($r) => '/' . ($r->plan->max_branches ?? '∞'))->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('products_used')->label('Products')->suffix(fn ($r) => '/' . ($r->plan->max_products ?? '∞'))->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('status')->options(['active' => 'Active', 'cancelled' => 'Cancelled', 'expired' => 'Expired', 'paused' => 'Paused', 'trialing' => 'Trialing'])->multiple(),
            Tables\Filters\SelectFilter::make('subscription_plan_id')->relationship('plan', 'name')->multiple(),
            Tables\Filters\TernaryFilter::make('auto_renew')->label('Auto Renew'),
            Tables\Filters\Filter::make('expiring_soon')->label('Expiring Soon (7 days)')->query(fn ($q) => $q->where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])),
        ])->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('renew')->icon('heroicon-o-arrow-path')->color('success')->form([Forms\Components\TextInput::make('days')->numeric()->default(30)->required()->label('Renew for (days)')])->action(function (Subscription $r, array $data) { $r->renew($data['days']); Notification::make()->success()->title('Subscription Renewed')->body("Extended by {$data['days']} days")->send(); }),
                Tables\Actions\Action::make('cancel')->icon('heroicon-o-x-circle')->color('danger')->requiresConfirmation()->form([Forms\Components\Textarea::make('reason')->label('Cancellation Reason')->rows(2)])->action(function (Subscription $r, array $data) { $r->cancel($data['reason'] ?? null); Notification::make()->warning()->title('Subscription Cancelled')->send(); })->visible(fn (Subscription $r) => $r->status === 'active'),
                Tables\Actions\Action::make('pause')->icon('heroicon-o-pause')->color('warning')->requiresConfirmation()->action(function (Subscription $r) { $r->pause(); Notification::make()->info()->title('Subscription Paused')->send(); })->visible(fn (Subscription $r) => $r->status === 'active'),
                Tables\Actions\Action::make('resume')->icon('heroicon-o-play')->color('success')->requiresConfirmation()->action(function (Subscription $r) { $r->resume(); Notification::make()->success()->title('Subscription Resumed')->send(); })->visible(fn (Subscription $r) => $r->status === 'paused'),
                Tables\Actions\DeleteAction::make(),
            ]),
        ])->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\BulkAction::make('cancel_selected')->label('Cancel Selected')->icon('heroicon-o-x-mark')->color('danger')->requiresConfirmation()->action(fn ($records) => $records->each->cancel('Bulk cancellation')),
        ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListSubscriptions::route('/'), 'create' => Pages\CreateSubscription::route('/create'), 'edit' => Pages\EditSubscription::route('/{record}/edit'), 'view' => Pages\ViewSubscription::route('/{record}')];
    }

    public static function getNavigationBadge(): ?string
    {
        $expiringSoon = static::getModel()::where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])->count();
        return $expiringSoon > 0 ? (string) $expiringSoon : null;
    }

    public static function getNavigationBadgeColor(): ?string { return 'warning'; }
}