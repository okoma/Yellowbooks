<?php
// ============================================
// app/Filament/Business/Resources/BusinessResource.php
// Complete Business Management Resource with Wizard
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BusinessResource\Pages;
use App\Filament\Business\Resources\BusinessResource\RelationManagers;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Amenity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BusinessResource extends Resource
{
    protected static ?string $model = Business::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    
    protected static ?string $navigationLabel = 'My Businesses';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Wizard handled in CreateBusiness page
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(Business::query()->where('user_id', Auth::id()))
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->business_name)),
                
                Tables\Columns\TextColumn::make('business_name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('businessType.name')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->icon('heroicon-o-map-pin'),
                
                Tables\Columns\TextColumn::make('branches_count')
                    ->counts('branches')
                    ->label('Branches')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'active',
                        'danger' => 'suspended',
                    ]),
                
                Tables\Columns\IconColumn::make('is_verified')
                    ->boolean()
                    ->label('Verified'),
                
                Tables\Columns\IconColumn::make('is_premium')
                    ->boolean()
                    ->label('Premium'),
                
                Tables\Columns\TextColumn::make('avg_rating')
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_reviews')
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'pending_review' => 'Pending Review',
                        'active' => 'Active',
                        'suspended' => 'Suspended',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_verified')
                    ->label('Verified Only'),
                
                Tables\Filters\TernaryFilter::make('is_premium')
                    ->label('Premium Only'),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\BranchesRelationManager::class,
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\LeadsRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\OfficialsRelationManager::class,
            RelationManagers\SocialAccountsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBusinesses::route('/'),
            'create' => Pages\CreateBusiness::route('/create'),
            'view' => Pages\ViewBusiness::route('/{record}'),
            'edit' => Pages\EditBusiness::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('user_id', Auth::id())->count();
    }
}