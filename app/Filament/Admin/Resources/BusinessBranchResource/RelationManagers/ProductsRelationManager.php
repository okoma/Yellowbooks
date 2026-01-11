<?php
// ============================================
// app/Filament/Admin/Resources/BusinessBranchResource/RelationManagers/ProductsRelationManager.php
// Location: app/Filament/Admin/Resources/BusinessBranchResource/RelationManagers/ProductsRelationManager.php
// Panel: Admin Panel
// Access: Admins, Moderators
// Purpose: Manage products/services at this branch
// ============================================

namespace App\Filament\Admin\Resources\BusinessBranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $title = 'Products & Services';
    protected static ?string $icon = 'heroicon-o-shopping-bag';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product Information')
                ->schema([
                    Forms\Components\TextInput::make('header_title')
                        ->label('Category/Header')
                        ->maxLength(255)
                        ->helperText('e.g., "Main Menu", "Services", "Appetizers"'),
                    
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                            $set('slug', Str::slug($state))
                        )
                        ->helperText('Product/service name'),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    
                    Forms\Components\Textarea::make('description')
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->options([
                            'NGN' => '₦ Nigerian Naira (NGN)',
                            'USD' => '$ US Dollar (USD)',
                            'EUR' => '€ Euro (EUR)',
                            'GBP' => '£ British Pound (GBP)',
                        ])
                        ->default('NGN')
                        ->required()
                        ->native(false),
                    
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->required()
                        ->prefix('₦')
                        ->step(0.01),
                    
                    Forms\Components\Select::make('discount_type')
                        ->options([
                            'none' => 'No Discount',
                            'percentage' => 'Percentage Off',
                            'fixed' => 'Fixed Amount Off',
                        ])
                        ->default('none')
                        ->live()
                        ->native(false),
                    
                    Forms\Components\TextInput::make('discount_value')
                        ->numeric()
                        ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'none')
                        ->required(fn (Forms\Get $get) => $get('discount_type') !== 'none')
                        ->suffix(fn (Forms\Get $get) => $get('discount_type') === 'percentage' ? '%' : '₦')
                        ->step(0.01),
                    
                    Forms\Components\TextInput::make('final_price')
                        ->numeric()
                        ->disabled()
                        ->dehydrated(false)
                        ->prefix('₦')
                        ->helperText('Auto-calculated based on discount'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Media & Settings')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->image()
                        ->directory('products')
                        ->maxSize(2048)
                        ->imageEditor(),
                    
                    Forms\Components\Toggle::make('is_available')
                        ->label('Available')
                        ->default(true)
                        ->helperText('Is this product currently available?'),
                    
                    Forms\Components\TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->helperText('Display order'),
                ])
                ->columns(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->defaultImageUrl('https://via.placeholder.com/100'),
                
                Tables\Columns\TextColumn::make('header_title')
                    ->label('Category')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('NGN')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('discount_type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->colors([
                        'gray' => 'none',
                        'success' => 'percentage',
                        'info' => 'fixed',
                    ])
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Final Price')
                    ->money('NGN')
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available'),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable(),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Availability'),
                
                Tables\Filters\SelectFilter::make('discount_type')
                    ->options([
                        'none' => 'No Discount',
                        'percentage' => 'Percentage Off',
                        'fixed' => 'Fixed Amount Off',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('toggle_availability')
                    ->label(fn ($record) => $record->is_available ? 'Mark Unavailable' : 'Mark Available')
                    ->icon(fn ($record) => $record->is_available ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn ($record) => $record->is_available ? 'danger' : 'success')
                    ->action(fn ($record) => $record->update(['is_available' => !$record->is_available])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_available')
                        ->label('Mark Available')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(fn ($records) => $records->each->update(['is_available' => true])),
                    
                    Tables\Actions\BulkAction::make('mark_unavailable')
                        ->label('Mark Unavailable')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(fn ($records) => $records->each->update(['is_available' => false])),
                ]),
            ])
            ->reorderable('order');
    }
}