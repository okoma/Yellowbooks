<?php
// ============================================
// app/Filament/Admin/Resources/ProductResource.php
// Location: app/Filament/Admin/Resources/ProductResource.php
// Panel: Admin Panel (/admin)
// Access: Admins, Moderators
// Purpose: Manage products/services/menu items (standalone businesses + branches)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Business;
use App\Models\BusinessBranch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\TrashedFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationLabel = 'Products';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 10;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Product Location')
                ->description('⚠️ Choose either Business (standalone) OR Branch (multi-location)')
                ->schema([
                    Forms\Components\Select::make('business_id')
                        ->label('Business')
                        ->options(fn () => Business::pluck('business_name', 'id'))
                        ->getOptionLabelUsing(fn ($value): ?string => Business::find($value)?->business_name)
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('business_branch_id', null))
                        ->helperText('Select to assign product directly to business (available at all locations)')
                        ->disabled(fn (Forms\Get $get) => filled($get('business_branch_id')))
                        ->required(fn (Forms\Get $get) => !filled($get('business_branch_id'))),

                    Forms\Components\Select::make('business_branch_id')
                        ->label('Business Branch')
                        ->options(fn () => 
                            BusinessBranch::pluck('branch_title', 'id')
                        )
                        ->getOptionLabelUsing(fn ($value): ?string => 
                            BusinessBranch::find($value)?->branch_title
                        )
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('business_id', null))
                        ->helperText('Select if product belongs to specific branch location only')
                        ->disabled(fn (Forms\Get $get) => filled($get('business_id')))
                        ->required(fn (Forms\Get $get) => !filled($get('business_id'))),
                    
                    Forms\Components\Placeholder::make('location_note')
                        ->label('')
                        ->content('💡 You must select EITHER a business OR a branch, not both.')
                        ->visible(fn (Forms\Get $get) => 
                            !filled($get('business_id')) && !filled($get('business_branch_id'))
                        )
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Product Details')
                ->schema([
                    Forms\Components\TextInput::make('header_title')
                        ->label('Category/Section Header')
                        ->maxLength(255)
                        ->helperText('e.g., "Main Courses", "Appetizers", "Desserts"')
                        ->placeholder('Optional grouping header'),
                    
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, Forms\Set $set) => 
                            $set('slug', Str::slug($state))
                        )
                        ->helperText('Product/Service name'),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->helperText('Auto-generated URL identifier')
                        ->disabled()
                        ->dehydrated(),
                    
                    Forms\Components\Textarea::make('description')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Detailed product description')
                        ->columnSpanFull(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Pricing')
                ->schema([
                    Forms\Components\Select::make('currency')
                        ->options([
                            'NGN' => '₦ Nigerian Naira',
                            'USD' => '$ US Dollar',
                            'EUR' => '€ Euro',
                            'GBP' => '£ British Pound',
                        ])
                        ->default('NGN')
                        ->required()
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\TextInput::make('price')
                        ->numeric()
                        ->required()
                        ->prefix(fn (Forms\Get $get) => match($get('currency')) {
                            'NGN' => '₦',
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            default => '₦'
                        })
                        ->step(0.01)
                        ->minValue(0)
                        ->live()
                        ->helperText('Original price'),
                    
                    Forms\Components\Select::make('discount_type')
                        ->options([
                            'none' => 'No Discount',
                            'percentage' => 'Percentage Off (%)',
                            'fixed' => 'Fixed Amount Off',
                        ])
                        ->default('none')
                        ->required()
                        ->native(false)
                        ->live(),
                    
                    Forms\Components\TextInput::make('discount_value')
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->suffix(fn (Forms\Get $get) => 
                            $get('discount_type') === 'percentage' ? '%' : ''
                        )
                        ->prefix(fn (Forms\Get $get) => 
                            $get('discount_type') === 'fixed' 
                                ? match($get('currency')) {
                                    'NGN' => '₦',
                                    'USD' => '$',
                                    'EUR' => '€',
                                    'GBP' => '£',
                                    default => '₦'
                                }
                                : ''
                        )
                        ->visible(fn (Forms\Get $get) => $get('discount_type') !== 'none')
                        ->required(fn (Forms\Get $get) => $get('discount_type') !== 'none')
                        ->helperText('Discount amount'),
                    
                    Forms\Components\TextInput::make('final_price')
                        ->label('Final Price (Calculated)')
                        ->numeric()
                        ->disabled()
                        ->prefix(fn (Forms\Get $get) => match($get('currency')) {
                            'NGN' => '₦',
                            'USD' => '$',
                            'EUR' => '€',
                            'GBP' => '£',
                            default => '₦'
                        })
                        ->helperText('Auto-calculated after discount')
                        ->dehydrated(false),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Media & Settings')
                ->schema([
                    Forms\Components\FileUpload::make('image')
                        ->image()
                        ->directory('product-images')
                        ->maxSize(5120)
                        ->imageEditor()
                        ->helperText('Product image (Max: 5MB)'),
                    
                    Forms\Components\Toggle::make('is_available')
                        ->label('Available')
                        ->default(true)
                        ->helperText('Is this product currently available?'),
                    
                    Forms\Components\TextInput::make('order')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->helperText('Display order (lower = shows first)'),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 
                        'https://ui-avatars.com/api/?name=' . urlencode($record->name)
                    ),
                
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => Str::limit($record->description, 50))
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('header_title')
                    ->label('Category')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->placeholder('Uncategorized')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->branch 
                            ? $record->branch->branch_title 
                            : 'All Locations'
                    )
                    ->getStateUsing(fn ($record) => 
                        $record->business_id 
                            ? $record->business->business_name
                            : $record->branch->business->business_name
                    )
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('location_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => $state === 'Business Level' ? 'success' : 'warning')
                    ->getStateUsing(fn ($record) => 
                        $record->isStandalone() ? 'Business Level' : 'Branch Specific'
                    )
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('price')
                    ->money('NGN')
                    ->sortable()
                    ->description(fn ($record) => 
                        $record->hasDiscount() 
                            ? '🏷️ ' . round($record->getDiscountPercentage()) . '% off'
                            : null
                    ),
                
                Tables\Columns\TextColumn::make('final_price')
                    ->label('Final Price')
                    ->money('NGN')
                    ->sortable()
                    ->weight('bold')
                    ->color(fn ($record) => $record->hasDiscount() ? 'success' : 'gray'),
                
                Tables\Columns\IconColumn::make('is_available')
                    ->boolean()
                    ->label('Available')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('order')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('business_branch_id')
                    ->label('Branch')
                    ->relationship('branch', 'branch_title')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('header_title')
                    ->label('Category')
                    ->options(function () {
                        return Product::query()
                            ->distinct()
                            ->whereNotNull('header_title')
                            ->pluck('header_title', 'header_title')
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
                
                Tables\Filters\TernaryFilter::make('is_available')
                    ->label('Availability')
                    ->placeholder('All products')
                    ->trueLabel('Available only')
                    ->falseLabel('Unavailable only'),
                
                Tables\Filters\Filter::make('has_discount')
                    ->label('Has Discount')
                    ->query(fn (Builder $query) => 
                        $query->where('discount_type', '!=', 'none')
                            ->where('discount_value', '>', 0)
                    ),
                
                Tables\Filters\Filter::make('type')
                    ->label('Location Type')
                    ->form([
                        Forms\Components\Select::make('type')
                            ->options([
                                'business' => 'Business Level Only',
                                'branch' => 'Branch Specific Only',
                            ])
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['type'] === 'business', 
                                fn ($q) => $q->whereNotNull('business_id')->whereNull('business_branch_id')
                            )
                            ->when($data['type'] === 'branch', 
                                fn ($q) => $q->whereNotNull('business_branch_id')
                            );
                    }),
                
                Tables\Filters\Filter::make('price_range')
                    ->form([
                        Forms\Components\TextInput::make('price_from')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Price from'),
                        Forms\Components\TextInput::make('price_to')
                            ->numeric()
                            ->prefix('₦')
                            ->label('Price to'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['price_from'], fn ($q, $val) => 
                                $q->where('final_price', '>=', $val)
                            )
                            ->when($data['price_to'], fn ($q, $val) => 
                                $q->where('final_price', '<=', $val)
                            );
                    }),
                
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    
                    Tables\Actions\Action::make('toggle_availability')
                        ->label(fn ($record) => $record->is_available ? 'Mark Unavailable' : 'Mark Available')
                        ->icon(fn ($record) => $record->is_available ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                        ->color(fn ($record) => $record->is_available ? 'warning' : 'success')
                        ->requiresConfirmation()
                        ->action(function (Product $record) {
                            $record->update(['is_available' => !$record->is_available]);
                            
                            Notification::make()
                                ->success()
                                ->title('Availability Updated')
                                ->body($record->is_available 
                                    ? 'Product is now available.' 
                                    : 'Product is now unavailable.'
                                )
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('apply_discount')
                        ->label('Apply Discount')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('discount_type')
                                ->options([
                                    'percentage' => 'Percentage Off',
                                    'fixed' => 'Fixed Amount Off',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),
                            
                            Forms\Components\TextInput::make('discount_value')
                                ->numeric()
                                ->required()
                                ->suffix(fn (Forms\Get $get) => 
                                    $get('discount_type') === 'percentage' ? '%' : ''
                                )
                                ->label('Discount Value'),
                        ])
                        ->action(function (Product $record, array $data) {
                            $record->update($data);
                            
                            Notification::make()
                                ->success()
                                ->title('Discount Applied')
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('remove_discount')
                        ->label('Remove Discount')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Product $record) {
                            $record->update([
                                'discount_type' => 'none',
                                'discount_value' => 0,
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Discount Removed')
                                ->send();
                        })
                        ->visible(fn (Product $record) => $record->hasDiscount()),
                    
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('mark_available')
                        ->label('Mark Available')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_available' => true]);
                            
                            Notification::make()
                                ->success()
                                ->title('Products Updated')
                                ->body(count($records) . ' products marked as available.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('mark_unavailable')
                        ->label('Mark Unavailable')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update(['is_available' => false]);
                            
                            Notification::make()
                                ->warning()
                                ->title('Products Updated')
                                ->body(count($records) . ' products marked as unavailable.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('apply_bulk_discount')
                        ->label('Apply Discount to Selected')
                        ->icon('heroicon-o-tag')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('discount_type')
                                ->options([
                                    'percentage' => 'Percentage Off',
                                    'fixed' => 'Fixed Amount Off',
                                ])
                                ->required()
                                ->native(false)
                                ->live(),
                            
                            Forms\Components\TextInput::make('discount_value')
                                ->numeric()
                                ->required()
                                ->suffix(fn (Forms\Get $get) => 
                                    $get('discount_type') === 'percentage' ? '%' : ''
                                )
                                ->label('Discount Value'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update($data);
                            
                            Notification::make()
                                ->success()
                                ->title('Bulk Discount Applied')
                                ->body(count($records) . ' products updated with discount.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('remove_bulk_discount')
                        ->label('Remove Discount from Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each->update([
                                'discount_type' => 'none',
                                'discount_value' => 0,
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Bulk Discounts Removed')
                                ->body(count($records) . ' products updated.')
                                ->send();
                        }),
                    
                    Tables\Actions\BulkAction::make('change_category')
                        ->label('Change Category')
                        ->icon('heroicon-o-tag')
                        ->color('info')
                        ->form([
                            Forms\Components\TextInput::make('header_title')
                                ->label('Category/Section Header')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each->update(['header_title' => $data['header_title']]);
                            
                            Notification::make()
                                ->success()
                                ->title('Category Updated')
                                ->body(count($records) . ' products moved to "' . $data['header_title'] . '".')
                                ->send();
                        }),
                ]),
            ])
            ->reorderable('order')
            ->emptyStateHeading('No Products Yet')
            ->emptyStateDescription('Start by adding products, services, or menu items.')
            ->emptyStateIcon('heroicon-o-shopping-bag');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Product Information')
                    ->schema([
                        Components\ImageEntry::make('image')
                            ->hiddenLabel()
                            ->columnSpanFull(),
                        
                        Components\TextEntry::make('name')
                            ->size('lg')
                            ->weight('bold'),
                        
                        Components\TextEntry::make('header_title')
                            ->label('Category')
                            ->badge()
                            ->color('info')
                            ->placeholder('Uncategorized'),
                        
                        Components\TextEntry::make('description')
                            ->columnSpanFull()
                            ->visible(fn ($record) => filled($record->description)),
                    ])
                    ->columns(2),
                
                Components\Section::make('Location')
                    ->schema([
                        Components\TextEntry::make('business.business_name')
                            ->label('Business')
                            ->visible(fn ($record) => $record->isStandalone())
                            ->url(fn ($record) => 
                                route('filament.admin.resources.businesses.view', $record->business)
                            )
                            ->color('primary'),
                        
                        Components\TextEntry::make('branch.branch_title')
                            ->label('Branch')
                            ->visible(fn ($record) => $record->isBranchProduct())
                            ->url(fn ($record) => 
                                route('filament.admin.resources.business-branches.view', $record->branch)
                            )
                            ->color('primary'),
                        
                        Components\TextEntry::make('location_type')
                            ->label('Type')
                            ->badge()
                            ->color(fn ($state) => $state === 'Business Level' ? 'success' : 'warning')
                            ->getStateUsing(fn ($record) => 
                                $record->isStandalone() ? 'Business Level' : 'Branch Specific'
                            ),
                    ])
                    ->columns(3),
                
                Components\Section::make('Pricing')
                    ->schema([
                        Components\TextEntry::make('price')
                            ->label('Original Price')
                            ->money('NGN')
                            ->size('lg'),
                        
                        Components\TextEntry::make('discount_type')
                            ->label('Discount Type')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'none' => 'No Discount',
                                'percentage' => 'Percentage Off',
                                'fixed' => 'Fixed Amount',
                                default => ucfirst($state)
                            })
                            ->badge()
                            ->color(fn ($state) => $state === 'none' ? 'gray' : 'warning'),
                        
                        Components\TextEntry::make('discount_value')
                            ->label('Discount')
                            ->formatStateUsing(fn ($record) => 
                                $record->discount_type === 'percentage'
                                    ? $record->discount_value . '%'
                                    : '₦' . number_format($record->discount_value, 2)
                            )
                            ->visible(fn ($record) => $record->hasDiscount()),
                        
                        Components\TextEntry::make('final_price')
                            ->label('Final Price')
                            ->money('NGN')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                        
                        Components\TextEntry::make('savings')
                            ->label('You Save')
                            ->money('NGN')
                            ->color('success')
                            ->visible(fn ($record) => $record->hasDiscount()),
                    ])
                    ->columns(3),
                
                Components\Section::make('Settings')
                    ->schema([
                        Components\IconEntry::make('is_available')
                            ->boolean()
                            ->label('Available'),
                        
                        Components\TextEntry::make('order')
                            ->label('Display Order'),
                        
                        Components\TextEntry::make('slug')
                            ->copyable(),
                    ])
                    ->columns(3)
                    ->collapsible(),
                
                Components\Section::make('Timestamps')
                    ->schema([
                        Components\TextEntry::make('created_at')
                            ->dateTime(),
                        
                        Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'view' => Pages\ViewProduct::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                \Illuminate\Database\Eloquent\SoftDeletingScope::class,
            ]);
    }
}