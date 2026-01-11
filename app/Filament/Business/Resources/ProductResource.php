<?php
// ============================================
// app/Filament/Business/Resources/ProductResource.php
// Centralized product/service management
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    
    protected static ?string $navigationLabel = 'Products & Services';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Location')
                    ->description('Where is this product/service available?')
                    ->schema([
                        Forms\Components\Select::make('business_id')
                            ->label('Business')
                            ->relationship(
                                'business',
                                'business_name',
                                fn($query) => $query->where('user_id', Auth::id())
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(),
                        
                        Forms\Components\Select::make('business_branch_id')
                            ->label('Branch (Optional)')
                            ->relationship('branch', 'branch_title')
                            ->searchable()
                            ->preload()
                            ->helperText('Leave empty for standalone businesses'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('header_title')
                            ->label('Category/Section')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., "Main Dishes", "Beverages"'),
                        
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set, $operation) => 
                                $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null
                            ),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                        
                        Forms\Components\FileUpload::make('image')
                            ->image()
                            ->directory('product-images')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Pricing')
                    ->schema([
                        Forms\Components\Select::make('currency')
                            ->options([
                                'NGN' => 'Nigerian Naira (₦)',
                                'USD' => 'US Dollar ($)',
                                'EUR' => 'Euro (€)',
                                'GBP' => 'British Pound (£)',
                            ])
                            ->default('NGN')
                            ->required(),
                        
                        Forms\Components\TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->prefix('₦')
                            ->minValue(0)
                            ->step(0.01),
                        
                        Forms\Components\Select::make('discount_type')
                            ->options([
                                'none' => 'No Discount',
                                'percentage' => 'Percentage Off',
                                'fixed' => 'Fixed Amount Off',
                            ])
                            ->default('none')
                            ->live()
                            ->required(),
                        
                        Forms\Components\TextInput::make('discount_value')
                            ->numeric()
                            ->minValue(0)
                            ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'none')
                            ->label(fn (Forms\Get $get) => 
                                $get('discount_type') === 'percentage' ? 'Discount %' : 'Discount Amount'
                            ),
                        
                        Forms\Components\Placeholder::make('final_price_display')
                            ->label('Final Price')
                            ->content(function (Forms\Get $get) {
                                $price = $get('price') ?? 0;
                                $discountType = $get('discount_type') ?? 'none';
                                $discountValue = $get('discount_value') ?? 0;
                                
                                if ($discountType === 'none') {
                                    return '₦' . number_format($price, 2);
                                }
                                
                                if ($discountType === 'percentage') {
                                    $finalPrice = $price - ($price * $discountValue / 100);
                                } else {
                                    $finalPrice = $price - $discountValue;
                                }
                                
                                return '₦' . number_format(max(0, $finalPrice), 2);
                            }),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Availability')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->label('Currently Available')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular(),
                
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('branch.branch_title')
                    ->label('Branch')
                    ->searchable()
                    ->badge()
                    ->color('success')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('header_title')
                    ->label('Category')
                    ->searchable()
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('NGN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('discount_type')
                    ->badge()
                    ->color(fn ($state) => $state !== 'none' ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('final_price')
                    ->money('NGN')
                    ->label('Final Price')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available'),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('header_title')
                    ->label('Category'),
                
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Available Only'),
                
                Tables\Filters\Filter::make('has_discount')
                    ->label('Has Discount')
                    ->query(fn ($query) => $query->where('discount_type', '!=', 'none')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('make_available')
                        ->label('Mark Available')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_available' => true])),
                    
                    Tables\Actions\BulkAction::make('make_unavailable')
                        ->label('Mark Unavailable')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_available' => false])),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where(function($q) {
            $businesses = Auth::user()->businesses()->pluck('id');
            $branches = Auth::user()->businesses()->with('branches')->get()
                ->flatMap(fn($b) => $b->branches->pluck('id'));
            
            $q->whereIn('business_id', $businesses)
              ->orWhereIn('business_branch_id', $branches);
        })->count();
    }
    
    public static function getEloquentQuery(): Builder
    {
        $businesses = Auth::user()->businesses()->pluck('id');
        $branches = Auth::user()->businesses()->with('branches')->get()
            ->flatMap(fn($b) => $b->branches->pluck('id'));
        
        return parent::getEloquentQuery()
            ->where(function($q) use ($businesses, $branches) {
                $q->whereIn('business_id', $businesses)
                  ->orWhereIn('business_branch_id', $branches);
            });
    }
}