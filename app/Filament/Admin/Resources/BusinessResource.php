<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource.php
// REFACTORED VERSION - Business can now have direct relationships including LEADS
// Standalone businesses work WITHOUT needing branches
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BusinessResource\Pages;
use App\Filament\Admin\Resources\BusinessResource\RelationManagers;
use App\Models\Business;
use App\Models\Location;
use App\Models\User;
use App\Enums\UserRole;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Businesses';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make('Business Details')
                ->tabs([
                    // ===== TAB 1: Basic Information =====
                    Forms\Components\Tabs\Tab::make('Basic Information')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\Select::make('user_id')
                                        ->label('Business Owner')
                                        ->relationship('owner', 'name', fn (Builder $query) => 
                                            $query->whereIn('role', [UserRole::BUSINESS_OWNER->value, UserRole::ADMIN->value])
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->helperText('Select the user who owns this business'),
                                    
                                    Forms\Components\Select::make('business_type_id')
                                        ->label('Business Type')
                                        ->relationship('businessType', 'name', fn (Builder $query) => 
                                            $query->where('is_active', true)->orderBy('order')
                                        )
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set) {
                                            $set('categories', []);
                                        })
                                        ->helperText('Select the type of business'),
                                    
                                    Forms\Components\TextInput::make('business_name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                                            $set('slug', Str::slug($state))
                                        )
                                        ->columnSpanFull(),
                                    
                                    Forms\Components\TextInput::make('slug')
                                        ->required()
                                        ->unique(ignoreRecord: true)
                                        ->maxLength(255)
                                        ->helperText('Auto-generated URL-friendly identifier'),
                                    
                                    Forms\Components\Select::make('categories')
                                        ->relationship('categories', 'name', function (Builder $query, Forms\Get $get) {
                                            $businessTypeId = $get('business_type_id');
                                            if ($businessTypeId) {
                                                return $query->where('business_type_id', $businessTypeId)
                                                    ->where('is_active', true)
                                                    ->orderBy('order');
                                            }
                                            return $query->where('is_active', true)->orderBy('order');
                                        })
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->disabled(fn (Forms\Get $get) => !$get('business_type_id'))
                                        ->helperText('Select business categories')
                                        ->hint(fn (Forms\Get $get) => !$get('business_type_id') ? '⚠️ Please select Business Type first' : null)
                                        ->hintColor('warning'),
                                    
                                    Forms\Components\Textarea::make('description')
                                        ->rows(4)
                                        ->maxLength(2000)
                                        ->helperText('Detailed description of the business')
                                        ->columnSpanFull(),
                                ])->columns(2),
                        ]),

                    // ===== TAB 2: Legal & Registration =====
                    Forms\Components\Tabs\Tab::make('Legal Information')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\TextInput::make('registration_number')
                                        ->label('CAC/Registration Number')
                                        ->maxLength(255),
                                    
                                    Forms\Components\Select::make('entity_type')
                                        ->options([
                                            'sole_proprietorship' => 'Sole Proprietorship',
                                            'partnership' => 'Partnership',
                                            'limited_liability' => 'Limited Liability Company',
                                            'public_company' => 'Public Company',
                                            'ngo' => 'NGO/Non-Profit',
                                            'government' => 'Government Agency',
                                        ])
                                        ->native(false),
                                    
                                    Forms\Components\TextInput::make('years_in_business')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(200)
                                        ->suffix('years'),
                                ])->columns(3),
                        ]),

                    // ===== TAB 3: Contact Information =====
                    Forms\Components\Tabs\Tab::make('Contact Information')
                        ->icon('heroicon-o-phone')
                        ->schema([
                            Forms\Components\Section::make()
                                ->schema([
                                    Forms\Components\TextInput::make('email')
                                        ->email()
                                        ->maxLength(255),
                                    
                                    Forms\Components\TextInput::make('phone')
                                        ->tel()
                                        ->maxLength(20),
                                    
                                    Forms\Components\TextInput::make('whatsapp')
                                        ->tel()
                                        ->maxLength(20)
                                        ->prefix('+234'),
                                    
                                    Forms\Components\TextInput::make('website')
                                        ->url()
                                        ->maxLength(255)
                                        ->prefix('https://'),
                                    
                                    Forms\Components\Textarea::make('whatsapp_message')
                                        ->label('Default WhatsApp Message')
                                        ->rows(3)
                                        ->maxLength(500)
                                        ->columnSpanFull(),
                                ])->columns(2),
                        ]),

                    // ===== TAB 4: Location & Address =====
                    Forms\Components\Tabs\Tab::make('Location')
                        ->icon('heroicon-o-map-pin')
                        ->schema([
                            Forms\Components\Section::make('Physical Address')
                                ->description('Business location (for standalone businesses or main location)')
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
                                            if (!$stateId) return [];
                                            
                                            return Location::where('parent_id', $stateId)
                                                ->where('type', 'city')
                                                ->orderBy('name')
                                                ->pluck('name', 'id');
                                        })
                                        ->searchable()
                                        ->preload()
                                        ->disabled(fn (Forms\Get $get) => !$get('state_location_id'))
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            if ($state) {
                                                $location = Location::find($state);
                                                $set('city', $location?->name);
                                            }
                                        }),
                                    
                                    Forms\Components\Hidden::make('state'),
                                    Forms\Components\Hidden::make('city'),
                                    
                                    Forms\Components\TextInput::make('area')
                                        ->label('Area/Neighborhood')
                                        ->maxLength(255),
                                    
                                    Forms\Components\Textarea::make('address')
                                        ->label('Street Address')
                                        ->rows(2)
                                        ->maxLength(500)
                                        ->columnSpanFull(),
                                ])->columns(3),
                            
                            Forms\Components\Section::make('Map Coordinates')
                                ->schema([
                                    Forms\Components\TextInput::make('latitude')
                                        ->numeric()
                                        ->label('Latitude'),
                                    
                                    Forms\Components\TextInput::make('longitude')
                                        ->numeric()
                                        ->label('Longitude'),
                                ])->columns(2),
                            
                            Forms\Components\Section::make('Multi-Location Business')
                                ->description('💡 Info about branches')
                                ->schema([
                                    Forms\Components\Placeholder::make('branches_note')
                                        ->label('')
                                        ->content(function ($record) {
                                            if (!$record) {
                                                return '💡 After creating this business, you can add branches from the "Branches" tab';
                                            }
                                            
                                            $branchCount = $record->branches()->count();
                                            if ($branchCount === 0) {
                                                return '✅ This is a standalone single-location business. Products, officials, and leads are managed directly on this business.';
                                            }
                                            
                                            return "✅ This business has {$branchCount} branch(es). Each branch can have its own products, officials, and leads.";
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->collapsible()
                                ->collapsed(),
                        ]),

                    // ===== TAB 5: Business Hours =====
                    Forms\Components\Tabs\Tab::make('Business Hours')
                        ->icon('heroicon-o-clock')
                        ->schema([
                            Forms\Components\Section::make()
                                ->description('Operating hours (inherited by branches)')
                                ->schema([
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
                                ])->columns(1),
                        ]),

                    // ===== TAB 6: Features & Amenities =====
                    Forms\Components\Tabs\Tab::make('Features & Amenities')
                        ->icon('heroicon-o-sparkles')
                        ->schema([
                            Forms\Components\Section::make('Payment Methods')
                                ->schema([
                                    Forms\Components\Select::make('payment_methods')
                                        ->relationship('paymentMethods', 'name', fn (Builder $query) => 
                                            $query->where('is_active', true)->orderBy('name')
                                        )
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->columnSpanFull(),
                                ]),
                            
                            Forms\Components\Section::make('Business Amenities')
                                ->schema([
                                    Forms\Components\Select::make('amenities')
                                        ->relationship('amenities', 'name', fn (Builder $query) => 
                                            $query->where('is_active', true)->orderBy('order')->orderBy('name')
                                        )
                                        ->multiple()
                                        ->preload()
                                        ->searchable()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ===== TAB 7: Media =====
                    Forms\Components\Tabs\Tab::make('Media')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Forms\Components\Section::make('Primary Images')
                                ->schema([
                                    Forms\Components\FileUpload::make('logo')
                                        ->image()
                                        ->directory('business-logos')
                                        ->maxSize(2048)
                                        ->imageEditor(),
                                    
                                    Forms\Components\FileUpload::make('cover_photo')
                                        ->image()
                                        ->directory('business-covers')
                                        ->maxSize(5120)
                                        ->imageEditor(),
                                ])->columns(2),
                            
                            Forms\Components\Section::make('Gallery')
                                ->schema([
                                    Forms\Components\FileUpload::make('gallery')
                                        ->image()
                                        ->directory('business-gallery')
                                        ->multiple()
                                        ->maxFiles(10)
                                        ->maxSize(5120)
                                        ->imageEditor()
                                        ->reorderable()
                                        ->columnSpanFull(),
                                ]),
                        ]),

                    // ===== TAB 8: Status & Settings =====
                    Forms\Components\Tabs\Tab::make('Status & Settings')
                        ->icon('heroicon-o-cog')
                        ->schema([
                            Forms\Components\Section::make('Verification & Premium')
                                ->schema([
                                    Forms\Components\Toggle::make('is_claimed')
                                        ->label('Claimed')
                                        ->disabled(),
                                    
                                    Forms\Components\Select::make('claimed_by')
                                        ->label('Claimed By')
                                        ->relationship('claimedBy', 'name')
                                        ->disabled()
                                        ->visible(fn ($get) => $get('is_claimed')),
                                    
                                    Forms\Components\Toggle::make('is_verified')
                                        ->label('Verified'),
                                    
                                    Forms\Components\Select::make('verification_level')
                                        ->options([
                                            'none' => 'None',
                                            'basic' => 'Basic',
                                            'standard' => 'Standard',
                                            'premium' => 'Premium',
                                        ])
                                        ->native(false)
                                        ->visible(fn ($get) => $get('is_verified')),
                                    
                                    Forms\Components\TextInput::make('verification_score')
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->suffix('/ 100')
                                        ->disabled()
                                        ->visible(fn ($get) => $get('is_verified')),
                                ])->columns(3),

                            Forms\Components\Section::make('Premium Status')
                                ->schema([
                                    Forms\Components\Toggle::make('is_premium')
                                        ->label('Premium Business')
                                        ->live(),
                                    
                                    Forms\Components\DateTimePicker::make('premium_until')
                                        ->label('Premium Valid Until')
                                        ->visible(fn ($get) => $get('is_premium'))
                                        ->required(fn ($get) => $get('is_premium'))
                                        ->native(false),
                                ])->columns(2),

                            Forms\Components\Section::make('Business Status')
                                ->schema([
                                    Forms\Components\Select::make('status')
                                        ->options([
                                            'draft' => 'Draft',
                                            'pending_review' => 'Pending Review',
                                            'active' => 'Active',
                                            'suspended' => 'Suspended',
                                            'closed' => 'Closed',
                                        ])
                                        ->required()
                                        ->default('active')
                                        ->native(false),
                                ])->columns(1),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->business_name)),
                
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => Str::limit($record->description, 50))
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('businessType.name')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Multiple'),
                
                Tables\Columns\TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label('Branches')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'success' : 'gray')
                    ->formatStateUsing(fn ($state) => $state > 0 ? $state : 'Standalone'),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified')
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_premium')
                    ->boolean()
                    ->label('Premium')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'active',
                        'danger' => 'suspended',
                        'secondary' => 'closed',
                    ])
                    ->formatStateUsing(fn ($state) => str_replace('_', ' ', ucwords($state, '_'))),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('business_type')
                    ->relationship('businessType', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                        'closed' => 'Closed',
                    ])
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('has_branches')
                    ->label('Has Branches')
                    ->placeholder('All')
                    ->trueLabel('Multi-location')
                    ->falseLabel('Standalone')
                    ->queries(
                        true: fn (Builder $query) => $query->has('branches'),
                        false: fn (Builder $query) => $query->doesntHave('branches'),
                    ),
                
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified'),
                
                Tables\Filters\TernaryFilter::make('is_premium')
                    ->label('Premium'),
                
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Direct relationships (for standalone businesses)
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\OfficialsRelationManager::class,
            RelationManagers\SocialAccountsRelationManager::class,
            RelationManagers\LeadsRelationManager::class, // NEW: Business can have leads
            RelationManagers\ReviewsRelationManager::class,
            
            // Branch management (for multi-location businesses)
            RelationManagers\BranchesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
            'view' => Pages\ViewBusiness::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}