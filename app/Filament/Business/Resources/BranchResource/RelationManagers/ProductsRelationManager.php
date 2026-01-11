<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/RelationManagers/ProductsRelationManager.php
// Same as Business but for branches
// ============================================

namespace App\Filament\Business\Resources\BranchResource\RelationManagers;

// This is identical to BusinessResource\RelationManagers\ProductsRelationManager
// Just copy the entire ProductsRelationManager from Business and change the namespace

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    
    protected static ?string $title = 'Products / Services';
    
    protected static ?string $icon = 'heroicon-o-shopping-bag';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Product Information')
                    ->schema([
                        Forms\Components\TextInput::make('header_title')
                            ->label('Category/Section')
                            ->required()
                            ->maxLength(255),
                        
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
                            ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'none'),
                    ])
                    ->columns(3),
                
                Forms\Components\Section::make('Availability')
                    ->schema([
                        Forms\Components\Toggle::make('is_available')
                            ->default(true),
                        
                        Forms\Components\TextInput::make('order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->defaultSort('order', 'asc')
            ->reorderable('order')
            ->columns([
                Tables\Columns\ImageColumn::make('image')->circular(),
                Tables\Columns\TextColumn::make('header_title')->badge()->color('info'),
                Tables\Columns\TextColumn::make('name')->weight('bold'),
                Tables\Columns\TextColumn::make('price')->money('NGN'),
                Tables\Columns\TextColumn::make('final_price')->money('NGN'),
                Tables\Columns\IconColumn::make('is_available')->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}