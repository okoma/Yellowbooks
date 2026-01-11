<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource/RelationManagers/BranchesRelationManager.php
// Manage business branches (locations)
// ============================================

namespace App\Filament\Business\Resources\BusinessResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BranchesRelationManager extends RelationManager
{
    protected static string $relationship = 'branches';
    
    protected static ?string $title = 'Branches';
    
    protected static ?string $icon = 'heroicon-o-map-pin';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Branch Information')
                    ->schema([
                        Forms\Components\TextInput::make('branch_title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Forms\Set $set, $operation) {
                                if ($operation === 'create') {
                                    $businessName = $this->getOwnerRecord()->business_name;
                                    $set('slug', \Illuminate\Support\Str::slug($businessName . ' ' . $state));
                                }
                            }),
                        
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        
                        Forms\Components\Toggle::make('is_main_branch')
                            ->label('Main Branch')
                            ->helperText('Is this the primary/head office location?'),
                        
                        Forms\Components\Textarea::make('branch_description')
                            ->rows(4)
                            ->maxLength(1000)
                            ->helperText('Optional: Describe what makes this location unique'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Location Details')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->maxLength(100),
                        
                        Forms\Components\TextInput::make('area')
                            ->maxLength(100),
                        
                        Forms\Components\TextInput::make('state')
                            ->required()
                            ->maxLength(100),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->numeric()
                                    ->step(0.0000001),
                                
                                Forms\Components\TextInput::make('longitude')
                                    ->numeric()
                                    ->step(0.0000001),
                            ]),
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
                            ->maxLength(20),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Business Hours')
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
                                
                                Forms\Components\TimePicker::make('open'),
                                Forms\Components\TimePicker::make('close'),
                                
                                Forms\Components\Toggle::make('closed')
                                    ->label('Closed'),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['day'] ?? null),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Section::make('Media')
                    ->schema([
                        Forms\Components\FileUpload::make('gallery')
                            ->image()
                            ->directory('branch-gallery')
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(3072)
                            ->imageEditor()
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
                    ->weight('bold'),
                
                Tables\Columns\IconColumn::make('is_main_branch')
                    ->boolean()
                    ->label('Main'),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->icon('heroicon-o-map-pin'),
                
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-o-phone')
                    ->copyable(),
                
                Tables\Columns\TextColumn::make('rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐'),
                
                Tables\Columns\TextColumn::make('reviews_count')
                    ->counts('reviews')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('leads_count')
                    ->counts('leads')
                    ->badge()
                    ->color('warning'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_main_branch')
                    ->label('Main Branch'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Branch'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}