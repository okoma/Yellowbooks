<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/Pages/CreateBusiness.php
// FULLY CORRECTED VERSION
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
                    Forms\Components\Select::make('state_id')
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
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('city_id', null)),
                    
                    Forms\Components\Select::make('city_id')
                        ->label('City')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->options(function (Forms\Get $get) {
                            $stateId = $get('state_id');
                            if (!$stateId) return [];
                            
                            return Location::where('type', 'city')
                                ->where('parent_id', $stateId)
                                ->where('is_active', true)
                                ->orderBy('name')
                                ->pluck('name', 'id');
                        })
                        ->disabled(fn (Forms\Get $get): bool => !$get('state_id'))
                        ->helperText('Select state first'),
                    
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
                        ->relationship('paymentMethods', 'name')
                        ->options(PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    
                    Forms\Components\Select::make('amenities')
                        ->label('Amenities & Features')
                        ->multiple()
                        ->relationship('amenities', 'name')
                        ->options(Amenity::where('is_active', true)->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
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
                        ->helperText('How many years have you been operating?'),
                ])
                ->columns(3),
        ];
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = Auth::id();
        $data['status'] = 'pending_review';
        $data['is_claimed'] = true;
        $data['claimed_by'] = Auth::id();
        $data['claimed_at'] = now();
        
        return $data;
    }
    
    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Business created successfully! It will be reviewed by our team.';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}