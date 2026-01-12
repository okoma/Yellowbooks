<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/CreateBusiness.php
// FULLY CORRECTED VERSION - All Syntax Errors Fixed
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\Pages;

use App\Filament\Business\Resources\BusinessResource;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Amenity;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Form;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;

class CreateBusiness extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = BusinessResource::class;
    
    protected function getSteps(): array
    {
        return [
            // Step 1: Basic Information
            Wizard\Step::make('Basic Information')
                ->description('Enter your business name and type')
                ->schema([
                    Forms\Components\TextInput::make('business_name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                            $set('slug', \Illuminate\Support\Str::slug($state))
                        ),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('URL-friendly version of your business name (auto-generated)'),
                    
                    Forms\Components\Select::make('business_type_id')
                        ->label('Business Type')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship('businessType', 'name')
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('categories', [])),
                    
                    Forms\Components\Select::make('categories')
                        ->label('Categories')
                        ->multiple()
                        ->options(function (Forms\Get $get) {
                            $businessTypeId = $get('business_type_id');
                            if (!$businessTypeId) return [];
                            
                            return Category::where('business_type_id', $businessTypeId)
                                ->where('is_active', true)
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Forms\Get $get): bool => !$get('business_type_id'))
                        ->helperText('Select one or more categories for your business (select business type first)'),
                    
                    Forms\Components\Textarea::make('description')
                        ->required()
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Describe your business in detail')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 2: Location & Contact
            Wizard\Step::make('Location & Contact')
                ->description('Where is your business located?')
                ->schema([
                    // ✅ FIXED: Changed state_id to state_location_id
                    Forms\Components\Select::make('state_location_id')
                        ->label('State')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return Location::where('type', 'state')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            $set('city_location_id', null);
                            // Auto-fill state name
                            $stateName = Location::find($state)?->name;
                            $set('state', $stateName);
                        }),
                    
                    // ✅ FIXED: Changed city_id to city_location_id
                    Forms\Components\Select::make('city_location_id')
                        ->label('City')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function (Forms\Get $get) {
                            $stateId = $get('state_location_id');
                            if (!$stateId) return [];
                            
                            return Location::where('type', 'city')
                                ->where('parent_id', $stateId)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->disabled(fn (Forms\Get $get): bool => !$get('state_location_id'))
                        ->helperText('Select state first')
                        ->live()
                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                            // Auto-fill city name
                            $cityName = Location::find($state)?->name;
                            $set('city', $cityName);
                        }),
                    
                    // ✅ ADDED: Hidden fields for state and city names (auto-filled)
                    Forms\Components\Hidden::make('state'),
                    Forms\Components\Hidden::make('city'),
                    
                    Forms\Components\TextInput::make('address')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->helperText('Street address or building name'),
                    
                    Forms\Components\TextInput::make('area')
                        ->label('Area/Neighborhood')
                        ->maxLength(100)
                        ->helperText('Optional: Specific area or neighborhood'),
                    
Forms\Components\Grid::make(2)
    ->schema([
        Forms\Components\TextInput::make('latitude')
            ->numeric()
            ->step(0.0000001)
            ->minValue(-90)
            ->maxValue(90)
            ->helperText('Latitude must be between -90 and 90'),
        
        Forms\Components\TextInput::make('longitude')
            ->numeric()
            ->step(0.0000001)
            ->minValue(-180)
            ->maxValue(180)
            ->helperText('Longitude must be between -180 and 180'),
    ]),

                    
                    Forms\Components\Section::make('Contact Information')
                        ->schema([
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(20),
                            
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('whatsapp')
                                ->tel()
                                ->maxLength(20),
                            
                            Forms\Components\TextInput::make('website')
                                ->url()
                                ->maxLength(255),
                            
                            Forms\Components\Textarea::make('whatsapp_message')
                                ->maxLength(500)
                                ->helperText('Pre-filled message when customers click WhatsApp')
                                ->rows(3),
                        ])
                        ->columns(2),
                ])
                ->columns(2),
            
            // Step 3: Business Hours (Optional)
            Wizard\Step::make('Business Hours')
                ->description('Set your operating hours (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Repeater::make('business_hours_temp')
                        ->label('Business Hours')
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
                                ->required(),
                            
                            Forms\Components\TimePicker::make('open')
                                ->required(),
                            
                            Forms\Components\TimePicker::make('close')
                                ->required(),
                            
                            Forms\Components\Toggle::make('closed')
                                ->label('Closed this day')
                                ->default(false),
                        ])
                        ->columns(4)
                        ->defaultItems(0)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => ucfirst($state['day'] ?? 'New Day'))
                        ->helperText('Add your operating hours for each day'),
                ])
                ->columns(1),
            
            // Step 4: Media & Branding (Optional)
            Wizard\Step::make('Media & Branding')
                ->description('Upload your business images (optional - you can skip this step)')
                ->schema([
                    Forms\Components\FileUpload::make('logo')
                        ->image()
                        ->directory('business-logos')
                        ->maxSize(2048)
                        ->imageEditor()
                        ->helperText('Square logo works best'),
                    
                    Forms\Components\FileUpload::make('cover_photo')
                        ->image()
                        ->directory('business-covers')
                        ->maxSize(5120)
                        ->imageEditor()
                        ->helperText('Wide banner image'),
                    
                    Forms\Components\FileUpload::make('gallery')
                        ->image()
                        ->directory('business-gallery')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(3072)
                        ->imageEditor()
                        ->helperText('Upload up to 10 images')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 5: Features & Amenities (Optional)
            Wizard\Step::make('Features & Amenities')
                ->description('What facilities do you offer? (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Select::make('payment_methods')
                        ->label('Payment Methods Accepted')
                        ->multiple()
                        ->options(PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Select all payment methods you accept'),
                    
                    Forms\Components\Select::make('amenities')
                        ->label('Amenities & Features')
                        ->multiple()
                        ->options(Amenity::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->helperText('Select all amenities available at your business'),
                ])
                ->columns(1),
            
            // Step 6: Legal Information (Optional)
            Wizard\Step::make('Legal Information')
                ->description('Business registration details (optional - you can skip this step)')
                ->schema([
                    Forms\Components\TextInput::make('registration_number')
                        ->label('CAC/RC Number')
                        ->maxLength(50)
                        ->helperText('Business registration number'),
                    
                    Forms\Components\Select::make('entity_type')
                        ->options([
                            'Sole Proprietorship' => 'Sole Proprietorship',
                            'Partnership' => 'Partnership',
                            'Limited Liability Company (LLC)' => 'Limited Liability Company (LLC)',
                            'Corporation' => 'Corporation',
                            'Non-Profit' => 'Non-Profit',
                        ]),
                    
                    Forms\Components\TextInput::make('years_in_business')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->helperText('How many years have you been operating?'),
                ])
                ->columns(3),
          
            // Step 7: SEO Settings (Optional)
            Wizard\Step::make('SEO Settings')
                ->description('Search engine optimization (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Select::make('canonical_strategy')
                        ->label('Indexing Strategy')
                        ->options([
                            'self' => 'Index Separately (Unique business with own SEO)',
                            'parent' => 'Standard Business',
                        ])
                        ->default('self')
                        ->required()
                        ->helperText('Most businesses should use "Index Separately"'),
                    
                    Forms\Components\TextInput::make('meta_title')
                        ->maxLength(255)
                        ->helperText('Custom page title (auto-generated if empty)'),
                    
                    Forms\Components\Textarea::make('meta_description')
                        ->maxLength(255)
                        ->rows(3)
                        ->helperText('Custom meta description (auto-generated if empty)'),
                    
                    Forms\Components\TagsInput::make('unique_features')
                        ->helperText('What makes your business unique? (e.g., "24/7 Service", "Award Winning")')
                        ->placeholder('Add unique features'),
                    
                    Forms\Components\Textarea::make('nearby_landmarks')
                        ->rows(3)
                        ->helperText('Mention nearby landmarks to help customers find you'),
                ])
                ->columns(1),
        ];
    }
    
    // ✅ FIXED: Transform business_hours and handle relationships
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set ownership
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending_review';
        $data['is_claimed'] = true;
        $data['claimed_by'] = Auth::id();
        
        // ✅ FIXED: Transform business_hours from repeater to keyed array
        if (isset($data['business_hours_temp']) && is_array($data['business_hours_temp'])) {
            $businessHours = [];
            foreach ($data['business_hours_temp'] as $hours) {
                if (isset($hours['day'])) {
                    $businessHours[$hours['day']] = [
                        'open' => $hours['open'] ?? null,
                        'close' => $hours['close'] ?? null,
                        'closed' => $hours['closed'] ?? false,
                    ];
                }
            }
            $data['business_hours'] = $businessHours;
            unset($data['business_hours_temp']);
        }
        
        // ✅ FIXED: Extract relationship data before creation
        $categories = $data['categories'] ?? [];
        $paymentMethods = $data['payment_methods'] ?? [];
        $amenities = $data['amenities'] ?? [];
        
        // Remove from data array (will be synced after creation)
        unset($data['categories'], $data['payment_methods'], $data['amenities']);
        
        // Store for after creation hook
        $this->categoriesData = $categories;
        $this->paymentMethodsData = $paymentMethods;
        $this->amenitiesData = $amenities;
        
        return $data;
    }
    
    // ✅ FIXED: Sync relationships after business is created
    protected function afterCreate(): void
    {
        $business = $this->record;
        
        // Sync categories
        if (!empty($this->categoriesData)) {
            $business->categories()->sync($this->categoriesData);
        }
        
        // Sync payment methods
        if (!empty($this->paymentMethodsData)) {
            $business->paymentMethods()->sync($this->paymentMethodsData);
        }
        
        // Sync amenities
        if (!empty($this->amenitiesData)) {
            $business->amenities()->sync($this->amenitiesData);
        }
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Business created successfully! It will be reviewed by our team.';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    // ✅ ADDED: Store relationship data temporarily
    protected array $categoriesData = [];
    protected array $paymentMethodsData = [];
    protected array $amenitiesData = [];
}
