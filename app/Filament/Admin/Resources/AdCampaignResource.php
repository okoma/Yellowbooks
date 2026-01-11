<?php

// ============================================
// 2. AdCampaignResource.php
// Location: app/Filament/Admin/Resources/AdCampaignResource.php
// Panel: Admin Panel - Access: Admins, Moderators
// ============================================
namespace App\Filament\Admin\Resources;
use App\Filament\Admin\Resources\AdCampaignResource\Pages;
use App\Models\AdCampaign;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AdCampaignResource extends Resource
{
    protected static ?string $model = AdCampaign::class;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Ad Campaigns';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Campaign Info')->schema([
                Forms\Components\Select::make('business_id')->relationship('business', 'business_name')->required()->searchable()->preload(),
                Forms\Components\Select::make('purchased_by')->relationship('purchaser', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('ad_package_id')->relationship('package', 'name')->required()->searchable()->preload(),
                Forms\Components\Select::make('type')->options(['bump_up' => 'Bump Up', 'sponsored' => 'Sponsored', 'featured' => 'Featured'])->required()->native(false),
            ])->columns(2),
            Forms\Components\Section::make('Content')->schema([
                Forms\Components\TextInput::make('title')->maxLength(255),
                Forms\Components\Textarea::make('description')->rows(3)->maxLength(500)->columnSpanFull(),
                Forms\Components\FileUpload::make('banner_image')->image()->directory('ad-banners')->maxSize(2048)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Targeting')->schema([
                Forms\Components\TagsInput::make('target_locations')->helperText('Target cities/states'),
                Forms\Components\TagsInput::make('target_categories')->helperText('Target business categories'),
            ])->columns(2)->collapsible(),
            Forms\Components\Section::make('Schedule & Budget')->schema([
                Forms\Components\DateTimePicker::make('starts_at')->required()->default(now())->native(false),
                Forms\Components\DateTimePicker::make('ends_at')->required()->default(now()->addDays(30))->native(false),
                Forms\Components\TextInput::make('budget')->numeric()->prefix('₦')->step(0.01)->required(),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                Forms\Components\Toggle::make('is_paid')->label('Paid')->default(false),
            ])->columns(3),
            Forms\Components\Section::make('Performance (Read-only)')->schema([
                Forms\Components\TextInput::make('total_impressions')->numeric()->disabled(),
                Forms\Components\TextInput::make('total_clicks')->numeric()->disabled(),
                Forms\Components\TextInput::make('total_spent')->numeric()->prefix('₦')->disabled(),
                Forms\Components\TextInput::make('ctr')->numeric()->suffix('%')->disabled()->helperText('Click-through rate'),
            ])->columns(4)->collapsible()->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('business.business_name')->searchable()->sortable()->limit(30),
            Tables\Columns\TextColumn::make('type')->badge()->formatStateUsing(fn ($s) => ucwords(str_replace('_', ' ', $s)))->colors(['info' => 'bump_up', 'warning' => 'sponsored', 'success' => 'featured']),
            Tables\Columns\TextColumn::make('budget')->money('NGN')->sortable(),
            Tables\Columns\TextColumn::make('total_spent')->money('NGN')->sortable()->description(fn ($r) => $r->budgetUsedPercentage() . '% used'),
            Tables\Columns\TextColumn::make('total_impressions')->formatStateUsing(fn ($s) => number_format($s))->sortable(),
            Tables\Columns\TextColumn::make('total_clicks')->formatStateUsing(fn ($s) => number_format($s))->sortable(),
            Tables\Columns\TextColumn::make('ctr')->suffix('%')->sortable()->formatStateUsing(fn ($s) => number_format($s, 2)),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
            Tables\Columns\IconColumn::make('is_paid')->boolean(),
            Tables\Columns\TextColumn::make('ends_at')->dateTime()->sortable()->since(),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('type')->options(['bump_up' => 'Bump Up', 'sponsored' => 'Sponsored', 'featured' => 'Featured']),
            Tables\Filters\TernaryFilter::make('is_active'),
            Tables\Filters\TernaryFilter::make('is_paid'),
            Tables\Filters\Filter::make('expiring_soon')
          ->query(fn (\Illuminate\Database\Eloquent\Builder $query) =>
          $query->where('is_active', true)
              ->whereBetween('ends_at', [now(), now()->addDays(3)])),

        ])->actions([
            Tables\Actions\ActionGroup::make([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('pause')->icon('heroicon-o-pause')->color('warning')->requiresConfirmation()->action(fn (AdCampaign $r) => $r->pause())->visible(fn (AdCampaign $r) => $r->is_active),
                Tables\Actions\Action::make('resume')->icon('heroicon-o-play')->color('success')->requiresConfirmation()->action(fn (AdCampaign $r) => $r->resume())->visible(fn (AdCampaign $r) => !$r->is_active && $r->ends_at->isFuture()),
                Tables\Actions\DeleteAction::make(),
            ]),
        ])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListAdCampaigns::route('/'), 'create' => Pages\CreateAdCampaign::route('/create'), 'edit' => Pages\EditAdCampaign::route('/{record}/edit'), 'view' => Pages\ViewAdCampaign::route('/{record}')]; }
   public static function getNavigationBadge(): ?string
  {
    $expiring = AdCampaign::query()
        ->where('is_active', true)
        ->whereBetween('ends_at', [now(), now()->addDays(3)])
        ->count();

    return $expiring > 0 ? (string) $expiring : null;
 }

}
