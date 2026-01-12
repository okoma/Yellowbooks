<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/Pages/EditBranch.php
// Edit branch with wizard
// ============================================

namespace App\Filament\Business\Resources\BranchResource\Pages;

use App\Filament\Business\Resources\BranchResource;
use App\Models\Amenity;
use App\Models\PaymentMethod;
use App\Models\Location;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Wizard;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\EditRecord\Concerns\HasWizard;

class EditBranch extends EditRecord
{
    use HasWizard;
    
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getSteps(): array
    {
        return [
            // Step 1: Business & Location
            Wizard\Step::make('Business & Location')
                ->description('Business and location information')
                ->schema([
                    Forms\Components\Select::make('business_id')
                        ->label('Business')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->relationship('business', 'business_name')
                        ->disabled()
                        ->helperText('Cannot change business for existing branch')
                        ->columnSpanFull(),
                    
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
                            $stateName = Location::find($state)?->name;
                            $set('state', $stateName);
                        }),
                    
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
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            $businessId = $get('business_id');
                            $cityId = $state;
                            
                            if ($businessId && $cityId) {
                                $business = \App\Models\Business::find($businessId);
                                $city = Location::find($cityId);
                                
                                if ($business && $city) {
                                    $branchTitle = $business->business_name . ' - ' . $city->name;
                                    $set('branch_title', $branchTitle);
                                    $set('slug', \Illuminate\Support\Str::slug($branchTitle));
                                    $set('city', $city->name);
                                }
                            }
                        })
                        ->helperText('Select state first'),
                    
                    Forms\Components\Hidden::make('state'),
                    Forms\Components\Hidden::make('city'),
                ])
                ->columns(2),
            
            // Step 2: Branch Information
            Wizard\Step::make('Branch Information')
                ->description('Review branch name and description')
                ->schema([
                    Forms\Components\TextInput::make('branch_title')
                        ->label('Branch Title')
                        ->required()
                        ->maxLength(255)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated from business name and city (contact admin to change)'),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated URL (contact admin to change)'),
                    
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
                ->description('Set operating hours for this branch (optional)')
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
            
            // Step 5: Media & Branding (Optional)
            Wizard\Step::make('Media & Branding')
                ->description('Upload branch images (optional)')
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
                ->description('What facilities does this branch offer? (optional)')
                ->schema([
                    Forms\Components\Select::make('payment_methods')
                        ->label('Payment Methods Accepted')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                        ->helperText('Select all payment methods this branch accepts'),
                    
                    Forms\Components\Select::make('amenities')
                        ->label('Amenities & Features')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(Amenity::where('is_active', true)->pluck('name', 'id'))
                        ->helperText('Select all amenities available at this branch'),
                    
                    Forms\Components\TagsInput::make('unique_features')
                        ->helperText('What makes this branch unique? (e.g., "Drive-thru", "Rooftop seating")')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            // Step 7: SEO Settings (Optional)
            Wizard\Step::make('SEO Settings')
                ->description('Search engine optimization (optional)')
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
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load relationship data
        $data['payment_methods'] = $this->record->paymentMethods()->pluck('payment_methods.id')->toArray();
        $data['amenities'] = $this->record->amenities()->pluck('amenities.id')->toArray();
        
        // Load state and city location IDs from text fields
        if (!empty($data['state'])) {
            $state = Location::where('type', 'state')
                ->where('name', $data['state'])
                ->first();
            $data['state_location_id'] = $state?->id;
        }
        
        if (!empty($data['city'])) {
            $city = Location::where('type', 'city')
                ->where('name', $data['city'])
                ->first();
            $data['city_location_id'] = $city?->id;
        }
        
        // Transform business_hours
        if (isset($data['business_hours']) && is_array($data['business_hours'])) {
            $businessHoursTemp = [];
            foreach ($data['business_hours'] as $day => $hours) {
                $businessHoursTemp[] = [
                    'day' => $day,
                    'open' => $hours['open'] ?? null,
                    'close' => $hours['close'] ?? null,
                    'closed' => $hours['closed'] ?? false,
                ];
            }
            $data['business_hours_temp'] = $businessHoursTemp;
        }
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Transform business_hours
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
        
        // Extract relationship data
        $paymentMethods = $data['payment_methods'] ?? [];
        $amenities = $data['amenities'] ?? [];
        
        unset($data['payment_methods'], $data['amenities']);
        
        $this->paymentMethodsData = $paymentMethods;
        $this->amenitiesData = $amenities;
        
        return $data;
    }
    
    protected function afterSave(): void
    {
        $branch = $this->record;
        
        if (isset($this->paymentMethodsData)) {
            $branch->paymentMethods()->sync($this->paymentMethodsData);
        }
        
        if (isset($this->amenitiesData)) {
            $branch->amenities()->sync($this->amenitiesData);
        }
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
    
    protected ?array $paymentMethodsData = null;
    protected ?array $amenitiesData = null;
}
