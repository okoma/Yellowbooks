<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/Pages/CreateBranch.php
// Wizard-based branch creation
// ============================================

namespace App\Filament\Business\Resources\BranchResource\Pages;

use App\Filament\Business\Resources\BranchResource;
use App\Models\Amenity;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\CreateRecord\Concerns\HasWizard;
use Illuminate\Support\Facades\Auth;

class CreateBranch extends CreateRecord
{
    use HasWizard;
    
    protected static string $resource = BranchResource::class;
    
    protected function getSteps(): array
    {
        return [
            // Step 1: Select Business & Location
            Wizard\Step::make('Select Business & Location')
                ->description('Choose your business and where this branch is located')
                ->schema([
                    Forms\Components\Select::make('business_id')
                        ->label('Business')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship(
                            'business',
                            'business_name',
                            fn($query) => $query->where('user_id', Auth::id())
                        )
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // Auto-generate branch title when business is selected
                            $businessId = $state;
                            $cityId = $get('city_id');
                            
                            if ($businessId) {
                                $business = \App\Models\Business::find($businessId);
                                if ($business) {
                                    if ($cityId) {
                                        $city = \App\Models\Location::find($cityId);
                                        $branchTitle = $business->business_name . ' - ' . $city->name;
                                    } else {
                                        $branchTitle = $business->business_name;
                                    }
                                    $set('branch_title', $branchTitle);
                                    $set('slug', \Illuminate\Support\Str::slug($branchTitle));
                                }
                            }
                        })
                        ->helperText('Select the parent business for this branch')
                        ->columnSpanFull(),
                    
                    Forms\Components\Select::make('state_id')
                        ->label('State')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function () {
                            return \App\Models\Location::where('type', 'state')
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('city_id', null)),
                    
                    Forms\Components\Select::make('city_id')
                        ->label('City')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function (Forms\Get $get) {
                            $stateId = $get('state_id');
                            if (!$stateId) return [];
                            
                            return \App\Models\Location::where('type', 'city')
                                ->where('parent_id', $stateId)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->disabled(fn (Forms\Get $get): bool => !$get('state_id'))
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            // Auto-generate branch title with city when city is selected
                            $businessId = $get('business_id');
                            $cityId = $state;
                            
                            if ($businessId && $cityId) {
                                $business = \App\Models\Business::find($businessId);
                                $city = \App\Models\Location::find($cityId);
                                
                                if ($business && $city) {
                                    $branchTitle = $business->business_name . ' - ' . $city->name;
                                    $set('branch_title', $branchTitle);
                                    $set('slug', \Illuminate\Support\Str::slug($branchTitle));
                                }
                            }
                        })
                        ->helperText('Select state first'),
                ])
                ->columns(2),
            
            // Step 2: Branch Information
            Wizard\Step::make('Branch Information')
                ->description('Review auto-generated name or describe this location')
                ->schema([
                    Forms\Components\TextInput::make('branch_title')
                        ->label('Branch Title')
                        ->required()
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated from business name and city (go back to change)'),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated URL (go back to change business or city)'),
                    
                    Forms\Components\Toggle::make('is_main_branch')
                        ->label('This is the main/head office')
                        ->helperText('Is this your primary location?'),
                    
                    Forms\Components\Textarea::make('branch_description')
                        ->label('Branch Description')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Describe what makes this location unique')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 3: Address & Contact
            Wizard\Step::make('Address & Contact')
                ->description('Complete address and contact details')
                ->schema([
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
                                ->helperText('Optional: For map display'),
                            
                            Forms\Components\TextInput::make('longitude')
                                ->numeric()
                                ->step(0.0000001)
                                ->helperText('Optional: For map display'),
                        ]),
                    
                    Forms\Components\Textarea::make('nearby_landmarks')
                        ->rows(2)
                        ->maxLength(255)
                        ->helperText('Optional: What landmarks are nearby? (e.g., "Next to City Mall")')
                        ->columnSpanFull(),
                    
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
                        ])
                        ->columns(3)
                        ->collapsible(),
                ])
                ->columns(2),
            
            // Step 4: Business Hours (Optional)
            Wizard\Step::make('Business Hours')
                ->description('Set operating hours for this branch (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Repeater::make('business_hours')
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
                        ->itemLabel(fn (array $state): ?string => $state['day'] ?? null),
                ])
                ->columns(1),
            
            // Step 5: Media & Branding (Optional)
            Wizard\Step::make('Media & Branding')
                ->description('Upload branch images (optional - you can skip this step)')
                ->schema([
                    Forms\Components\FileUpload::make('gallery')
                        ->image()
                        ->directory('branch-gallery')
                        ->multiple()
                        ->maxFiles(10)
                        ->maxSize(3072)
                        ->imageEditor()
                        ->helperText('Upload up to 10 photos of this location')
                        ->columnSpanFull(),
                ])
                ->columns(1),
            
            // Step 6: Features & Amenities (Optional)
            Wizard\Step::make('Features & Amenities')
                ->description('What facilities does this branch offer? (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Select::make('payment_methods')
                        ->label('Payment Methods Accepted')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->relationship('paymentMethods', 'name')
                        ->options(PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                        ->helperText('Select all payment methods this branch accepts'),
                    
                    Forms\Components\Select::make('amenities')
                        ->label('Amenities & Features')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->relationship('amenities', 'name')
                        ->options(Amenity::where('is_active', true)->pluck('name', 'id'))
                        ->helperText('Select all amenities available at this branch'),
                    
                    Forms\Components\TagsInput::make('unique_features')
                        ->helperText('What makes this branch unique? (e.g., "Drive-thru", "Rooftop seating")')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 7: SEO Settings (Optional)
            Wizard\Step::make('SEO Settings')
                ->description('Search engine optimization (optional - you can skip this step)')
                ->schema([
                    Forms\Components\Select::make('canonical_strategy')
                        ->label('Indexing Strategy')
                        ->options([
                            'self' => 'Index Separately (Unique branch with own SEO)',
                            'parent' => 'Point to Parent Business (Standard branch)',
                        ])
                        ->default('parent')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Default: Points to parent business. Contact admin to request separate indexing for unique branches.'),
                    
                    Forms\Components\TextInput::make('meta_title')
                        ->maxLength(255)
                        ->helperText('Custom page title (auto-generated if empty)'),
                    
                    Forms\Components\Textarea::make('meta_description')
                        ->maxLength(255)
                        ->rows(3)
                        ->helperText('Custom meta description (auto-generated if empty)'),
                ])
                ->columns(1),
        ];
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_active'] = true;
        return $data;
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Branch created successfully!';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}