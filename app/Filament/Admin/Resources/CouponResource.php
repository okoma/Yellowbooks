<?php
// ============================================
// 3. CouponResource.php
// Location: app/Filament/Admin/Resources/CouponResource.php
// Panel: Admin Panel - Access: Admins
// ============================================
namespace App\Filament\Admin\Resources;
use App\Filament\Admin\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;
    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Coupons';
    protected static ?string $navigationGroup = 'Monetization';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Coupon Details')->schema([
                Forms\Components\TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true)->helperText('Unique coupon code (e.g., SAVE20)')->columnSpan(1),
                Forms\Components\Textarea::make('description')->rows(2)->maxLength(500)->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Discount')->schema([
                Forms\Components\Select::make('discount_type')->options(['percentage' => 'Percentage Off', 'fixed' => 'Fixed Amount Off'])->required()->native(false)->live(),
                Forms\Components\TextInput::make('discount_value')->numeric()->required()->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : '₦')->step(0.01),
                Forms\Components\TextInput::make('max_discount')->numeric()->prefix('₦')->step(0.01)->helperText('Max discount for percentage coupons')->visible(fn (Forms\Get $get) => $get('discount_type') === 'percentage'),
            ])->columns(3),
            Forms\Components\Section::make('Applicability')->schema([
                Forms\Components\Select::make('applies_to')->options(['all' => 'All Products', 'subscriptions' => 'Subscriptions', 'ad_campaigns' => 'Ad Campaigns'])->required()->native(false),
                Forms\Components\TagsInput::make('applicable_plans')->helperText('Specific plan IDs (leave empty for all)')->visible(fn (Forms\Get $get) => $get('applies_to') === 'subscriptions'),
            ])->columns(2),
            Forms\Components\Section::make('Usage Limits')->schema([
                Forms\Components\TextInput::make('usage_limit')->numeric()->helperText('Total uses (leave empty for unlimited)'),
                Forms\Components\TextInput::make('usage_limit_per_user')->numeric()->default(1)->required()->helperText('Uses per user'),
                Forms\Components\TextInput::make('min_purchase_amount')->numeric()->prefix('₦')->step(0.01)->helperText('Minimum purchase amount'),
            ])->columns(3),
            Forms\Components\Section::make('Validity')->schema([
                Forms\Components\DateTimePicker::make('valid_from')->native(false),
                Forms\Components\DateTimePicker::make('valid_until')->native(false),
                Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('code')->searchable()->sortable()->copyable()->description(fn ($record) => $record->description),
            Tables\Columns\TextColumn::make('discount_type')->badge()->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),
            Tables\Columns\TextColumn::make('discount_value')->formatStateUsing(fn ($state, $record) => $record->discount_type === 'percentage' ? $state . '%' : '₦' . number_format($state, 2)),
            Tables\Columns\TextColumn::make('times_used')->sortable()->suffix(fn ($record) => $record->usage_limit ? '/' . $record->usage_limit : ''),
            Tables\Columns\TextColumn::make('applies_to')->badge()->formatStateUsing(fn ($state) => match($state) {
                'subscriptions' => 'Subscriptions',
                'ad_campaigns' => 'Ad Campaigns',
                'all' => 'All Products',
                default => ucfirst($state)
            }),
            Tables\Columns\TextColumn::make('valid_from')->dateTime()->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('valid_until')->dateTime()->sortable()->since()->description(fn ($record) => $record->valid_until?->format('M d, Y')),
            Tables\Columns\IconColumn::make('is_active')->boolean(),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('discount_type')->options(['percentage' => 'Percentage', 'fixed' => 'Fixed Amount']),
            Tables\Filters\TernaryFilter::make('is_active'),
            Tables\Filters\Filter::make('valid_now')->label('Valid Now')->query(fn ($q) => $q->where('is_active', true)->where(fn ($q) => $q->whereNull('valid_from')->orWhere('valid_from', '<=', now()))->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))),
        ])->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListCoupons::route('/'), 'create' => Pages\CreateCoupon::route('/create'), 'edit' => Pages\EditCoupon::route('/{record}/edit')]; }
}