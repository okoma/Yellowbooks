<?php
// ============================================
// app/Filament/Admin/Resources/BusinessResource/RelationManagers/BranchesRelationManager.php
// Manage branches directly from business edit page - WITH LOCATION DROPDOWNS
// ============================================

namespace App\Filament\Admin\Resources\BusinessResource\RelationManagers;

use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';
    protected static ?string $title = 'Business Branches';
    protected static ?string $icon = 'heroicon-o-map-pin';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Location Details')
                ->schema([
                    Forms\Components\Textarea::make('address')
                        ->required()
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Full street address')
                        ->columnSpanFull(),
                    
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
                            // Reset city when state changes
                            $set('city_location_id', null);
                            
                            // Update state name for storage
                            if ($state) {
                                $location = Location::find($state);
                                $set('state', $location?->name);
                            }
                        })
                        ->helperText('Select the state'),
                    
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
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get, $record) {
                            if ($state) {
                                $location = Location::find($state);
                                $cityName = $location?->name;
                                $set('city', $cityName);
                                
                                // Auto-generate branch_title with city appended
                                if ($cityName) {
                                    $business = $this->getOwnerRecord();
                                    if ($business) {
                                        // ALL branches get city appended
                                        $branchTitle = $business->business_name . ' ' . $cityName;
                                        $set('branch_title', $branchTitle);
                                        
                                        // Auto-generate slug
                                        $set('slug', Str::slug($branchTitle));
                                    }
                                }
                            }
                        })
                        ->helperText('Select the city (state must be selected first)'),
                    
                    // Hidden fields to store the actual state and city names
                    Forms\Components\Hidden::make('state'),
                    Forms\Components\Hidden::make('city'),
                    
                    Forms\Components\TextInput::make('area')
                        ->maxLength(100)
                        ->helperText('Area/District/LGA'),
                    
                    Forms\Components\TextInput::make('latitude')
                        ->numeric()
                        ->step(0.0000001)
                        ->helperText('For map display'),
                    
                    Forms\Components\TextInput::make('longitude')
                        ->numeric()
                        ->step(0.0000001)
                        ->helperText('For map display'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Branch Information')
                ->schema([
                    Forms\Components\TextInput::make('branch_title')
                        ->label('Branch Name')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Auto-generated: Business Name + City')
                        ->columnSpan(1),
                    
                    Forms\Components\TextInput::make('slug')
                        ->label('URL Slug')
                        ->disabled()
                        ->dehydrated()
                        ->prefix(fn () => url('/') . '/')
                        ->helperText('Auto-generated URL identifier')
                        ->columnSpan(1),
                    
                    Forms\Components\Textarea::make('branch_description')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
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
                        ->maxLength(20)
                        ->prefix('+234'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Settings')
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),
                    
                    Forms\Components\TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Display order (lower numbers appear first)'),
                ])
                ->columns(2),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('branch_title')
            ->columns([
                Tables\Columns\TextColumn::make('branch_title')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->city . ', ' . $record->state)
                    ->badge(fn ($record) => $record->is_main_branch ? 'MAIN' : null)
                    ->color(fn ($record) => $record->is_main_branch ? 'success' : 'gray'),
                
                Tables\Columns\IconColumn::make('is_main_branch')
                    ->boolean()
                    ->label('Main')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-m-phone')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . ' ⭐' : 'No ratings')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviews_count')
                    ->label('Reviews')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->label('Views')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_main_branch')
                    ->label('Main Branch')
                    ->placeholder('All branches')
                    ->trueLabel('Main branch only')
                    ->falseLabel('Additional branches'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $business = $this->getOwnerRecord();
                        
                        // Determine if this is the first branch (main branch)
                        $existingBranchesCount = $business->branches()->count();
                        $isFirstBranch = $existingBranchesCount === 0;
                        
                        // Set is_main_branch automatically
                        $data['is_main_branch'] = $isFirstBranch;
                        
                        // Ensure branch_title is generated with city appended
                        if (empty($data['branch_title']) && !empty($data['city'])) {
                            $data['branch_title'] = $business->business_name . ' ' . $data['city'];
                        }
                        
                        // Auto-generate slug from branch_title
                        if (!empty($data['branch_title'])) {
                            $data['slug'] = Str::slug($data['branch_title']);
                        }
                        
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data, $record): array {
                        // Regenerate slug if branch_title changed
                        if (!empty($data['branch_title'])) {
                            $data['slug'] = Str::slug($data['branch_title']);
                        }
                        
                        // Keep is_main_branch unchanged during edit
                        $data['is_main_branch'] = $record->is_main_branch;
                        
                        return $data;
                    }),
                
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Prevent deletion of main branch if other branches exist
                        if ($record->is_main_branch) {
                            $business = $record->business;
                            $otherBranchesCount = $business->branches()->where('id', '!=', $record->id)->count();
                            
                            if ($otherBranchesCount > 0) {
                                throw new \Exception('Cannot delete the main branch while other branches exist. Delete other branches first or designate a new main branch.');
                            }
                        }
                    }),
                
                //Tables\Actions\Action::make('view_public')
                    //->label('View Public Page')
                    //->icon('heroicon-o-eye')
                    //->url(fn ($record) => route('branch.show', $record->slug))
                    //->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_active' => true])),
                    
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_active' => false])),
                ]),
            ])
            ->defaultSort('is_main_branch', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->orderBy('order', 'asc'));
    }
}