<?php

// ============================================
// 3. ManagerActivityLogResource.php (Read-Only)
// Location: app/Filament/Admin/Resources/ManagerActivityLogResource.php
// Panel: Admin Panel - Access: Admins
// ============================================
namespace App\Filament\Admin\Resources;
use App\Filament\Admin\Resources\ManagerActivityLogResource\Pages;
use App\Models\ManagerActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ManagerActivityLogResource extends Resource
{
    protected static ?string $model = ManagerActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Manager Activity Logs';
    protected static ?string $navigationGroup = 'Business Management';
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('manager.user.name')->label('Manager')->searchable()->sortable(),
            Tables\Columns\TextColumn::make('branch.branch_title')->label('Branch')->searchable(),
            Tables\Columns\TextColumn::make('action')->badge()->formatStateUsing(fn ($s) => str_replace('_', ' ', ucwords($s, '_'))),
            Tables\Columns\TextColumn::make('description')->limit(50)->wrap()->searchable(),
            Tables\Columns\TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('created_at')->label('Time')->dateTime()->sortable()->since()->description(fn ($r) => $r->created_at->format('M d, Y h:i A')),
        ])->defaultSort('created_at', 'desc')->filters([
            Tables\Filters\SelectFilter::make('manager_id')->relationship('manager', 'id')->getOptionLabelFromRecordUsing(fn ($r) => $r->user->name)->searchable(),
            Tables\Filters\SelectFilter::make('action')->options(fn () => ManagerActivityLog::distinct()->pluck('action', 'action')->toArray()),
            Tables\Filters\Filter::make('today')->query(fn ($q) => $q->whereDate('created_at', today())),
        ])->actions([Tables\Actions\ViewAction::make()])->bulkActions([]);
    }
    public static function getPages(): array { return ['index' => Pages\ListManagerActivityLogs::route('/'), 'view' => Pages\ViewManagerActivityLog::route('/{record}')]; }
    public static function canCreate(): bool { return false; }
}