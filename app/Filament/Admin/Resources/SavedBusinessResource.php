<?php
// ============================================
// app/Filament/Admin/Resources/SavedBusinessResource.php
// Panel: Admin Panel
// Purpose: Track user bookmarks/saved businesses (READ-ONLY)
// ============================================

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SavedBusinessResource\Pages;
use App\Models\SavedBusiness;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms;

class SavedBusinessResource extends Resource
{
    protected static ?string $model = SavedBusiness::class;
    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationLabel = 'Saved Businesses';
    protected static ?string $navigationGroup = 'Analytics';
    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->user->email)
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record->user)),
                
                Tables\Columns\TextColumn::make('branch.business.business_name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->branch->branch_title)
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('branch.city')
                    ->label('Location')
                    ->searchable()
                    ->description(fn ($record) => $record->branch->state),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Saved On')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M d, Y')),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('business_branch_id')
                    ->label('Business Branch')
                    ->relationship('branch', 'branch_title')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('saved_from')->label('Saved from'),
                        Forms\Components\DatePicker::make('saved_until')->label('Saved until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['saved_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['saved_until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                
                Tables\Filters\Filter::make('this_month')
                    ->label('This Month')
                    ->query(fn ($query) => $query->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Remove')
                    ->successNotificationTitle('Bookmark removed'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Remove Selected'),
            ])
            ->emptyStateHeading('No Saved Businesses Yet')
            ->emptyStateDescription('User bookmarks will appear here.')
            ->emptyStateIcon('heroicon-o-bookmark-slash');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavedBusinesses::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $todayCount = static::getModel()::whereDate('created_at', today())->count();
        return $todayCount > 0 ? number_format($todayCount) : null;
    }
}