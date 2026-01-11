<?php
// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource.php
// REFACTORED VERSION - Branch inherits from parent, can have own relationships
// Clear indication of inherited vs branch-specific fields
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessBranchResource\Pages;
use App\Filament\Admin\Resources\BusinessBranchResource\RelationManagers;
use App\Models\BusinessBranch;
use App\Models\Business;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BusinessBranchResource extends Resource
{
    protected static ?string $model = BusinessBranch::class;
    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Business Branches';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 3;
    protected static ?string $recordTitleAttribute = 'branch_title';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Branch Details')
                ->tabs([
                    // ===== TAB 1: Parent Business & Basic Info =====
                    Forms\Components\Tabs\Tab::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Section::make('Parent Business')
                                ->description('🔗 Link this branch to a parent business. Core information (type, categories) will be inherited.')
                                ->schema([
                                    Forms\Components\Select::make('business_id')
                                        ->label('Parent Business')
                                        ->relationship('business', 'business_name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if ($state) {
                                                $business = Business::with(['businessType', 'categories', 'paymentMethods'])->find($state);
                                                if ($business) {
                                                    // Inherit business hours from parent
                                                    $set('business_hours', $business->business_hours ?? []);
                                                    
                                                    // Inherit payment methods from parent
                                                    $set('payment_methods', $business->paymentMethods->pluck('id')->toArray());
                                                    
                                                    // Set inherited data for display
                                                    $set('inherited_type_name', $business->businessType?->name);
                                                    $set('inherited_categories', $business->categories->pluck('name')->join(', '));
                                                    
                                                    // Auto-generate branch title and slug
                                                    self::updateBranchTitleAndSlug($get, $set, $business);
                                                }
                                            }
                                        })
                                        ->helperText('Select the business this branch belongs to')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Placeholder::make('inherited_info')
                                        ->label('📋 Inherited from Parent Business')
                                        ->content(function (Forms\Get $get) {
                                            $businessId = $get('business_id');
                                            if (!$businessId) {
                                                return 'Select a parent business to see inherited information';
                                            }
                                            
                                            $business = Business::with(['businessType', 'categories'])->find($businessId);
                                            if (!$business) {
                                                return 'Business not found';
                                            }
                                            
                                            $type = $business->businessType?->name ?? 'None';
                                            $categories = $business->categories->pluck('name')->join(', ') ?: 'None';
                                            
                                            return "Type: {$type} | Categories: {$categories}";
                                        })
                                        ->visible(fn (Forms\Get $get) => $get('business_id'))
                                        ->columnSpanFull(),
                                ])
                                ->columns(1),
                            
                            Forms\Components\Section::make('Branch Identification')
                                ->description('📍 Branch-specific details')
                                ->schema([
                                    Forms\Components\TextInput::make('branch_title')
                                        ->label('Branch Name (Auto-generated)')
                                        ->disabled()
                                        ->dehydrated()
                                        ->helperText('Auto-generated: Business Name + City')
                                        ->placeholder('Will be generated after selecting parent and city')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\TextInput::make('slug')
                                        ->label('URL Slug (Auto-generated)')
                                        ->disabled()
                                        ->dehydrated()
                                        ->helperText('Auto-generated URL identifier')
                                        ->placeholder('Will be generated automatically')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Textarea::make('branch_description')
                                        ->label('Branch Description (REQUIRED - Must be unique)')
                                        ->required()
                                        ->minLength(100)
                                        ->maxLength(1000)
                                        ->rows(5)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            $businessId = $get('business_id');
                                            if ($businessId && $state) {
                                                $business = Business::find($businessId);
                                                if ($business && $business->description) {
                                                    similar_text(
                                                        strtolower($state),
                                                        strtolower($business->description),
                                                        $percent
                                                    );
                                                    
                                                    $set('content_similarity_score', round($percent, 2));
                                                    $set('has_unique_content', $percent < 30);
                                                }
                                            }
                                        })
                                        ->helperText('⚠️ IMPORTANT: Describe what makes THIS LOCATION unique. Mention nearby landmarks, parking, special features, etc.')
                                        ->placeholder('Example: Located in Ikeja City Mall with ample free parking. Features a spacious dine-in area with AC, free WiFi, and kids play area.')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\TagsInput::make('unique_features')
                                        ->label('Unique Features of This Branch')
                                        ->placeholder('Press Enter after each feature')
                                        ->helperText('What makes this branch special?')
                                        ->suggestions([
                                            'Free WiFi',
                                            'Free Parking',
                                            'Wheelchair Accessible',
                                            'Air Conditioned',
                                            'Kids Play Area',
                                            'Drive-thru',
                                            '24/7 Service',
                                            'Outdoor Seating',
                                            'Private Rooms',
                                            'Delivery Available',
                                        ])
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Textarea::make('nearby_landmarks')
                                        ->label('Nearby Landmarks & Directions')
                                        ->rows(3)
                                        ->maxLength(500)
                                        ->helperText('Help visitors find you! Mention nearby landmarks, malls, major roads.')
                                        ->placeholder('Example: Opposite ShopRite Mall, next to GTBank. 5 minutes walk from Ikeja Bus Stop.')
                                        ->columnSpanFull(),
                                ])->columns(1),
                        ]),

                    // ===== TAB 2: Location (BRANCH-SPECIFIC) =====
                    Forms\Components\Tabs\Tab::make('Location')
                        ->icon('heroicon-o-map')
                        ->badge('Required')
                        ->badgeColor('warning')
                        ->schema([
                            Forms\Components\Section::make('Branch Address')
                                ->description('📍 Branch-specific location (NOT inherited from parent)')
                                ->schema([
                                    Forms\Components\Select::make('state_location_id')
                                        ->label('State')
                                        ->options(function () {
                                            return Location::whereNull('parent_id')
                                                ->where('type', 'state')
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $set('city_location_id', null);
                                            $set('area', null);
                                            
                                            if ($state) {
                                                $location = Location::find($state);
                                                $set('state', $location?->name);
                                            }
                                        }),
                                    
                                    Forms\Components\Select::make('city_location_id')
                                        ->label('City')
                                        ->options(function (Forms\Get $get) {
                                            $stateId = $get('state_location_id');
                                            if (!$stateId) {
                                                return [];
                                            }
                                            
                                            return Location::where('parent_id', $stateId)
                                                ->where('type', 'city')
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->disabled(fn (Forms\Get $get) => !$get('state_location_id'))
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                            if ($state) {
                                                $location = Location::find($state);
                                                $set('city', $location?->name);
                                                
                                                // Trigger branch title update
                                                $businessId = $get('business_id');
                                                if ($businessId) {
                                                    $business = Business::find($businessId);
                                                    if ($business) {
                                                        self::updateBranchTitleAndSlug($get, $set, $business);
                                                    }
                                                }
                                            }
                                        }),
                                    
                                    Forms\Components\Hidden::make('state'),
                                    Forms\Components\Hidden::make('city'),
                                    
                                    Forms\Components\TextInput::make('area')
                                        ->label('Area/Neighborhood')
                                        ->maxLength(100)
                                        ->helperText('e.g., Ikeja GRA, Victoria Island'),
                                    
                                    Forms\Components\Textarea::make('address')
                                        ->label('Street Address')
                                        ->required()
                                        ->rows(2)
                                        ->maxLength(500)
                                        ->helperText('Full street address for this branch')
                                        ->placeholder('123 Main Street, Suite 100')
                                        ->columnSpanFull(),
                                ])->columns(3),
                            
                            Forms\Components\Section::make('Map Coordinates')
                                ->description('For displaying on Google Maps')
                                ->schema([
                                    Forms\Components\TextInput::make('latitude')
                                        ->numeric()
                                        ->step(0.0000001)
                                        ->placeholder('6.5244'),
                                    
                                    Forms\Components\TextInput::make('longitude')
                                        ->numeric()
                                        ->step(0.0000001)
                                        ->placeholder('3.3792'),
                                ])->columns(2)
                                ->collapsible(),
                        ]),

                    // ===== TAB 3: Contact (BRANCH-SPECIFIC) =====
                    Forms\Components\Tabs\Tab::make('Contact Information')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Forms\Components\Section::make('Branch Contact Details')
                                ->description('📞 Branch-specific contact information (overrides parent if provided)')
                                ->schema([
                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->maxLength(255)
                                        ->helperText('Branch email (leave empty to use parent business email)'),
                                    
                                    Forms\Components\TextInput::make('phone')
                                        ->tel()
                                        ->maxLength(20)
                                        ->helperText('Branch phone number'),
                                    
                                    Forms\Components\TextInput::make('whatsapp')
                                        ->tel()
                                        ->maxLength(20)
                                        ->prefix('+234')
                                        ->helperText('Branch WhatsApp number'),
                                ])->columns(3),
                        ]),

                    // ===== TAB 4: Business Hours (INHERITED, CAN OVERRIDE) =====
                    Forms\Components\Tabs\Tab::make('Business Hours')
                        ->icon('heroicon-o-clock')
                        ->badge('Inherited')
                        ->badgeColor('info')
                        ->schema([
                            Forms\Components\Section::make('Operating Hours')
                                ->description('⏰ Inherited from parent business but can be customized for this branch')
                                ->schema([
                                    Forms\Components\Placeholder::make('hours_note')
                                        ->label('')
                                        ->content('💡 These hours are pre-filled from the parent business. You can modify them if this branch has different hours.')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Repeater::make('business_hours')
                                        ->label('Operating Hours')
                                        ->schema([
                                            Forms\Components\Select::make('day')
                                                ->options([
                                                    'monday' => 'Monday',
                                                    'tuesday' => 'Tuesday',
                                                    'wednesday' => 'Wednesday',
                                                    'thursday' => 'Thursday',
                                                    'friday' => 'Friday',
                                                    'saturday' => 'Saturday',
                                                    'sunday' => 'Sunday',
                                                ])
                                                ->required()
                                                ->distinct(),
                                            
                                            Forms\Components\TimePicker::make('open')
                                                ->label('Opening Time')
                                                ->seconds(false),
                                            
                                            Forms\Components\TimePicker::make('close')
                                                ->label('Closing Time')
                                                ->seconds(false),
                                            
                                            Forms\Components\Toggle::make('closed')
                                                ->label('Closed')
                                                ->inline(false),
                                        ])
                                        ->columns(4)
                                        ->defaultItems(0)
                                        ->collapsible()
                                        ->itemLabel(fn (array $state): ?string => 
                                            $state['day'] ? ucfirst($state['day']) : null
                                        )
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ===== TAB 5: Amenities & Payments (INHERITED, CAN EXTEND) =====
                    Forms\Components\Tabs\Tab::make('Amenities & Payments')
                        ->icon('heroicon-o-sparkles')
                        ->badge('Can Customize')
                        ->badgeColor('success')
                        ->schema([
                            Forms\Components\Section::make('Payment Methods')
                                ->description('💳 Inherited from parent, can add branch-specific methods')
                                ->schema([
                                    Forms\Components\Select::make('payment_methods')
                                        ->relationship('paymentMethods', 'name', fn (Builder $query) => 
                                            $query->where('is_active', true)->orderBy('name')
                                        )
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->helperText('Pre-filled from parent business. You can add or remove methods.')
                                        ->columnSpanFull(),
                                ]),
                            
                            Forms\Components\Section::make('Branch Amenities')
                                ->description('✨ Branch-specific amenities (can differ from parent)')
                                ->schema([
                                    Forms\Components\Select::make('amenities')
                                        ->relationship('amenities', 'name', fn (Builder $query) => 
                                            $query->where('is_active', true)->orderBy('order')->orderBy('name')
                                        )
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->helperText('Select amenities available at THIS branch location')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ===== TAB 6: Gallery (BRANCH-SPECIFIC) =====
                    Forms\Components\Tabs\Tab::make('Photos')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\Section::make('Branch Gallery')
                                ->description('📷 Upload photos of THIS branch location')
                                ->schema([
                                    Forms\Components\FileUpload::make('gallery')
                                        ->label('Branch Photos')
                                        ->image()
                                        ->multiple()
                                        ->directory('branch-gallery')
                                        ->maxSize(5120)
                                        ->maxFiles(10)
                                        ->imageEditor()
                                        ->reorderable()
                                        ->helperText('Upload photos specific to this branch (interior, exterior, etc.)')
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ===== TAB 7: SEO & Settings =====
                    Forms\Components\Tabs\Tab::make('SEO & Settings')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Forms\Components\Section::make('SEO Settings')
                                ->description('🔍 Control how search engines index this branch')
                                ->schema([
                                    Forms\Components\Select::make('canonical_strategy')
                                        ->label('Indexing Strategy')
                                        ->options([
                                            'self' => '✅ Index Separately (Recommended for unique branches)',
                                            'parent' => '🔗 Point to Parent Business (Use if content is similar)',
                                        ])
                                        ->default('self')
                                        ->required()
                                        ->helperText('Choose "Index Separately" if this branch has unique content, reviews, and photos'),
                                    
                                    Forms\Components\TextInput::make('meta_title')
                                        ->label('Meta Title (Optional)')
                                        ->maxLength(60)
                                        ->helperText('Custom page title. Auto-generated if empty.')
                                        ->placeholder('Auto: Branch Title | City, State')
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\Textarea::make('meta_description')
                                        ->label('Meta Description (Optional)')
                                        ->rows(3)
                                        ->maxLength(160)
                                        ->helperText('Custom description. Auto-generated if empty.')
                                        ->columnSpanFull(),
                                ])->columns(2),
                            
                            Forms\Components\Section::make('Branch Status')
                                ->schema([
                                    Forms\Components\Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true)
                                        ->helperText('Is this branch currently operating?'),
                                    
                                    Forms\Components\TextInput::make('order')
                                        ->numeric()
                                        ->default(0)
                                        ->helperText('Display order (lower = appears first)'),
                                ])->columns(2),
                            
                            Forms\Components\Section::make('Content Quality')
                                ->description('📊 SEO & uniqueness metrics')
                                ->schema([
                                    Forms\Components\TextInput::make('content_similarity_score')
                                        ->label('Content Similarity to Parent')
                                        ->disabled()
                                        ->suffix('%')
                                        ->helperText(function ($get) {
                                            $score = $get('content_similarity_score');
                                            if (!$score) return 'Will calculate when you add description';
                                            if ($score > 70) return '🔴 TOO SIMILAR! Rewrite to be more unique.';
                                            if ($score > 30) return '🟡 Moderately similar. Add more unique details.';
                                            return '🟢 Great! Content is unique.';
                                        }),
                                    
                                    Forms\Components\Toggle::make('has_unique_content')
                                        ->label('Has Unique Content')
                                        ->disabled()
                                        ->helperText('Automatically determined based on content similarity'),
                                ])->columns(2)
                                ->collapsible()
                                ->collapsed(),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    /**
     * Update branch title and slug based on business name and city
     */
    protected static function updateBranchTitleAndSlug(Forms\Get $get, Forms\Set $set, Business $business): void
    {
        $city = $get('city');
        
        if ($city) {
            $branchTitle = $business->business_name . ' ' . $city;
        } else {
            $branchTitle = $business->business_name;
        }
        
        $set('branch_title', $branchTitle);
        $set('slug', Str::slug($branchTitle));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch_title')
                    ->label('Branch Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->business->business_name)
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Parent Business')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->state),
                
                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->toggleable()
                    ->placeholder('Use parent'),
                
                Tables\Columns\TextColumn::make('products_count')
                    ->counts('products')
                    ->label('Products')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'No ratings')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Parent Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
                
                Tables\Filters\SelectFilter::make('state')
                    ->options(function () {
                        return BusinessBranch::query()
                            ->distinct()
                            ->pluck('state', 'state')
                            ->toArray();
                    })
                    ->searchable(),
                
                Tables\Filters\Filter::make('has_products')
                    ->label('Has Products')
                    ->query(fn (Builder $query) => $query->has('products')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Branch-specific relationships (NOT inherited)
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\OfficialsRelationManager::class,
            RelationManagers\SocialAccountsRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\LeadsRelationManager::class,
            RelationManagers\ManagersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinessBranches::route('/'),
            'create' => Pages\CreateBusinessBranch::route('/create'),
            'edit' => Pages\EditBusinessBranch::route('/{record}/edit'),
            'view' => Pages\ViewBusinessBranch::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getGloballySearchableAttributes(): array
    {
        return ['branch_title', 'business.business_name', 'city', 'area', 'state', 'address'];
    }
}