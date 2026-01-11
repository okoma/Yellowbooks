<?php
// ============================================
// app/Filament/Business/Resources/BranchResource/RelationManagers/ViewsRelationManager.php
// View branch analytics and view statistics
// ============================================

namespace App\Filament\Business\Resources\BranchResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ViewsRelationManager extends RelationManager
{
    protected static string $relationship = 'views';
    
    protected static ?string $title = 'View Analytics';
    
    protected static ?string $icon = 'heroicon-o-eye';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Read-only - views are tracked automatically
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('viewed_at')
            ->defaultSort('viewed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('viewed_at')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->label('Viewed'),
                
                Tables\Columns\TextColumn::make('referral_source')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'yellowbooks' => 'success',
                        'google' => 'info',
                        'facebook' => 'primary',
                        'instagram' => 'danger',
                        'direct' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match($state) {
                        'yellowbooks' => 'heroicon-o-home',
                        'google' => 'heroicon-o-magnifying-glass',
                        'facebook' => 'heroicon-o-user-group',
                        'instagram' => 'heroicon-o-camera',
                        'direct' => 'heroicon-o-link',
                        default => 'heroicon-o-globe-alt',
                    }),
                
                Tables\Columns\TextColumn::make('country')
                    ->searchable()
                    ->icon('heroicon-o-map-pin'),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('device_type')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'mobile' => 'success',
                        'tablet' => 'warning',
                        'desktop' => 'info',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('view_date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('view_hour')
                    ->label('Hour')
                    ->suffix(':00')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('referral_source')
                    ->options([
                        'yellowbooks' => 'YellowBooks',
                        'google' => 'Google',
                        'bing' => 'Bing',
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter',
                        'linkedin' => 'LinkedIn',
                        'direct' => 'Direct',
                        'other' => 'Other',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('device_type')
                    ->options([
                        'mobile' => 'Mobile',
                        'tablet' => 'Tablet',
                        'desktop' => 'Desktop',
                    ])
                    ->multiple(),
                
                Tables\Filters\Filter::make('viewed_at')
                    ->form([
                        Forms\Components\DatePicker::make('viewed_from')
                            ->label('From'),
                        Forms\Components\DatePicker::make('viewed_until')
                            ->label('Until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['viewed_from'], fn ($q, $date) => $q->whereDate('viewed_at', '>=', $date))
                            ->when($data['viewed_until'], fn ($q, $date) => $q->whereDate('viewed_at', '<=', $date));
                    }),
            ])
            ->actions([
                // No actions - read-only analytics
            ])
            ->bulkActions([
                // No bulk actions
            ])
            ->emptyStateHeading('No Views Yet')
            ->emptyStateDescription('View statistics will appear here once customers visit your branch page.')
            ->emptyStateIcon('heroicon-o-eye-slash');
    }
    
    public function isReadOnly(): bool
    {
        return true;
    }
}