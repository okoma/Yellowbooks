<?php
// ============================================
// app/Filament/Business/Resources/BranchResource.php
// Complete Branch Management Resource with Wizard
// ============================================

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\BranchResource\Pages;
use App\Filament\Business\Resources\BranchResource\RelationManagers;
use App\Models\BusinessBranch;
use App\Models\Business;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BranchResource extends Resource
{
    protected static ?string $model = BusinessBranch::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    
    protected static ?string $navigationLabel = 'My Branches';
    
    protected static ?string $navigationGroup = null;
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Wizard handled in CreateBranch page
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                BusinessBranch::query()
                    ->whereHas('business', fn($q) => $q->where('user_id', Auth::id()))
            )
            ->columns([
                Tables\Columns\TextColumn::make('business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
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
                    ->formatStateUsing(fn ($state) => number_format($state, 1) . ' ⭐')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('reviews_count')
                    ->counts('reviews')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('leads_count')
                    ->counts('leads')
                    ->badge()
                    ->color('warning'),
                
                Tables\Columns\TextColumn::make('views_count')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('business_id')
                    ->label('Business')
                    ->relationship('business', 'business_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('is_main_branch')
                    ->label('Main Branch Only'),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Only'),
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
            RelationManagers\ProductsRelationManager::class,
            RelationManagers\LeadsRelationManager::class,
            RelationManagers\ReviewsRelationManager::class,
            RelationManagers\OfficialsRelationManager::class,
            RelationManagers\ManagersRelationManager::class,
            RelationManagers\ViewsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereHas('business', fn($q) => 
            $q->where('user_id', Auth::id())
        )->count();
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('business', fn($q) => $q->where('user_id', Auth::id()));
    }
}